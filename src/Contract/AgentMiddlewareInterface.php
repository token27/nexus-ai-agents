<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Contract;

use Token27\NexusAI\Agents\Core\AgentContext;

/**
 * Intercepts the agent execution loop for cross-cutting concerns.
 *
 * Follows the same onion middleware pattern as nexus-ai's
 * MiddlewareInterface. Each middleware can execute logic before,
 * after, or instead of passing to the next handler.
 *
 * Use cases: tool approval, memory injection, budget guard,
 * planning strategy injection.
 *
 * @see \Token27\NexusAI\Agents\Contract\AgentInterface
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
interface AgentMiddlewareInterface
{
    /**
     * Processes the agent context through this middleware.
     *
     * @param AgentContext $context The current agent context.
     * @param callable(AgentContext): AgentContext $next The next middleware or final handler.
     * @return AgentContext The processed context.
     */
    public function process(AgentContext $context, callable $next): AgentContext;
}
