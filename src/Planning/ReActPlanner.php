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
 * ReAct (Reasoning + Acting) planning strategy.
 *
 * The LLM alternates between Thought (reasoning), Action (executing),
 * and Observation (evaluating results). This is the most common strategy
 * for agents with tools.
 *
 * Based on: Yao et al., 2023 — "ReAct: Synergizing Reasoning and Acting
 * in Language Models."
 *
 * @see \Token27\NexusAI\Agents\Enum\PlanningStrategy::ReAct
 */
final class ReActPlanner implements PlannerInterface, PlanningStrategyInterface
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
You are a ReAct agent. Follow this format:

Thought: [Your reasoning about what to do next]
Action: [The action to take]
Observation: [The result of the action]
... (repeat Thought/Action/Observation as needed)
Final Answer: [The final response to the user]
PROMPT;
    }

    public function formatInput(string $input, AgentContext $context): string
    {
        $memoryContext = $context->get('_memory_context', '');

        if ($memoryContext !== '' && $memoryContext !== '0') {
            return sprintf("Context:\n%s\n\nTask: %s", $memoryContext, $input);
        }

        return sprintf('Task: %s', $input);
    }

    public function parseResponse(string $response): array
    {
        $parsed = [
            'thoughts' => [],
            'actions' => [],
            'observations' => [],
            'finalAnswer' => '',
        ];

        // Extract Thought sections
        if (preg_match_all('/Thought:\s*(.+?)(?=\n(?:Thought:|Action:|Observation:|Final Answer:)|$)/s', $response, $matches)) {
            $parsed['thoughts'] = array_map('trim', $matches[1]);
        }

        // Extract Action sections
        if (preg_match_all('/Action:\s*(.+?)(?=\n(?:Thought:|Action:|Observation:|Final Answer:)|$)/s', $response, $matches)) {
            $parsed['actions'] = array_map('trim', $matches[1]);
        }

        // Extract Observation sections
        if (preg_match_all('/Observation:\s*(.+?)(?=\n(?:Thought:|Action:|Observation:|Final Answer:)|$)/s', $response, $matches)) {
            $parsed['observations'] = array_map('trim', $matches[1]);
        }

        // Extract Final Answer
        if (preg_match('/Final Answer:\s*(.+?)$/s', $response, $matches)) {
            $parsed['finalAnswer'] = trim($matches[1]);
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
            systemPrompt: $this->getSystemPrompt() . "\n\nGenerate a plan with numbered steps. Each step should have an action type (think, act, observe, output) and a description.",
        );

        $response = $this->driver->text($request);
        $parsed = $this->parseResponse($response->text);

        $steps = [];

        // Build steps from parsed actions
        foreach ($parsed['actions'] as $i => $action) {
            $thought = $parsed['thoughts'][$i] ?? '';
            $steps[] = new PlanStep(
                description: $thought !== '' ? $thought : $action,
                action: 'act',
                expectedOutput: $parsed['observations'][$i] ?? '',
            );
        }

        // If parsing produced no steps, build from thoughts
        if ($steps === [] && $parsed['thoughts'] !== []) {
            foreach ($parsed['thoughts'] as $thought) {
                $steps[] = new PlanStep(
                    description: $thought,
                    action: 'think',
                );
            }
        }

        // Always end with an output step
        if ($steps !== []) {
            $steps[] = new PlanStep(
                description: 'Provide the final answer based on all observations.',
                action: 'output',
                expectedOutput: $parsed['finalAnswer'],
            );
        }

        if ($steps === []) {
            return $this->staticPlan($input);
        }

        return new Plan(
            steps: $steps,
            goal: $input,
            reasoning: 'Using ReAct strategy: dynamically generated plan via LLM.',
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
                    description: 'Analyze the task and think about what needs to be done.',
                    action: 'think',
                    expectedOutput: 'A clear understanding of the task and next action.',
                ),
                new PlanStep(
                    description: 'Execute the planned action.',
                    action: 'act',
                    expectedOutput: 'The result of executing the action.',
                ),
                new PlanStep(
                    description: 'Observe and evaluate the result.',
                    action: 'observe',
                    expectedOutput: 'Analysis of whether the action succeeded.',
                ),
                new PlanStep(
                    description: 'Provide the final answer based on all observations.',
                    action: 'output',
                    expectedOutput: 'The final response to the user.',
                ),
            ],
            goal: $input,
            reasoning: 'Using ReAct strategy: interleaved reasoning and acting for tool-based tasks.',
        );
    }
}
