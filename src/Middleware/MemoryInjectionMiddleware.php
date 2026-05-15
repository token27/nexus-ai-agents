<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Middleware;

use Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Memory\MemoryManager;

/**
 * Middleware that injects memory context into the agent's prompt.
 *
 * Before each step, builds a context string from working memory
 * and recent episodic memory, and injects it into the context
 * so planners and executors can use it.
 *
 * @see \Token27\NexusAI\Agents\Memory\MemoryManager
 * @see \Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface
 */
final readonly class MemoryInjectionMiddleware implements AgentMiddlewareInterface
{
    /**
     * @param MemoryManager $memory The memory manager to source context from.
     */
    public function __construct(
        private MemoryManager $memory,
    ) {
    }

    public function process(AgentContext $context, callable $next): AgentContext
    {
        // Build and inject memory context
        $memoryContext = $this->memory->buildContext();
        $context = $context->with('_memory_context', $memoryContext);

        // Execute the next handler
        $result = $next($context);

        // Trim working memory after execution to stay within budget
        $this->memory->trimWorking();

        return $result;
    }
}
