<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Contract;

use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\PlanStep;

/**
 * Executes a single step from the plan.
 *
 * Uses nexus-ai to call the LLM and execute tools. Returns an
 * updated AgentContext with the step's output and any side effects
 * (tool results, memory updates, cost accrual).
 *
 * @see \Token27\NexusAI\Agents\Planning\PlanStep
 * @see \Token27\NexusAI\Agents\Core\AgentContext
 */
interface ExecutorInterface
{
    /**
     * Executes a plan step and returns updated context.
     *
     * @param PlanStep $step The step to execute.
     * @param AgentContext $context The current agent context.
     * @return AgentContext Updated context with step output.
     */
    public function execute(PlanStep $step, AgentContext $context): AgentContext;
}
