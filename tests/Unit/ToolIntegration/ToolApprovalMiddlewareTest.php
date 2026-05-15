<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\ToolIntegration;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\ToolIntegration\ToolApprovalMiddleware;
use Token27\NexusAI\Agents\ToolIntegration\ToolPolicy;

final class ToolApprovalMiddlewareTest extends TestCase
{
    public function test_passthrough_when_no_tools(): void
    {
        $policy = new ToolPolicy();
        $middleware = new ToolApprovalMiddleware($policy);
        $ctx = AgentContext::empty();

        $result = $middleware->process($ctx, fn (AgentContext $c): AgentContext => $c->with('passed', true));

        $this->assertTrue($result->get('passed'));
    }

    public function test_blocks_blocked_tool(): void
    {
        $policy = new ToolPolicy(blockedTools: ['delete_files']);
        $middleware = new ToolApprovalMiddleware($policy);

        $ctx = AgentContext::empty()->with('_pending_tool_calls', [
            ['name' => 'delete_files', 'arguments' => []],
        ]);

        $nextCalled = false;
        $result = $middleware->process($ctx, function (AgentContext $c) use (&$nextCalled): AgentContext {
            $nextCalled = true;

            return $c;
        });

        $this->assertFalse($nextCalled);
        $this->assertTrue($result->get('_tool_blocked'));
    }

    public function test_pauses_for_approval(): void
    {
        $policy = new ToolPolicy(approvalRequired: ['transfer_funds']);
        $middleware = new ToolApprovalMiddleware($policy);

        $ctx = AgentContext::empty()->with('_pending_tool_calls', [
            ['name' => 'transfer_funds', 'arguments' => ['amount' => 100]],
        ]);

        $result = $middleware->process($ctx, fn (AgentContext $c): AgentContext => $c);

        $this->assertSame(AgentStatus::WaitingForApproval, $result->getStatus());
        $this->assertSame('transfer_funds', $result->get('_approval_required_for'));
    }
}
