<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Reflection;

use Token27\NexusAI\Agents\Contract\ReflectorInterface;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Enum\ReflectionAction;
use Token27\NexusAI\Contract\DriverInterface;
use Token27\NexusAI\Message\UserMessage;
use Token27\NexusAI\Request\TextRequest;

/**
 * LLM-based reflector that evaluates step results via AI reasoning.
 *
 * After each step executes, the LLMReflector asks the LLM to evaluate
 * the output and decide the next action: Continue, Finish, Replan,
 * Retry, AskUser, or Abort.
 *
 * The LLM response is parsed for action keywords. Falls back to
 * ReflectionResult::continue() if parsing fails.
 *
 * @see \Token27\NexusAI\Agents\Contract\ReflectorInterface
 * @see \Token27\NexusAI\Agents\Reflection\ReflectionResult
 */
final class LLMReflector implements ReflectorInterface
{
    /**
     * @param DriverInterface $driver LLM driver for reflection calls.
     * @param string $provider Provider key for LLM calls.
     * @param string $model Model for LLM calls.
     */
    public function __construct(
        private readonly DriverInterface $driver,
        private readonly string $provider = 'openai',
        private readonly string $model = 'gpt-4o',
    ) {
    }

    /**
     * Evaluates the step output by calling the LLM.
     *
     * @param AgentContext $context The current agent context.
     * @param string $stepOutput The output from the last step.
     * @return ReflectionResult The reflection decision.
     */
    public function reflect(AgentContext $context, string $stepOutput): ReflectionResult
    {
        try {
            $prompt = $this->buildReflectionPrompt($context, $stepOutput);

            $request = new TextRequest(
                provider: $this->provider,
                model: $this->model,
                messages: [new UserMessage($prompt)],
                systemPrompt: $this->getSystemPrompt(),
            );

            $response = $this->driver->text($request);

            return $this->parseReflection($response->text);
        } catch (\Throwable) {
            // If LLM fails, default to Continue to avoid halting the agent
            return ReflectionResult::continue('Reflection failed, continuing by default.');
        }
    }

    /**
     * Builds the reflection prompt with full context.
     */
    private function buildReflectionPrompt(AgentContext $context, string $stepOutput): string
    {
        $input = $context->get('_input', 'Unknown task');
        $plan = $context->getPlan();
        $stepCount = $context->getStepCount();
        $totalSteps = $plan !== null ? count($plan->steps) : 0;

        $parts = [
            sprintf('Original task: %s', $input),
            sprintf('Steps completed: %d of %d', $stepCount, $totalSteps),
            sprintf("Last step output:\n%s", $stepOutput),
        ];

        if ($plan !== null) {
            $parts[] = sprintf('Plan goal: %s', $plan->goal);
        }

        $parts[] = 'Based on the output above, decide: should I CONTINUE to the next step, FINISH (task is complete), REPLAN (approach needs changing), RETRY (step failed, try again), or ABORT (task is impossible)?';

        return implode("\n\n", $parts);
    }

    /**
     * Returns the system prompt for the reflection LLM call.
     */
    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a reflection agent that evaluates task progress.

Analyze the step output and decide the next action. Respond with EXACTLY ONE of these actions followed by your reasoning:

CONTINUE - The step succeeded and there are more steps to execute.
FINISH - The task is fully complete. The last output contains the final answer.
REPLAN - The current approach is not working. A new plan is needed.
RETRY - The step failed or produced poor results. Try the same step again.
ABORT - The task is impossible or unsafe to continue.

Format your response as:
Action: [ACTION]
Reasoning: [Your explanation]
PROMPT;
    }

    /**
     * Parses the LLM response into a ReflectionResult.
     *
     * Looks for action keywords in the response text.
     * Falls back to Continue if no clear action is found.
     */
    private function parseReflection(string $response): ReflectionResult
    {
        $upper = strtoupper($response);

        // Try to extract explicit "Action:" line
        $action = null;
        $reasoning = null;

        if (preg_match('/Action:\s*(CONTINUE|FINISH|REPLAN|RETRY|ABORT|ASK\s*USER)/i', $response, $actionMatch)) {
            $action = strtoupper(trim($actionMatch[1]));
        }

        if (preg_match('/Reasoning:\s*(.+?)$/is', $response, $reasonMatch)) {
            $reasoning = trim($reasonMatch[1]);
        }

        // Fallback: look for keywords anywhere in the response
        if ($action === null) {
            if (str_contains($upper, 'FINISH') || str_contains($upper, 'COMPLETE')) {
                $action = 'FINISH';
            } elseif (str_contains($upper, 'REPLAN')) {
                $action = 'REPLAN';
            } elseif (str_contains($upper, 'RETRY') || str_contains($upper, 'TRY AGAIN')) {
                $action = 'RETRY';
            } elseif (str_contains($upper, 'ABORT') || str_contains($upper, 'IMPOSSIBLE')) {
                $action = 'ABORT';
            } elseif (str_contains($upper, 'ASK USER') || str_contains($upper, 'ASK_USER')) {
                $action = 'ASK USER';
            } else {
                $action = 'CONTINUE';
            }
        }

        $reasoning ??= trim($response);

        return match ($action) {
            'FINISH' => ReflectionResult::finish($reasoning),
            'REPLAN' => ReflectionResult::replan($reasoning),
            'RETRY' => new ReflectionResult(
                action: ReflectionAction::Retry,
                reasoning: $reasoning,
            ),
            'ABORT' => ReflectionResult::abort($reasoning),
            'ASK USER' => new ReflectionResult(
                action: ReflectionAction::AskUser,
                reasoning: $reasoning,
            ),
            default => ReflectionResult::continue($reasoning),
        };
    }
}
