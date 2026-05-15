<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Observability\Event;

/**
 * Event emitted after each step execution.
 *
 * Contains the step number, action type, output, and timing.
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
final readonly class AgentStep
{
    /**
     * @param string $agentName The agent's name.
     * @param int $stepNumber The 1-based step number.
     * @param string $stepAction The action type (think, act, observe, output).
     * @param string $stepOutput The step's output text.
     * @param float $elapsedMs Milliseconds since agent start.
     */
    public function __construct(
        public string $agentName,
        public int $stepNumber,
        public string $stepAction,
        public string $stepOutput,
        public float $elapsedMs,
    ) {
    }
}
