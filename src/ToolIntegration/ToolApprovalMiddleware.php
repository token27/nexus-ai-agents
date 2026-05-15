<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\ToolIntegration;

use Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Contract\ToolInterface;

/**
 * Middleware that enforces tool approval policies.
 *
 * Intercepts step execution and checks pending tool calls against
 * the ToolPolicy. Tools that require approval pause the agent
 * (WaitingForApproval status). Blocked tools return an error.
 *
 * @see \Token27\NexusAI\Agents\ToolIntegration\ToolPolicy
 * @see \Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface
 */
final readonly class ToolApprovalMiddleware implements AgentMiddlewareInterface
{
    /**
     * @param ToolPolicy $policy The tool approval policy.
     */
    public function __construct(
        private ToolPolicy $policy,
    ) {
    }

    public function process(AgentContext $context, callable $next): AgentContext
    {
        // Read pending tool calls from context
        $pendingTools = $context->get('_pending_tool_calls', []);

        if ($pendingTools === []) {
            // No tools pending — pass through
            return $next($context);
        }

        foreach ($pendingTools as $toolData) {
            // Tool data should contain the tool name at minimum
            if (!isset($toolData['name'])) {
                continue;
            }

            $toolName = $toolData['name'];

            // Create a lightweight anonymous tool for policy checking
            $tool = $this->createAnonymousTool($toolName);

            // Check if tool is blocked
            if (!$this->policy->isAllowed($tool)) {
                return $context
                    ->with('_last_output', sprintf('Tool "%s" is blocked by policy.', $toolName))
                    ->with('_tool_blocked', true);
            }

            // Check if tool requires human approval
            if ($this->policy->needsApproval($tool)) {
                return $context
                    ->withStatus(AgentStatus::WaitingForApproval)
                    ->with('_approval_required_for', $toolName)
                    ->with('_approval_question', sprintf(
                        'Tool "%s" requires human approval. Arguments: %s',
                        $toolName,
                        json_encode($toolData['arguments'] ?? [], JSON_THROW_ON_ERROR),
                    ));
            }
        }

        // All tools pass — execute
        return $next($context);
    }

    /**
     * Creates a lightweight anonymous ToolInterface implementation for policy checks.
     *
     * @param string $name The tool name.
     * @return ToolInterface A minimal tool instance.
     */
    private function createAnonymousTool(string $name): ToolInterface
    {
        return new readonly class ($name) implements ToolInterface {
            public function __construct(
                private string $name,
            ) {
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
}
