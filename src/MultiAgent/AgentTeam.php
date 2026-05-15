<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\MultiAgent;

use Token27\NexusAI\Agents\Contract\AgentInterface;
use Token27\NexusAI\Agents\Core\AgentResult;

/**
 * A team of agents collaborating on a shared task.
 *
 * Each member receives the same input and can communicate with
 * others via the shared MessageBus. Messages are delivered
 * to each agent before its execution begins.
 *
 * @see \Token27\NexusAI\Agents\MultiAgent\MessageBus
 * @see \Token27\NexusAI\Agents\MultiAgent\OrchestratorAgent
 */
final class AgentTeam
{
    /** @var array<AgentInterface> Team members. */
    private array $members = [];

    /** @var MessageBus Shared message bus. */
    private readonly MessageBus $bus;

    public function __construct()
    {
        $this->bus = new MessageBus();
    }

    /**
     * Adds an agent to the team.
     *
     * @param AgentInterface $agent The agent to add.
     * @return $this
     */
    public function addMember(AgentInterface $agent): self
    {
        $this->members[] = $agent;

        return $this;
    }

    /**
     * Returns all team members.
     *
     * @return array<AgentInterface> The team members.
     */
    public function getMembers(): array
    {
        return $this->members;
    }

    /**
     * Returns the shared message bus.
     *
     * @return MessageBus The message bus.
     */
    public function getBus(): MessageBus
    {
        return $this->bus;
    }

    /**
     * Runs all agents with the same input.
     *
     * Each agent receives pending messages from the bus before execution.
     * Results are keyed by agent name.
     *
     * @param string $input The shared task input.
     * @return array<string, AgentResult> Results keyed by agent name.
     */
    public function runAll(string $input): array
    {
        $results = [];

        foreach ($this->members as $agent) {
            $name = $agent->getName();

            // Inject pending messages into context
            $context = [];
            if ($this->bus->hasMessages($name)) {
                $context['_messages'] = $this->bus->receive($name);
            }

            $results[$name] = $agent->run($input, $context);
        }

        return $results;
    }
}
