<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Execution;

use Token27\NexusAI\Agents\Contract\ExecutorInterface;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\PlanStep;

/**
 * Simple executor that passes the step description as output.
 *
 * Does NOT call the LLM or WorkflowRunner. Useful for:
 * - Testing: verify agent loop behavior without LLM calls
 * - OrchestratorAgent: where sub-agents handle execution
 * - Debugging: trace step flow without execution overhead
 *
 * @see \Token27\NexusAI\Agents\Contract\ExecutorInterface
 * @see \Token27\NexusAI\Agents\MultiAgent\OrchestratorAgent
 */
final class PassthroughExecutor implements ExecutorInterface
{
    /**
     * Returns the step description as the output without executing anything.
     *
     * @param PlanStep $step The step to "execute".
     * @param AgentContext $context The current agent context.
     * @return AgentContext Updated context with step description as output.
     */
    public function execute(PlanStep $step, AgentContext $context): AgentContext
    {
        return $context->with('_last_output', $step->description);
    }
}
