<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\ToolIntegration;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\ToolIntegration\HumanInTheLoop;

final class HumanInTheLoopTest extends TestCase
{
    public function test_request_approval(): void
    {
        $hitl = new HumanInTheLoop();
        $ctx = AgentContext::empty();

        $ctx = $hitl->requestApproval($ctx, 'Allow this action?', ['yes' => 'Approve', 'no' => 'Deny']);

        $this->assertSame(AgentStatus::WaitingForApproval, $ctx->getStatus());
        $this->assertSame('Allow this action?', $ctx->get('_human_question'));
        $this->assertSame(['yes' => 'Approve', 'no' => 'Deny'], $ctx->get('_human_options'));
    }

    public function test_is_waiting(): void
    {
        $hitl = new HumanInTheLoop();
        $ctx = AgentContext::empty();

        $this->assertFalse($hitl->isWaiting($ctx));

        $ctx = $hitl->requestApproval($ctx, 'Question?');

        $this->assertTrue($hitl->isWaiting($ctx));
    }

    public function test_resume(): void
    {
        $hitl = new HumanInTheLoop();
        $ctx = AgentContext::empty();
        $ctx = $hitl->requestApproval($ctx, 'Allow?');

        $ctx = $hitl->resume($ctx, 'Approved!');

        $this->assertSame(AgentStatus::Executing, $ctx->getStatus());
        $this->assertSame('Approved!', $ctx->get('_human_response'));
        $this->assertNull($ctx->get('_human_question'));
    }
}
