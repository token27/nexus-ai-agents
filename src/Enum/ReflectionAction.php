<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Enum;

/**
 * Actions the reflector can decide after evaluating a step result.
 *
 * Maps directly to the agent loop control flow:
 * Continue → next step, Finish → produce output,
 * Replan → regenerate plan, Abort → stop with error.
 *
 * @see \Token27\NexusAI\Agents\Contract\ReflectorInterface
 * @see \Token27\NexusAI\Agents\Reflection\ReflectionResult
 */
enum ReflectionAction: string
{
    /** Proceed to the next step in the plan. */
    case Continue = 'continue';

    /** Current plan is invalid; generate a new one. */
    case Replan = 'replan';

    /** Retry the current step, possibly with more context. */
    case Retry = 'retry';

    /** Task completed successfully; produce final output. */
    case Finish = 'finish';

    /** Request human input before continuing. */
    case AskUser = 'ask_user';

    /** Task is impossible; abort with error. */
    case Abort = 'abort';
}
