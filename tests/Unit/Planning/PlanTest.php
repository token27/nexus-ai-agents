<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Planning;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Planning\Plan;
use Token27\NexusAI\Agents\Planning\PlanStep;

final class PlanTest extends TestCase
{
    public function test_creation(): void
    {
        $steps = [
            new PlanStep(description: 'Step 1'),
            new PlanStep(description: 'Step 2', action: 'act'),
        ];

        $plan = new Plan(steps: $steps, goal: 'Complete task', reasoning: 'Two steps needed');

        $this->assertCount(2, $plan->steps);
        $this->assertSame('Complete task', $plan->goal);
        $this->assertSame('Two steps needed', $plan->reasoning);
    }

    public function test_defaults(): void
    {
        $plan = new Plan(steps: []);

        $this->assertSame([], $plan->steps);
        $this->assertSame('', $plan->goal);
        $this->assertSame('', $plan->reasoning);
    }

    public function test_to_array(): void
    {
        $plan = new Plan(
            steps: [new PlanStep(description: 'Test')],
            goal: 'Goal',
            reasoning: 'Because',
        );

        $array = $plan->toArray();

        $this->assertCount(1, $array['steps']);
        $this->assertSame('Goal', $array['goal']);
        $this->assertSame('Because', $array['reasoning']);
    }
}
