<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\ToolIntegration;

use Token27\NexusAI\Contract\ToolInterface;

/**
 * Security policy for tool usage by the agent.
 *
 * Defines which tools are allowed, which are blocked, and which
 * require human approval before execution. Provides allowlist
 * and blocklist semantics with a global approval toggle.
 *
 * Empty allowlist means all tools are allowed (except blocked ones).
 * Non-empty allowlist means only those tools are allowed.
 *
 * @see \Token27\NexusAI\Contract\ToolInterface
 * @see \Token27\NexusAI\Agents\ToolIntegration\ToolApprovalMiddleware
 */
final readonly class ToolPolicy
{
    /**
     * @param array<string> $allowedTools Allowlist. Empty = all allowed.
     * @param array<string> $blockedTools Blocklist.
     * @param bool $requireApproval Global approval requirement. If true, ALL tools need approval.
     * @param array<string> $approvalRequired Specific tools that require human approval.
     * @param float $maxCostPerCall Maximum cost in USD per tool call. 0 = no limit.
     */
    public function __construct(
        public array $allowedTools = [],
        public array $blockedTools = [],
        public bool $requireApproval = false,
        public array $approvalRequired = [],
        public float $maxCostPerCall = 0.0,
    ) {
    }

    /**
     * Checks if a tool is allowed to execute.
     *
     * Blocked tools are always denied. If the allowlist is non-empty,
     * the tool must be in it.
     *
     * @param ToolInterface $tool The tool to check.
     * @return bool True if the tool is allowed.
     */
    public function isAllowed(ToolInterface $tool): bool
    {
        $name = $tool->getName();

        // Blocklist takes precedence
        if (in_array($name, $this->blockedTools, true)) {
            return false;
        }

        // If allowlist is non-empty, tool must be in it
        if ($this->allowedTools !== [] && !in_array($name, $this->allowedTools, true)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if a tool requires human approval before execution.
     *
     * True if the global approve-all flag is set or if the tool
     * is explicitly listed in approvalRequired.
     *
     * @param ToolInterface $tool The tool to check.
     * @return bool True if approval is required.
     */
    public function needsApproval(ToolInterface $tool): bool
    {
        if ($this->requireApproval) {
            return true;
        }

        return in_array($tool->getName(), $this->approvalRequired, true);
    }
}
