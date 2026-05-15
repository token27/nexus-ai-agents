<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\MultiAgent;

use Token27\NexusAI\Agents\Contract\AgentInterface;
use Token27\NexusAI\Agents\Core\AgentResult;

/**
 * Transfers control from one agent to another.
 *
 * When an agent detects it needs a specialist, it can hand off
 * the task with the current context. The receiving agent picks
 * up where the first left off.
 *
 * @see \Token27\NexusAI\Agents\Contract\AgentInterface
 * @see \Token27\NexusAI\Agents\MultiAgent\AgentTeam
 */
final readonly class Handoff
{
    /**
     * Transfers execution from one agent to another.
     *
     * @param AgentInterface $from The source agent.
     * @param AgentInterface $to The target agent.
     * @param string $context The task context to transfer.
     * @return AgentResult The result from the target agent.
     */
    public function transfer(AgentInterface $from, AgentInterface $to, string $context): AgentResult
    {
        return $to->run($context, [
            'handoff_from' => $from->getName(),
            'handoff_description' => $from->getDescription(),
        ]);
    }
}
