<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\ToolIntegration;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\ToolIntegration\ToolPolicy;
use Token27\NexusAI\Contract\ToolInterface;

final class ToolPolicyTest extends TestCase
{
    private function createTool(string $name): ToolInterface
    {
        return new readonly class ($name) implements ToolInterface {
            public function __construct(private string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): string
            {
                return '';
            }

            public function getParameters(): array
            {
                return [];
            }

            public function execute(array $arguments): string
            {
                return '';
            }
        };
    }

    public function test_empty_policy_allows_all(): void
    {
        $policy = new ToolPolicy();
        $tool = $this->createTool('get_weather');

        $this->assertTrue($policy->isAllowed($tool));
    }

    public function test_allowlist_blocks_unknown(): void
    {
        $policy = new ToolPolicy(allowedTools: ['get_weather']);
        $allowed = $this->createTool('get_weather');
        $blocked = $this->createTool('delete_files');

        $this->assertTrue($policy->isAllowed($allowed));
        $this->assertFalse($policy->isAllowed($blocked));
    }

    public function test_blocklist_blocks_specific(): void
    {
        $policy = new ToolPolicy(blockedTools: ['delete_files']);
        $allowed = $this->createTool('get_weather');
        $blocked = $this->createTool('delete_files');

        $this->assertTrue($policy->isAllowed($allowed));
        $this->assertFalse($policy->isAllowed($blocked));
    }

    public function test_require_approval_global(): void
    {
        $policy = new ToolPolicy(requireApproval: true);
        $tool = $this->createTool('get_weather');

        $this->assertTrue($policy->needsApproval($tool));
    }

    public function test_approval_required_specific(): void
    {
        $policy = new ToolPolicy(approvalRequired: ['transfer_funds']);
        $normal = $this->createTool('get_weather');
        $sensitive = $this->createTool('transfer_funds');

        $this->assertFalse($policy->needsApproval($normal));
        $this->assertTrue($policy->needsApproval($sensitive));
    }
}
