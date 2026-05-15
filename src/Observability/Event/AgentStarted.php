<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Observability\Event;

/**
 * Event emitted when an agent starts execution.
 *
 * Contains the agent name, input text, and timestamp.
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
final readonly class AgentStarted
{
    /**
     * @param string $agentName The agent's name.
     * @param string $input The user input.
     * @param float $timestamp Unix timestamp with microseconds.
     */
    public function __construct(
        public string $agentName,
        public string $input,
        public float $timestamp,
    ) {
    }
}
