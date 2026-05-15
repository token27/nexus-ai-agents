<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Execution;

use Token27\NexusAI\Agents\Contract\ExecutorInterface;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\PlanStep;
use Token27\NexusAI\Workflows\Contract\WorkflowRunnerInterface;
use Token27\NexusAI\Workflows\Engine\WorkflowContext;

/**
 * Executes plan steps using WorkflowRunner from nexus-ai-workflows.
 *
 * This is the key integration point between the agent layer and the
 * workflow engine. For each PlanStep:
 *
 * 1. StepMapper converts the PlanStep into a Workflow (graph of nodes)
 * 2. WorkflowRunner executes the workflow
 * 3. The output is extracted from WorkflowResult and merged into AgentContext
 *
 * This gives agents all workflow features for free: middleware pipeline,
 * persistence/resume, observability events, guards, and AI/Tool nodes.
 *
 * @see \Token27\NexusAI\Agents\Execution\StepMapper
 * @see \Token27\NexusAI\Workflows\Contract\WorkflowRunnerInterface
 */
final class WorkflowExecutor implements ExecutorInterface
{
    /**
     * @param WorkflowRunnerInterface $runner The workflow execution engine.
     * @param StepMapper $mapper Converts plan steps to workflows.
     */
    public function __construct(
        private readonly WorkflowRunnerInterface $runner,
        private readonly StepMapper $mapper,
    ) {
    }

    /**
     * Executes a plan step by converting it to a workflow and running it.
     *
     * Flow:
     * 1. PlanStep → Workflow (via StepMapper)
     * 2. AgentContext → WorkflowContext (data transfer)
     * 3. WorkflowRunner::run(workflow, context) → WorkflowResult
     * 4. Extract output and elapsed time → updated AgentContext
     *
     * @param PlanStep $step The step to execute.
     * @param AgentContext $context The current agent context.
     * @return AgentContext Updated context with step output and cost.
     */
    public function execute(PlanStep $step, AgentContext $context): AgentContext
    {
        // 1. Convert PlanStep → Workflow
        $workflow = $this->mapper->toWorkflow($step, $context);

        // 2. Convert AgentContext → WorkflowContext
        $wfContext = WorkflowContext::from($context->all());

        // 3. Execute workflow
        $result = $this->runner->run($workflow, $wfContext);

        // 4. Extract output from workflow result
        $output = $this->extractOutput($result->output);

        // 5. Update agent context with results
        return $context
            ->with('_last_output', $output)
            ->with('_last_workflow_result', [
                'steps' => count($result->steps),
                'elapsedMs' => $result->elapsedMs,
                'state' => $result->state->status->value,
            ]);
    }

    /**
     * Extracts the meaningful output from a workflow result.
     *
     * Looks for the output in priority order:
     * 1. '_step_output' key (set by AINode via outputKey)
     * 2. 'output' key (default AINode outputKey)
     * 3. Last non-internal key in output array
     * 4. Empty string fallback
     *
     * @param array<string, mixed> $output The workflow result output data.
     * @return string The extracted output text.
     */
    private function extractOutput(array $output): string
    {
        // Priority 1: explicit step output key
        if (isset($output['_step_output']) && is_string($output['_step_output'])) {
            return $output['_step_output'];
        }

        // Priority 2: default output key
        if (isset($output['output']) && is_string($output['output'])) {
            return $output['output'];
        }

        // Priority 3: last non-internal value
        foreach (array_reverse($output, true) as $key => $value) {
            if (!str_starts_with($key, '_') && is_string($value)) {
                return $value;
            }
        }

        return '';
    }
}
