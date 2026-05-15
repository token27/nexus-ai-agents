<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Enum;

/**
 * Planning strategy used by the agent.
 *
 * Each strategy defines how the agent approaches problem-solving:
 * - ReAct: Interleaved reasoning and acting (Thought → Action → Observation)
 * - ChainOfThought: Step-by-step reasoning before producing output
 * - PlanAndExecute: Full plan first, then execute sequentially
 *
 * @see \Token27\NexusAI\Agents\Contract\PlannerInterface
 * @see \Token27\NexusAI\Agents\Planning\PlannerFactory
 */
enum PlanningStrategy: string
{
    /** Reasoning + Acting interleaved (Thought → Action → Observation). */
    case ReAct = 'react';

    /** Think step by step before answering. */
    case ChainOfThought = 'chain_of_thought';

    /** Plan complete first, then execute sequentially. */
    case PlanAndExecute = 'plan_and_execute';
}
