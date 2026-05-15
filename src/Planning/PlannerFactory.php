<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Planning;

use Token27\NexusAI\Agents\Contract\PlannerInterface;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;
use Token27\NexusAI\Contract\DriverInterface;

/**
 * Factory that creates PlannerInterface implementations from strategy enums.
 *
 * Maps each PlanningStrategy case to its concrete planner class.
 * When a DriverInterface is provided, planners will call the LLM for
 * dynamic plan generation. Without it, they fall back to static plans.
 *
 * @see \Token27\NexusAI\Agents\Enum\PlanningStrategy
 * @see \Token27\NexusAI\Agents\Contract\PlannerInterface
 */
final class PlannerFactory
{
    /**
     * Creates a planner for the given strategy.
     *
     * @param PlanningStrategy $strategy The planning strategy.
     * @param DriverInterface|null $driver Optional LLM driver for dynamic planning.
     * @param string $provider Provider key for LLM calls.
     * @param string $model Model for LLM calls.
     * @return PlannerInterface The corresponding planner instance.
     */
    public static function create(
        PlanningStrategy $strategy,
        ?DriverInterface $driver = null,
        string $provider = 'openai',
        string $model = 'gpt-4o',
    ): PlannerInterface {
        return match ($strategy) {
            PlanningStrategy::ReAct => new ReActPlanner($driver, $provider, $model),
            PlanningStrategy::ChainOfThought => new ChainOfThoughtPlanner($driver, $provider, $model),
            PlanningStrategy::PlanAndExecute => new PlanAndExecutePlanner($driver, $provider, $model),
        };
    }
}
