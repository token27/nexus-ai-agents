<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Contract;

use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\Plan;

/**
 * Generates an execution plan from user input and current context.
 *
 * This is the first step of the Plan → Execute → Reflect cycle.
 * The planner decomposes the user's task into concrete steps that
 * the executor can carry out.
 *
 * @see \Token27\NexusAI\Agents\Planning\Plan
 * @see \Token27\NexusAI\Agents\Planning\PlannerFactory
 */
interface PlannerInterface
{
    /**
     * Generates a plan to accomplish the given task.
     *
     * @param string $input The user's input/request.
     * @param AgentContext $context The current agent context (may contain memory, history, etc.).
     * @return Plan The generated execution plan with steps, goal, and reasoning.
     */
    public function plan(string $input, AgentContext $context): Plan;
}
