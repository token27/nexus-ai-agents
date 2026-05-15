<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Observability\Event;

/**
 * Event emitted when the agent completes successfully.
 *
 * Contains the final output and aggregate metrics.
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 * @see \Token27\NexusAI\Agents\Core\AgentResult
 */
final readonly class AgentCompleted
{
    /**
     * @param string $agentName The agent's name.
     * @param string $output The final output.
     * @param int $totalSteps Total steps executed.
     * @param float $elapsedMs Total time in milliseconds.
     * @param float $totalCost Total cost in USD.
     */
    public function __construct(
        public string $agentName,
        public string $output,
        public int $totalSteps,
        public float $elapsedMs,
        public float $totalCost,
    ) {
    }
}
