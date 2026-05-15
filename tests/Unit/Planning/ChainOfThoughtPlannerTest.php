<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Planning;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\ChainOfThoughtPlanner;

final class ChainOfThoughtPlannerTest extends TestCase
{
    private ChainOfThoughtPlanner $planner;

    protected function setUp(): void
    {
        $this->planner = new ChainOfThoughtPlanner();
    }

    public function test_get_system_prompt(): void
    {
        $prompt = $this->planner->getSystemPrompt();

        $this->assertStringContainsString('Chain-of-Thought', $prompt);
        $this->assertStringContainsString('step by step', $prompt);
    }

    public function test_format_input(): void
    {
        $ctx = AgentContext::empty();
        $formatted = $this->planner->formatInput('Solve math', $ctx);

        $this->assertStringContainsString('Solve math', $formatted);
        $this->assertStringContainsString('Think through this step by step', $formatted);
    }

    public function test_parse_response_with_conclusion(): void
    {
        $response = "Step 1: Identify the problem.\nStep 2: Break it down.\nStep 3: Solve each part.\nConclusion: The answer is 42.";

        $parsed = $this->planner->parseResponse($response);

        $this->assertCount(3, $parsed['steps']);
        $this->assertStringContainsString('42', $parsed['conclusion']);
    }

    public function test_plan_creates_four_steps(): void
    {
        $ctx = AgentContext::empty();
        $plan = $this->planner->plan('Complex problem', $ctx);

        $this->assertCount(4, $plan->steps);
        $this->assertSame('think', $plan->steps[0]->action);
        $this->assertSame('output', $plan->steps[3]->action);
    }
}
