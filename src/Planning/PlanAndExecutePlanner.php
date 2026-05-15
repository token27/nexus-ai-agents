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
 * Plan-and-Execute planning strategy.
 *
 * The LLM first generates a complete plan, then executes it step by step,
 * verifying each result. Best for tasks that require structured, upfront
 * planning before execution.
 *
 * @see \Token27\NexusAI\Agents\Enum\PlanningStrategy::PlanAndExecute
 */
final class PlanAndExecutePlanner implements PlannerInterface, PlanningStrategyInterface
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
You are a Plan-and-Execute agent. Follow this process:

1. PLAN: Create a numbered list of steps to complete the task.
2. EXECUTE: Go through each step one at a time.
3. VERIFY: After each step, check if the output is correct.
4. FINALIZE: Compile all results into a final answer.

Always plan before acting. Verify each step before moving on.
PROMPT;
    }

    public function formatInput(string $input, AgentContext $context): string
    {
        $memoryContext = $context->get('_memory_context', '');
        $plan = $context->getPlan();

        if ($plan !== null && $plan->steps !== []) {
            $stepsText = implode("\n", array_map(
                static fn (int $i, PlanStep $s): string => sprintf('  %d. %s', $i + 1, $s->description),
                array_keys($plan->steps),
                $plan->steps,
            ));

            if ($memoryContext !== '' && $memoryContext !== '0') {
                return sprintf(
                    "Context:\n%s\n\nPlan:\n%s\n\nNow execute: %s",
                    $memoryContext,
                    $stepsText,
                    $input,
                );
            }

            return sprintf("Plan:\n%s\n\nNow execute: %s", $stepsText, $input);
        }

        if ($memoryContext !== '' && $memoryContext !== '0') {
            return sprintf("Context:\n%s\n\nCreate a plan for: %s", $memoryContext, $input);
        }

        return sprintf('Create a plan for: %s', $input);
    }

    public function parseResponse(string $response): array
    {
        $parsed = [
            'plan' => [],
            'execution' => [],
            'verification' => '',
            'finalOutput' => '',
        ];

        // Extract plan steps (numbered list after PLAN: marker)
        if (preg_match('/PLAN:\s*(.+?)(?=EXECUTE:|VERIFY:|FINALIZE:|\z)/s', $response, $matches)) {
            if (preg_match_all('/^\s*(?:\d+[.)]\s*)(.+?)$/m', $matches[1], $stepMatches)) {
                $parsed['plan'] = array_map('trim', $stepMatches[1]);
            }
        }

        // If no PLAN: marker, try numbered list directly
        if ($parsed['plan'] === []) {
            if (preg_match_all('/^\s*\d+[.)]\s*(.+?)$/m', $response, $stepMatches)) {
                $parsed['plan'] = array_map('trim', $stepMatches[1]);
            }
        }

        // Extract final output
        if (preg_match('/FINALIZE:\s*(.+?)$/s', $response, $matches)) {
            $parsed['finalOutput'] = trim($matches[1]);
        } elseif (preg_match('/Final Answer:\s*(.+?)$/s', $response, $matches)) {
            $parsed['finalOutput'] = trim($matches[1]);
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
            systemPrompt: $this->getSystemPrompt() . "\n\nCreate a numbered list of concrete, actionable steps. Each step should be specific enough to execute independently.",
        );

        $response = $this->driver->text($request);
        $parsed = $this->parseResponse($response->text);

        $steps = [];

        foreach ($parsed['plan'] as $i => $stepText) {
            // First step is always 'think' (planning), middle steps are 'act', last is 'output'
            $action = match (true) {
                $i === 0 => 'think',
                $i === count($parsed['plan']) - 1 => 'output',
                default => 'act',
            };

            $steps[] = new PlanStep(
                description: $stepText,
                action: $action,
                expectedOutput: 'Result of step ' . ($i + 1),
            );
        }

        if ($steps === []) {
            return $this->staticPlan($input);
        }

        return new Plan(
            steps: $steps,
            goal: $input,
            reasoning: 'Using Plan-and-Execute: dynamically generated structured plan via LLM.',
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
                    description: 'Create a detailed plan with numbered steps.',
                    action: 'think',
                    expectedOutput: 'A numbered list of concrete steps.',
                ),
                new PlanStep(
                    description: 'Execute the first step of the plan.',
                    action: 'act',
                    expectedOutput: 'Result of the first execution step.',
                ),
                new PlanStep(
                    description: 'Verify the result and proceed or correct.',
                    action: 'observe',
                    expectedOutput: 'Verification result. Continue or replan.',
                ),
                new PlanStep(
                    description: 'Compile all results into the final output.',
                    action: 'output',
                    expectedOutput: 'The compiled final answer.',
                ),
            ],
            goal: $input,
            reasoning: 'Using Plan-and-Execute: plan the full structure first, then execute sequentially.',
        );
    }
}
