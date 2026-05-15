<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Core;

use Token27\NexusAI\Agents\Enum\PlanningStrategy;
use Token27\NexusAI\Contract\ToolInterface;

/**
 * Immutable configuration for an autonomous agent.
 *
 * Defines the provider, model, temperature, budget, step limits,
 * planning strategy, and available tools. Follows the same
 * value-object pattern as nexus-ai's PendingRequest — every
 * property has a sensible default.
 *
 * @see \Token27\NexusAI\Agents\Contract\AgentInterface
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
final readonly class AgentConfig
{
    /**
     * @param string $provider Provider key (e.g., 'openai', 'anthropic').
     * @param string $model Model identifier (e.g., 'gpt-4o').
     * @param float $temperature LLM temperature (0.0-2.0).
     * @param int $maxTokens Maximum tokens per request.
     * @param float $budget Maximum budget in USD before stopping.
     * @param int $maxSteps Safety limit: maximum steps before aborting.
     * @param PlanningStrategy $planningStrategy The planning strategy to use.
     * @param array<ToolInterface> $tools Tools available to the agent.
     * @param string|null $systemPrompt Optional system prompt (strategy provides default if null).
     */
    public function __construct(
        public string $provider = 'openai',
        public string $model = 'gpt-4o',
        public float $temperature = 0.7,
        public int $maxTokens = 4096,
        public float $budget = 10.0,
        public int $maxSteps = 20,
        public PlanningStrategy $planningStrategy = PlanningStrategy::ReAct,
        public array $tools = [],
        public ?string $systemPrompt = null,
    ) {
    }
}
