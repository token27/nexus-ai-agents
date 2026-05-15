<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Enum;

/**
 * Status of an agent during execution.
 *
 * Tracks the lifecycle from idle → running through the planning/execution/
 * reflection loop, with possible suspension for human-in-the-loop approval.
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 * @see \Token27\NexusAI\Agents\ToolIntegration\HumanInTheLoop
 */
enum AgentStatus: string
{
    /** Agent has not started execution yet. */
    case Idle = 'idle';

    /** Agent is currently planning the next steps. */
    case Planning = 'planning';

    /** Agent is executing a single step. */
    case Executing = 'executing';

    /** Agent is reflecting on the result of a completed step. */
    case Reflecting = 'reflecting';

    /** Agent is waiting for external input (human-in-the-loop). */
    case WaitingForApproval = 'waiting_for_approval';

    /** Agent completed successfully. */
    case Completed = 'completed';

    /** Agent failed with an error. */
    case Failed = 'failed';
}
