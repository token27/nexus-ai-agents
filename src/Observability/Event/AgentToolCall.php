<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Observability\Event;

/**
 * Event emitted when the agent invokes a tool.
 *
 * Contains the tool name and arguments. Emitted by the executor
 * when a step involves a tool call.
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 * @see \Token27\NexusAI\Contract\ToolInterface
 */
final readonly class AgentToolCall
{
    /**
     * @param string $agentName The agent's name.
     * @param string $toolName The invoked tool name.
     * @param array<string, mixed> $arguments The arguments passed to the tool.
     */
    public function __construct(
        public string $agentName,
        public string $toolName,
        public array $arguments,
    ) {
    }
}
