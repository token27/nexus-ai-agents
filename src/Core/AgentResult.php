<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Core;

use Token27\NexusAI\Agents\Enum\AgentStatus;

/**
 * Final result of an agent execution.
 *
 * Immutable value object containing the output, metrics (steps, time, cost),
 * and a complete trace of every phase in the agent loop.
 *
 * @see \Token27\NexusAI\Agents\Contract\AgentInterface::run()
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
final readonly class AgentResult
{
    /**
     * @param AgentStatus $status Final status (Completed or Failed).
     * @param string $output Final output text from the agent.
     * @param int $totalSteps Total steps executed.
     * @param float $elapsedMs Total time in milliseconds.
     * @param float $totalCost Total cost in USD.
     * @param array<int, mixed> $trace Step-by-step trace of execution.
     * @param array<string, mixed> $metadata Additional metadata.
     */
    public function __construct(
        public AgentStatus $status,
        public string $output = '',
        public int $totalSteps = 0,
        public float $elapsedMs = 0.0,
        public float $totalCost = 0.0,
        public array $trace = [],
        public array $metadata = [],
    ) {
    }

    /**
     * Whether the agent completed successfully.
     *
     * @return bool True if status is Completed.
     */
    public function isSuccess(): bool
    {
        return $this->status === AgentStatus::Completed;
    }

    /**
     * Whether the agent failed.
     *
     * @return bool True if status is Failed.
     */
    public function isFailed(): bool
    {
        return $this->status === AgentStatus::Failed;
    }
}
