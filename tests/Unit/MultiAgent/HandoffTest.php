<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\MultiAgent;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Contract\AgentInterface;
use Token27\NexusAI\Agents\Core\AgentResult;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\MultiAgent\Handoff;

final class HandoffTest extends TestCase
{
    public function test_transfer(): void
    {
        $from = $this->createMock(AgentInterface::class);
        $from->method('getName')->willReturn('source-agent');
        $from->method('getDescription')->willReturn('Source');

        $expectedResult = new AgentResult(
            status: AgentStatus::Completed,
            output: 'Done',
            totalSteps: 1,
            elapsedMs: 0.0,
            totalCost: 0.0,
        );

        $to = $this->createMock(AgentInterface::class);
        $to->expects($this->once())->method('run')->with(
            'Transfer context',
            $this->callback(function (array $ctx): bool {
                return $ctx['handoff_from'] === 'source-agent';
            }),
        )->willReturn($expectedResult);

        $handoff = new Handoff();
        $result = $handoff->transfer($from, $to, 'Transfer context');

        $this->assertTrue($result->isSuccess());
    }
}
