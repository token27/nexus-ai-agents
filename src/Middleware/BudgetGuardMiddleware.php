<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Middleware;

use Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface;
use Token27\NexusAI\Agents\Core\AgentConfig;
use Token27\NexusAI\Agents\Core\AgentContext;

/**
 * Middleware that guards against budget overruns.
 *
 * Checks the total cost before each step and prevents execution
 * if the budget has been exceeded. This is a safety net on top
 * of the budget check in AbstractAgent::run().
 *
 * @see \Token27\NexusAI\Agents\Core\AgentConfig::$budget
 * @see \Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface
 */
final readonly class BudgetGuardMiddleware implements AgentMiddlewareInterface
{
    /**
     * @param AgentConfig $config The agent configuration with budget limit.
     */
    public function __construct(
        private AgentConfig $config,
    ) {
    }

    public function process(AgentContext $context, callable $next): AgentContext
    {
        if ($context->getTotalCost() > $this->config->budget) {
            return $context->with(
                '_last_output',
                sprintf('Budget exceeded: $%.4f / $%.2f', $context->getTotalCost(), $this->config->budget),
            );
        }

        return $next($context);
    }
}
