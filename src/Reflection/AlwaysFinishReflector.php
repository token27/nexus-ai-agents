<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Reflection;

use Token27\NexusAI\Agents\Contract\ReflectorInterface;
use Token27\NexusAI\Agents\Core\AgentContext;

/**
 * Simple reflector that finishes on the last step, continues otherwise.
 *
 * Does NOT call the LLM. Useful for:
 * - Testing: deterministic reflection behavior
 * - OrchestratorAgent: where all steps must execute sequentially
 * - Predictable pipelines: no re-planning or retries needed
 *
 * @see \Token27\NexusAI\Agents\Contract\ReflectorInterface
 * @see \Token27\NexusAI\Agents\MultiAgent\OrchestratorAgent
 */
final class AlwaysFinishReflector implements ReflectorInterface
{
    /**
     * Returns Finish on the last step, Continue otherwise.
     *
     * @param AgentContext $context The current agent context.
     * @param string $stepOutput The output from the last step (unused).
     * @return ReflectionResult Continue or Finish based on step position.
     */
    public function reflect(AgentContext $context, string $stepOutput): ReflectionResult
    {
        $plan = $context->getPlan();

        // If there's a plan, finish when we've executed all steps
        if ($plan !== null) {
            $isLastStep = $context->getStepCount() >= count($plan->steps);

            return $isLastStep
                ? ReflectionResult::finish('All plan steps have been executed.')
                : ReflectionResult::continue('More steps remain to execute.');
        }

        // No plan: always finish (single-step execution)
        return ReflectionResult::finish('No plan; finishing after single step.');
    }
}
