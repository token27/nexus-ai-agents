<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Execution;

use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\PlanStep;
use Token27\NexusAI\Workflows\Contract\WorkflowInterface;
use Token27\NexusAI\Workflows\Engine\WorkflowBuilder;

/**
 * Maps agent PlanSteps to executable Workflow graphs.
 *
 * This is the bridge between the agent's Plan (a flat list of steps)
 * and the workflow engine (a directed graph of nodes). Each PlanStep
 * is converted into a single-node workflow that can be executed by
 * WorkflowRunner.
 *
 * The mapping rules:
 * - 'think' action → AINode with reasoning prompt
 * - 'act' action → AINode with execution prompt (+ tools if available)
 * - 'observe' action → AINode with evaluation prompt
 * - 'output' action → AINode with output synthesis prompt
 *
 * @see \Token27\NexusAI\Agents\Execution\WorkflowExecutor
 * @see \Token27\NexusAI\Agents\Planning\PlanStep
 */
final class StepMapper
{
    /**
     * @param string $provider Default provider key for AINodes.
     * @param string $model Default model for AINodes.
     */
    public function __construct(
        private readonly string $provider = 'openai',
        private readonly string $model = 'gpt-4o',
    ) {
    }

    /**
     * Converts a PlanStep into a Workflow for execution.
     *
     * The resulting workflow is a single-node graph (the simplest workflow)
     * that encapsulates the step's intent as an AINode prompt.
     *
     * @param PlanStep $step The plan step to convert.
     * @param AgentContext $context The current agent context.
     * @return WorkflowInterface The executable workflow.
     */
    public function toWorkflow(PlanStep $step, AgentContext $context): WorkflowInterface
    {
        $prompt = $this->buildPrompt($step, $context);
        $workflowName = sprintf('agent_step_%s_%d', $step->action, $context->getStepCount() + 1);

        $builder = WorkflowBuilder::named($workflowName, sprintf('Executes agent step: %s', $step->description));

        $builder->addAINode(
            name: 'execute',
            provider: $this->provider,
            model: $this->model,
            prompt: $prompt,
            systemPrompt: $this->getSystemPromptForAction($step->action),
            outputKey: '_step_output',
        );

        return $builder->build();
    }

    /**
     * Builds the prompt for the AINode from the PlanStep and context.
     */
    private function buildPrompt(PlanStep $step, AgentContext $context): string
    {
        $parts = [];

        // Include the original user input
        $input = $context->get('_input', '');
        if ($input !== '' && $input !== '0') {
            $parts[] = sprintf('Original task: %s', $input);
        }

        // Include memory context if available
        $memoryContext = $context->get('_memory_context', '');
        if ($memoryContext !== '' && $memoryContext !== '0') {
            $parts[] = sprintf("Context:\n%s", $memoryContext);
        }

        // Include the last output if available (chain of steps)
        $lastOutput = $context->get('_last_output', '');
        if ($lastOutput !== '' && $lastOutput !== '0') {
            $parts[] = sprintf("Previous step result:\n%s", $lastOutput);
        }

        // The step itself
        $parts[] = sprintf('Current step: %s', $step->description);

        if ($step->expectedOutput !== '' && $step->expectedOutput !== '0') {
            $parts[] = sprintf('Expected output: %s', $step->expectedOutput);
        }

        // Include step arguments if any
        if ($step->arguments !== []) {
            $parts[] = sprintf('Arguments: %s', json_encode($step->arguments, JSON_UNESCAPED_UNICODE));
        }

        return implode("\n\n", $parts);
    }

    /**
     * Returns an action-specific system prompt for the AINode.
     */
    private function getSystemPromptForAction(string $action): string
    {
        return match ($action) {
            'think' => 'You are a reasoning assistant. Analyze the problem carefully, break it down, and provide clear reasoning. Do not take actions, only think.',
            'act' => 'You are an execution assistant. Perform the requested action and provide the result. Be precise and thorough.',
            'observe' => 'You are an evaluation assistant. Analyze the result of the previous action. Determine if it was successful and what should be done next.',
            'output' => 'You are a synthesis assistant. Compile all previous results into a clear, well-structured final answer.',
            default => 'You are an AI assistant. Complete the requested task thoroughly and accurately.',
        };
    }
}
