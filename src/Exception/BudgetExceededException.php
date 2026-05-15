<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Exception;

/**
 * Thrown when the agent exceeds its configured budget.
 *
 * @see \Token27\NexusAI\Agents\Core\AgentConfig::$budget
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
class BudgetExceededException extends AgentException
{
    /**
     * @param float $used The amount used.
     * @param float $limit The budget limit.
     */
    public function __construct(
        public readonly float $used,
        public readonly float $limit,
    ) {
        parent::__construct(
            sprintf('Budget exceeded: $%.4f used of $%.2f limit.', $this->used, $this->limit),
        );
    }
}
