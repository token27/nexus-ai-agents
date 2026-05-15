<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Planning;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Planning\ReActPlanner;

final class ReActPlannerTest extends TestCase
{
    private ReActPlanner $planner;

    protected function setUp(): void
    {
        $this->planner = new ReActPlanner();
    }

    public function test_get_system_prompt(): void
    {
        $prompt = $this->planner->getSystemPrompt();

        $this->assertStringContainsString('ReAct agent', $prompt);
        $this->assertStringContainsString('Thought:', $prompt);
        $this->assertStringContainsString('Action:', $prompt);
        $this->assertStringContainsString('Observation:', $prompt);
        $this->assertStringContainsString('Final Answer:', $prompt);
    }

    public function test_format_input(): void
    {
        $ctx = AgentContext::empty();
        $formatted = $this->planner->formatInput('Do something', $ctx);

        $this->assertStringContainsString('Do something', $formatted);
    }

    public function test_format_input_with_memory_context(): void
    {
        $ctx = AgentContext::empty()->with('_memory_context', 'Previous result');
        $formatted = $this->planner->formatInput('Do something', $ctx);

        $this->assertStringContainsString('Context:', $formatted);
        $this->assertStringContainsString('Previous result', $formatted);
    }

    public function test_parse_response(): void
    {
        $response = <<<'TEXT'
Thought: I need to check the weather
Action: get_weather(location="Paris")
Observation: It's sunny, 22°C
Final Answer: The weather in Paris is sunny and 22°C.
TEXT;

        $parsed = $this->planner->parseResponse($response);

        $this->assertCount(1, $parsed['thoughts']);
        $this->assertStringContainsString('weather', $parsed['thoughts'][0]);
        $this->assertCount(1, $parsed['actions']);
        $this->assertStringContainsString('get_weather', $parsed['actions'][0]);
        $this->assertCount(1, $parsed['observations']);
        $this->assertStringContainsString('sunny', $parsed['observations'][0]);
        $this->assertStringContainsString('Paris', $parsed['finalAnswer']);
    }

    public function test_plan_creates_four_steps(): void
    {
        $ctx = AgentContext::empty();
        $plan = $this->planner->plan('Test task', $ctx);

        $this->assertCount(4, $plan->steps);
        $this->assertSame('think', $plan->steps[0]->action);
        $this->assertSame('act', $plan->steps[1]->action);
        $this->assertSame('observe', $plan->steps[2]->action);
        $this->assertSame('output', $plan->steps[3]->action);
        $this->assertSame('Test task', $plan->goal);
    }
}
