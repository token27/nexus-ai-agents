<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentResult;
use Token27\NexusAI\Agents\Enum\AgentStatus;

final class AgentResultTest extends TestCase
{
    public function test_is_success_when_completed(): void
    {
        $result = new AgentResult(status: AgentStatus::Completed);

        $this->assertTrue($result->isSuccess());
        $this->assertFalse($result->isFailed());
    }

    public function test_is_failed_when_failed(): void
    {
        $result = new AgentResult(status: AgentStatus::Failed);

        $this->assertTrue($result->isFailed());
        $this->assertFalse($result->isSuccess());
    }

    public function test_full_result(): void
    {
        $result = new AgentResult(
            status: AgentStatus::Completed,
            output: 'Final answer',
            totalSteps: 5,
            elapsedMs: 1500.0,
            totalCost: 0.25,
            trace: [['phase' => 'planning']],
            metadata: ['key' => 'value'],
        );

        $this->assertSame(AgentStatus::Completed, $result->status);
        $this->assertSame('Final answer', $result->output);
        $this->assertSame(5, $result->totalSteps);
        $this->assertSame(1500.0, $result->elapsedMs);
        $this->assertSame(0.25, $result->totalCost);
        $this->assertCount(1, $result->trace);
        $this->assertSame('value', $result->metadata['key']);
    }

    public function test_defaults(): void
    {
        $result = new AgentResult(status: AgentStatus::Failed);

        $this->assertSame('', $result->output);
        $this->assertSame(0, $result->totalSteps);
        $this->assertSame(0.0, $result->elapsedMs);
        $this->assertSame(0.0, $result->totalCost);
        $this->assertSame([], $result->trace);
        $this->assertSame([], $result->metadata);
    }
}
