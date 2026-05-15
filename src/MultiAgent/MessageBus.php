<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\MultiAgent;

/**
 * Message bus for inter-agent communication in multi-agent teams.
 *
 * Agents send messages to each other through this bus without
 * needing direct references. Messages are stored per recipient
 * and delivered on demand. Subscribers are notified on every send.
 *
 * @see \Token27\NexusAI\Agents\MultiAgent\AgentTeam
 * @see \Token27\NexusAI\Agents\MultiAgent\OrchestratorAgent
 */
final class MessageBus
{
    /** @var array<string, array<int, array{from: string, content: string, metadata: array<string, mixed>}>> */
    private array $messages = [];

    /** @var array<callable> Subscriber callbacks. */
    private array $subscribers = [];

    /**
     * Sends a message from one agent to another.
     *
     * @param string $from Sender agent name.
     * @param string $to Recipient agent name.
     * @param string $content Message content.
     * @param array<string, mixed> $metadata Optional metadata.
     */
    public function send(string $from, string $to, string $content, array $metadata = []): void
    {
        if (!isset($this->messages[$to])) {
            $this->messages[$to] = [];
        }

        $this->messages[$to][] = [
            'from' => $from,
            'content' => $content,
            'metadata' => $metadata,
        ];

        // Notify subscribers
        foreach ($this->subscribers as $callback) {
            $callback($from, $to, $content, $metadata);
        }
    }

    /**
     * Receives and clears all messages for an agent.
     *
     * @param string $agentName The agent's name.
     * @return array<int, array{from: string, content: string, metadata: array<string, mixed>}>
     */
    public function receive(string $agentName): array
    {
        $msgs = $this->messages[$agentName] ?? [];
        unset($this->messages[$agentName]);

        return $msgs;
    }

    /**
     * Registers a subscriber callback notified on every send().
     *
     * @param callable(string, string, string, array<string, mixed>): void $callback
     */
    public function subscribe(callable $callback): void
    {
        $this->subscribers[] = $callback;
    }

    /**
     * Checks if an agent has pending messages.
     *
     * @param string $agentName The agent's name.
     * @return bool True if messages are pending.
     */
    public function hasMessages(string $agentName): bool
    {
        return !empty($this->messages[$agentName]);
    }
}
