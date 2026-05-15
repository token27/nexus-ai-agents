<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Planning;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\PlanAndExecutePlanner;

final class PlanAndExecutePlannerTest extends TestCase
{
    private PlanAndExecutePlanner $planner;

    protected function setUp(): void
    {
        $this->planner = new PlanAndExecutePlanner();
    }

    public function test_get_system_prompt(): void
    {
        $prompt = $this->planner->getSystemPrompt();

        $this->assertStringContainsString('Plan-and-Execute', $prompt);
        $this->assertStringContainsString('PLAN:', $prompt);
        $this->assertStringContainsString('EXECUTE:', $prompt);
        $this->assertStringContainsString('VERIFY:', $prompt);
        $this->assertStringContainsString('FINALIZE:', $prompt);
    }

    public function test_format_input_without_plan(): void
    {
        $ctx = AgentContext::empty();
        $formatted = $this->planner->formatInput('Build a house', $ctx);

        $this->assertStringContainsString('Create a plan for:', $formatted);
        $this->assertStringContainsString('Build a house', $formatted);
    }

    public function test_parse_response_with_plan(): void
    {
        $response = "PLAN:\n1. Get materials\n2. Build foundation\n3. Construct walls\nFINALIZE: House complete.";

        $parsed = $this->planner->parseResponse($response);

        $this->assertCount(3, $parsed['plan']);
        $this->assertStringContainsString('House complete', $parsed['finalOutput']);
    }

    public function test_plan_creates_four_steps(): void
    {
        $ctx = AgentContext::empty();
        $plan = $this->planner->plan('Project', $ctx);

        $this->assertCount(4, $plan->steps);
        $this->assertSame('think', $plan->steps[0]->action);
        $this->assertSame('output', $plan->steps[3]->action);
    }
}
