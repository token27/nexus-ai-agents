<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Contract;

use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Reflection\ReflectionResult;

/**
 * Evaluates a step result and decides the next action.
 *
 * The reflector is the "critic" in the agent loop. After a step
 * executes, the reflector assesses the output and determines
 * whether to continue, replan, retry, finish, ask the user,
 * or abort.
 *
 * @see \Token27\NexusAI\Agents\Enum\ReflectionAction
 * @see \Token27\NexusAI\Agents\Reflection\ReflectionResult
 */
interface ReflectorInterface
{
    /**
     * Evaluates the result of a completed step.
     *
     * @param AgentContext $context The current agent context with step results.
     * @param string $stepOutput The text output from the last executed step.
     * @return ReflectionResult The reflection decision with action and reasoning.
     */
    public function reflect(AgentContext $context, string $stepOutput): ReflectionResult;
}
