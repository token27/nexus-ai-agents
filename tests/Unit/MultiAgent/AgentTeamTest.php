<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\MultiAgent;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Contract\AgentInterface;
use Token27\NexusAI\Agents\Core\AgentResult;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\MultiAgent\AgentTeam;

final class AgentTeamTest extends TestCase
{
    public function test_add_member(): void
    {
        $team = new AgentTeam();
        $agent = $this->createMock(AgentInterface::class);

        $team->addMember($agent);

        $this->assertCount(1, $team->getMembers());
    }

    public function test_get_bus(): void
    {
        $team = new AgentTeam();

        $bus = $team->getBus();

        $this->assertNotNull($bus);
    }

    public function test_run_all(): void
    {
        $agent1 = $this->createMock(AgentInterface::class);
        $agent1->method('getName')->willReturn('agent1');
        $agent1->method('run')->willReturn(new AgentResult(status: AgentStatus::Completed, output: 'Result 1'));

        $agent2 = $this->createMock(AgentInterface::class);
        $agent2->method('getName')->willReturn('agent2');
        $agent2->method('run')->willReturn(new AgentResult(status: AgentStatus::Completed, output: 'Result 2'));

        $team = new AgentTeam();
        $team->addMember($agent1);
        $team->addMember($agent2);

        $results = $team->runAll('Test task');

        $this->assertCount(2, $results);
        $this->assertSame('Result 1', $results['agent1']->output);
        $this->assertSame('Result 2', $results['agent2']->output);
    }
}
