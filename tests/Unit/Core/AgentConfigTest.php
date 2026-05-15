<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentConfig;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;

final class AgentConfigTest extends TestCase
{
    public function test_default_values(): void
    {
        $config = new AgentConfig();

        $this->assertSame('openai', $config->provider);
        $this->assertSame('gpt-4o', $config->model);
        $this->assertSame(0.7, $config->temperature);
        $this->assertSame(4096, $config->maxTokens);
        $this->assertSame(10.0, $config->budget);
        $this->assertSame(20, $config->maxSteps);
        $this->assertSame(PlanningStrategy::ReAct, $config->planningStrategy);
        $this->assertSame([], $config->tools);
        $this->assertNull($config->systemPrompt);
    }

    public function test_custom_values(): void
    {
        $config = new AgentConfig(
            provider: 'anthropic',
            model: 'claude-sonnet-4-20250514',
            temperature: 0.3,
            maxTokens: 8192,
            budget: 25.0,
            maxSteps: 50,
            planningStrategy: PlanningStrategy::ChainOfThought,
            tools: [],
            systemPrompt: 'You are a helpful assistant.',
        );

        $this->assertSame('anthropic', $config->provider);
        $this->assertSame('claude-sonnet-4-20250514', $config->model);
        $this->assertSame(0.3, $config->temperature);
        $this->assertSame(8192, $config->maxTokens);
        $this->assertSame(25.0, $config->budget);
        $this->assertSame(50, $config->maxSteps);
        $this->assertSame(PlanningStrategy::ChainOfThought, $config->planningStrategy);
        $this->assertSame('You are a helpful assistant.', $config->systemPrompt);
    }

    public function test_all_planning_strategies(): void
    {
        foreach (PlanningStrategy::cases() as $strategy) {
            $config = new AgentConfig(planningStrategy: $strategy);
            $this->assertSame($strategy, $config->planningStrategy);
        }
    }
}
