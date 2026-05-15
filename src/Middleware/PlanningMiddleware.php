<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Middleware;

use Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface;
use Token27\NexusAI\Agents\Contract\PlanningStrategyInterface;
use Token27\NexusAI\Agents\Core\AgentContext;

/**
 * Middleware that injects the planning strategy's system prompt
 * and formats the input before each step execution.
 *
 * @see \Token27\NexusAI\Agents\Contract\PlanningStrategyInterface
 * @see \Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface
 */
final readonly class PlanningMiddleware implements AgentMiddlewareInterface
{
    /**
     * @param PlanningStrategyInterface $strategy The planning strategy.
     */
    public function __construct(
        private PlanningStrategyInterface $strategy,
    ) {
    }

    public function process(AgentContext $context, callable $next): AgentContext
    {
        $input = $context->get('_input', '');

        // Format the input using the planning strategy
        $formattedInput = $this->strategy->formatInput($input, $context);
        $systemPrompt = $this->strategy->getSystemPrompt();

        // Inject into context for the executor
        $context = $context
            ->with('_formatted_input', $formattedInput)
            ->with('_system_prompt', $systemPrompt);

        return $next($context);
    }
}
