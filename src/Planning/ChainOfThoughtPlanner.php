<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Planning;

use Token27\NexusAI\Agents\Contract\PlannerInterface;
use Token27\NexusAI\Agents\Contract\PlanningStrategyInterface;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Contract\DriverInterface;
use Token27\NexusAI\Message\UserMessage;
use Token27\NexusAI\Request\TextRequest;

/**
 * Chain-of-Thought planning strategy.
 *
 * The LLM decomposes the problem and reasons step by step before
 * giving a final answer. Best for complex reasoning tasks that
 * don't require tool usage.
 *
 * Based on: Wei et al., 2022 — "Chain-of-Thought Prompting Elicits
 * Reasoning in Large Language Models."
 *
 * @see \Token27\NexusAI\Agents\Enum\PlanningStrategy::ChainOfThought
 */
final class ChainOfThoughtPlanner implements PlannerInterface, PlanningStrategyInterface
{
    /**
     * @param DriverInterface|null $driver LLM driver for dynamic planning. Null = static fallback.
     * @param string $provider Provider key for LLM calls.
     * @param string $model Model for LLM calls.
     */
    public function __construct(
        private readonly ?DriverInterface $driver = null,
        private readonly string $provider = 'openai',
        private readonly string $model = 'gpt-4o',
    ) {
    }

    public function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a Chain-of-Thought agent. Think step by step before answering.

For every task:
1. Break down the problem into smaller parts.
2. Reason through each part sequentially.
3. Show your work clearly.
4. Draw a final conclusion based on your reasoning.
PROMPT;
    }

    public function formatInput(string $input, AgentContext $context): string
    {
        $memoryContext = $context->get('_memory_context', '');

        if ($memoryContext !== '' && $memoryContext !== '0') {
            return sprintf("Context:\n%s\n\nProblem: %s\n\nThink through this step by step.", $memoryContext, $input);
        }

        return sprintf('Problem: %s\n\nThink through this step by step.', $input);
    }

    public function parseResponse(string $response): array
    {
        $parsed = [
            'steps' => [],
            'conclusion' => '',
        ];

        // Try to extract numbered reasoning steps
        if (preg_match_all('/(?:Step\s*\d+|^\d+)[.:]\s*(.+?)(?=(?:Step\s*\d+|^\d+)[.:]|\z)/ms', $response, $matches)) {
            $parsed['steps'] = array_map('trim', $matches[1]);
        }

        // Look for conclusion markers
        if (preg_match('/(?:Conclusion|Therefore|In conclusion|Final answer):\s*(.+?)$/is', $response, $matches)) {
            $parsed['conclusion'] = trim($matches[1]);
        } elseif ($parsed['steps'] !== []) {
            // If no explicit conclusion, use the last step
            $parsed['conclusion'] = end($parsed['steps']);
        } else {
            // Fallback: whole response is the conclusion
            $parsed['conclusion'] = trim($response);
        }

        return $parsed;
    }

    public function plan(string $input, AgentContext $context): Plan
    {
        if ($this->driver !== null) {
            try {
                return $this->planWithLLM($input, $context);
            } catch (\Throwable) {
                // Fallback to static plan
            }
        }

        return $this->staticPlan($input);
    }

    /**
     * Generates a dynamic plan by calling the LLM.
     */
    private function planWithLLM(string $input, AgentContext $context): Plan
    {
        $prompt = $this->formatInput($input, $context);

        $request = new TextRequest(
            provider: $this->provider,
            model: $this->model,
            messages: [new UserMessage($prompt)],
            systemPrompt: $this->getSystemPrompt() . "\n\nBreak down the problem into numbered steps. Each step should be a clear reasoning action.",
        );

        $response = $this->driver->text($request);
        $parsed = $this->parseResponse($response->text);

        $steps = [];

        foreach ($parsed['steps'] as $stepText) {
            $steps[] = new PlanStep(
                description: $stepText,
                action: 'think',
                expectedOutput: 'Reasoning for this component.',
            );
        }

        // Always end with output step
        if ($steps !== []) {
            $steps[] = new PlanStep(
                description: 'Draw a conclusion and output the final answer.',
                action: 'output',
                expectedOutput: $parsed['conclusion'],
            );
        }

        if ($steps === []) {
            return $this->staticPlan($input);
        }

        return new Plan(
            steps: $steps,
            goal: $input,
            reasoning: 'Using Chain-of-Thought: dynamically generated reasoning plan via LLM.',
        );
    }

    /**
     * Returns the static fallback plan.
     */
    private function staticPlan(string $input): Plan
    {
        return new Plan(
            steps: [
                new PlanStep(
                    description: 'Analyze the problem and identify key components.',
                    action: 'think',
                    expectedOutput: 'A breakdown of the problem into sub-problems.',
                ),
                new PlanStep(
                    description: 'Reason through each component step by step.',
                    action: 'think',
                    expectedOutput: 'Step-by-step reasoning for each component.',
                ),
                new PlanStep(
                    description: 'Draw a conclusion based on the reasoning.',
                    action: 'think',
                    expectedOutput: 'A well-reasoned conclusion.',
                ),
                new PlanStep(
                    description: 'Output the final answer with reasoning trace.',
                    action: 'output',
                    expectedOutput: 'The final answer with clear reasoning.',
                ),
            ],
            goal: $input,
            reasoning: 'Using Chain-of-Thought: decompose the problem and reason step by step.',
        );
    }
}
