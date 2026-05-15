<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Planning;

/**
 * A single step in an agent's execution plan.
 *
 * Each step describes what action to take (think, act, observe, output),
 * provides arguments for that action, and states the expected result.
 *
 * @see \Token27\NexusAI\Agents\Planning\Plan
 * @see \Token27\NexusAI\Agents\Contract\ExecutorInterface
 */
final readonly class PlanStep
{
    /**
     * @param string $description Human-readable description of the step.
     * @param string $action Action type: 'think', 'act', 'observe', 'output'.
     * @param array<string, mixed> $arguments Arguments for the action.
     * @param string $expectedOutput What this step is expected to produce.
     */
    public function __construct(
        public string $description,
        public string $action = 'think',
        public array $arguments = [],
        public string $expectedOutput = '',
    ) {
    }

    /**
     * Serializes the step to an array for trace/debugging.
     *
     * @return array<string, mixed> The step as an array.
     */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'action' => $this->action,
            'arguments' => $this->arguments,
            'expectedOutput' => $this->expectedOutput,
        ];
    }
}
