<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\Planning\Plan;
use Token27\NexusAI\Agents\Planning\PlanStep;

final class AgentContextTest extends TestCase
{
    public function test_empty_context(): void
    {
        $ctx = AgentContext::empty();

        $this->assertSame(AgentStatus::Idle, $ctx->getStatus());
        $this->assertSame(0, $ctx->getStepCount());
        $this->assertSame(0.0, $ctx->getTotalCost());
        $this->assertNull($ctx->getPlan());
        $this->assertSame([], $ctx->all());
    }

    public function test_with_adds_key(): void
    {
        $ctx = AgentContext::empty()->with('key', 'value');

        $this->assertSame('value', $ctx->get('key'));
        $this->assertTrue($ctx->has('key'));
    }

    public function test_immutability(): void
    {
        $original = AgentContext::empty();
        $modified = $original->with('key', 'value');

        $this->assertFalse($original->has('key'));
        $this->assertTrue($modified->has('key'));
    }

    public function test_with_status(): void
    {
        $ctx = AgentContext::empty()->withStatus(AgentStatus::Executing);

        $this->assertSame(AgentStatus::Executing, $ctx->getStatus());
    }

    public function test_increment_step(): void
    {
        $ctx = AgentContext::empty();
        $ctx = $ctx->incrementStep();
        $ctx = $ctx->incrementStep();

        $this->assertSame(2, $ctx->getStepCount());
    }

    public function test_with_cost(): void
    {
        $ctx = AgentContext::empty();
        $ctx = $ctx->withCost(0.05);
        $ctx = $ctx->withCost(0.03);

        $this->assertSame(0.08, $ctx->getTotalCost());
    }

    public function test_with_plan(): void
    {
        $plan = new Plan(
            steps: [new PlanStep(description: 'Test step')],
            goal: 'Test goal',
        );

        $ctx = AgentContext::empty()->withPlan($plan);

        $this->assertSame($plan, $ctx->getPlan());
    }

    public function test_get_with_default(): void
    {
        $ctx = AgentContext::empty();

        $this->assertSame('default', $ctx->get('missing', 'default'));
        $this->assertNull($ctx->get('missing'));
    }

    public function test_elapsed_time(): void
    {
        $ctx = AgentContext::empty();
        usleep(100); // 0.1ms to guarantee elapsed > 0
        $elapsed = $ctx->getElapsedMs();

        $this->assertGreaterThan(0.0, $elapsed);
    }

    public function test_constructor_with_data(): void
    {
        $ctx = new AgentContext(data: ['input' => 'hello'], status: AgentStatus::Planning);

        $this->assertSame('hello', $ctx->get('input'));
        $this->assertSame(AgentStatus::Planning, $ctx->getStatus());
    }
}
