<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Contract;

use Token27\NexusAI\Agents\Core\AgentConfig;
use Token27\NexusAI\Agents\Core\AgentResult;

/**
 * Core contract for an autonomous AI agent.
 *
 * An agent is a named entity with a configuration and the ability
 * to process input through the Plan → Execute → Reflect loop.
 *
 * This interface defines WHAT an agent does; AbstractAgent
 * implements HOW (the agent loop).
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 * @see \Token27\NexusAI\Agents\Core\AgentConfig
 * @see \Token27\NexusAI\Agents\Core\AgentResult
 */
interface AgentInterface
{
    /**
     * Returns the unique name of the agent.
     *
     * @return string The agent's name.
     */
    public function getName(): string;

    /**
     * Returns a human-readable description of the agent's purpose.
     *
     * @return string|null The description, or null if not set.
     */
    public function getDescription(): ?string;

    /**
     * Returns the immutable agent configuration.
     *
     * @return AgentConfig The configuration (provider, model, budget, etc.).
     */
    public function getConfig(): AgentConfig;

    /**
     * Executes the agent with the given input and optional context.
     *
     * This triggers the full Plan → Execute → Reflect cycle.
     *
     * @param string $input The user's input/request.
     * @param array<string, mixed> $context Optional initial context data.
     * @return AgentResult The final result with output, metrics, and trace.
     */
    public function run(string $input, array $context = []): AgentResult;
}
