<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Observability\Event;

/**
 * Event emitted after the reflection phase.
 *
 * Contains the reflection decision and reasoning.
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 * @see \Token27\NexusAI\Agents\Reflection\ReflectionResult
 */
final readonly class AgentReflection
{
    /**
     * @param string $agentName The agent's name.
     * @param string $action The ReflectionAction value (e.g., 'continue', 'finish').
     * @param string|null $reasoning The reflector's reasoning.
     */
    public function __construct(
        public string $agentName,
        public string $action,
        public ?string $reasoning,
    ) {
    }
}
