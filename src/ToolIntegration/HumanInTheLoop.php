<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\ToolIntegration;

use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Enum\AgentStatus;

/**
 * Human-in-the-loop mechanism for agent approval.
 *
 * Allows the agent to pause execution and wait for external input.
 * Used for sensitive tool calls, critical decisions, or when the
 * agent needs clarification from a human operator.
 *
 * @see \Token27\NexusAI\Agents\Enum\AgentStatus::WaitingForApproval
 * @see \Token27\NexusAI\Agents\ToolIntegration\ToolApprovalMiddleware
 */
final class HumanInTheLoop
{
    /**
     * Pauses the agent and requests human input.
     *
     * Sets the agent status to WaitingForApproval and stores
     * the question and available options in the context.
     *
     * @param AgentContext $context The current agent context.
     * @param string $question The question to present to the human.
     * @param array<string, string> $options Available response options (label → value).
     * @return AgentContext Updated context with approval request.
     */
    public function requestApproval(AgentContext $context, string $question, array $options = []): AgentContext
    {
        return $context
            ->withStatus(AgentStatus::WaitingForApproval)
            ->with('_human_question', $question)
            ->with('_human_options', $options);
    }

    /**
     * Resumes the agent with the human's response.
     *
     * Clears the approval state and stores the human's response
     * in the context for the agent to process.
     *
     * @param AgentContext $context The paused agent context.
     * @param string $humanResponse The human's response.
     * @return AgentContext Updated context ready for execution.
     */
    public function resume(AgentContext $context, string $humanResponse): AgentContext
    {
        return $context
            ->withStatus(AgentStatus::Executing)
            ->with('_human_response', $humanResponse)
            ->with('_human_question', null)
            ->with('_human_options', null);
    }

    /**
     * Checks if the agent is currently waiting for human input.
     *
     * @param AgentContext $context The agent context.
     * @return bool True if the agent is waiting.
     */
    public function isWaiting(AgentContext $context): bool
    {
        return $context->getStatus() === AgentStatus::WaitingForApproval;
    }
}
