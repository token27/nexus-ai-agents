<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Observability\Event;

/**
 * Event emitted when the agent fails.
 *
 * Contains the error message and the step where the failure occurred.
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 * @see \Token27\NexusAI\Agents\Core\AgentResult
 */
final readonly class AgentFailed
{
    /**
     * @param string $agentName The agent's name.
     * @param string $error The error message.
     * @param int $totalSteps Steps executed before failure.
     * @param float $elapsedMs Total time in milliseconds.
     */
    public function __construct(
        public string $agentName,
        public string $error,
        public int $totalSteps,
        public float $elapsedMs,
    ) {
    }
}
