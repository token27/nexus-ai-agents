<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Observability;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Observability\Event\AgentCompleted;
use Token27\NexusAI\Agents\Observability\Event\AgentFailed;
use Token27\NexusAI\Agents\Observability\Event\AgentReflection;
use Token27\NexusAI\Agents\Observability\Event\AgentStarted;
use Token27\NexusAI\Agents\Observability\Event\AgentStep;
use Token27\NexusAI\Agents\Observability\Event\AgentToolCall;

final class EventTest extends TestCase
{
    public function test_agent_started(): void
    {
        $event = new AgentStarted(agentName: 'test-agent', input: 'Hello', timestamp: 1234567890.0);

        $this->assertSame('test-agent', $event->agentName);
        $this->assertSame('Hello', $event->input);
        $this->assertSame(1234567890.0, $event->timestamp);
    }

    public function test_agent_step(): void
    {
        $event = new AgentStep(
            agentName: 'agent',
            stepNumber: 3,
            stepAction: 'act',
            stepOutput: 'Done',
            elapsedMs: 500.0,
        );

        $this->assertSame(3, $event->stepNumber);
        $this->assertSame('act', $event->stepAction);
        $this->assertSame('Done', $event->stepOutput);
    }

    public function test_agent_reflection(): void
    {
        $event = new AgentReflection(agentName: 'agent', action: 'finish', reasoning: 'Task done');

        $this->assertSame('finish', $event->action);
        $this->assertSame('Task done', $event->reasoning);
    }

    public function test_agent_tool_call(): void
    {
        $event = new AgentToolCall(agentName: 'agent', toolName: 'get_weather', arguments: ['city' => 'Paris']);

        $this->assertSame('get_weather', $event->toolName);
        $this->assertSame(['city' => 'Paris'], $event->arguments);
    }

    public function test_agent_completed(): void
    {
        $event = new AgentCompleted(
            agentName: 'agent',
            output: 'Final result',
            totalSteps: 5,
            elapsedMs: 1200.0,
            totalCost: 0.15,
        );

        $this->assertSame('Final result', $event->output);
        $this->assertSame(5, $event->totalSteps);
        $this->assertSame(0.15, $event->totalCost);
    }

    public function test_agent_failed(): void
    {
        $event = new AgentFailed(agentName: 'agent', error: 'Budget exceeded', totalSteps: 2, elapsedMs: 300.0);

        $this->assertSame('Budget exceeded', $event->error);
        $this->assertSame(2, $event->totalSteps);
    }
}
