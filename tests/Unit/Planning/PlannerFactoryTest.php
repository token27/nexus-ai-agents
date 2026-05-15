<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Planning;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;
use Token27\NexusAI\Agents\Planning\ChainOfThoughtPlanner;
use Token27\NexusAI\Agents\Planning\PlanAndExecutePlanner;
use Token27\NexusAI\Agents\Planning\PlannerFactory;
use Token27\NexusAI\Agents\Planning\ReActPlanner;

final class PlannerFactoryTest extends TestCase
{
    public function test_create_react(): void
    {
        $planner = PlannerFactory::create(PlanningStrategy::ReAct);

        $this->assertInstanceOf(ReActPlanner::class, $planner);
    }

    public function test_create_chain_of_thought(): void
    {
        $planner = PlannerFactory::create(PlanningStrategy::ChainOfThought);

        $this->assertInstanceOf(ChainOfThoughtPlanner::class, $planner);
    }

    public function test_create_plan_and_execute(): void
    {
        $planner = PlannerFactory::create(PlanningStrategy::PlanAndExecute);

        $this->assertInstanceOf(PlanAndExecutePlanner::class, $planner);
    }

    public function test_all_strategies_covered(): void
    {
        foreach (PlanningStrategy::cases() as $strategy) {
            $planner = PlannerFactory::create($strategy);
            $this->assertNotNull($planner);
        }
    }
}
