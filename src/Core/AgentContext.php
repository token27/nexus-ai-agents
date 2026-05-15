<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Core;

use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\Planning\Plan;

/**
 * Immutable state container that flows through the agent execution loop.
 *
 * Every with*() method returns a NEW instance via clone, never mutating
 * the original. This follows the same pattern as nexus-ai's Pipeline\Context
 * and nexus-ai-workflows' WorkflowContext.
 *
 * Dedicated methods (withStatus, incrementStep, withCost, withPlan) exist
 * for frequent operations to prevent key-typo errors.
 *
 * @see \Token27\NexusAI\Pipeline\Context
 * @see \Token27\NexusAI\Workflows\Engine\WorkflowContext
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
final class AgentContext
{
    /** @var array<string, mixed> Arbitrary data store for step results and intermediates. */
    private array $data;

    /** @var AgentStatus Current lifecycle status. */
    private AgentStatus $status;

    /** @var int Number of steps executed so far. */
    private int $stepCount;

    /** @var float Total cost accrued in USD. */
    private float $totalCost;

    /** @var Plan|null Active execution plan. */
    private ?Plan $plan;

    /** @var float Timestamp when context was created (for elapsed time). */
    private readonly float $startedAt;

    /**
     * @param array<string, mixed> $data Initial data.
     * @param AgentStatus $status Initial status.
     * @param int $stepCount Initial step count.
     * @param float $totalCost Initial cost.
     * @param Plan|null $plan Initial plan.
     */
    public function __construct(
        array $data = [],
        AgentStatus $status = AgentStatus::Idle,
        int $stepCount = 0,
        float $totalCost = 0.0,
        ?Plan $plan = null,
    ) {
        $this->data = $data;
        $this->status = $status;
        $this->stepCount = $stepCount;
        $this->totalCost = $totalCost;
        $this->plan = $plan;
        $this->startedAt = microtime(true);
    }

    /**
     * Creates an empty context with Idle status.
     *
     * @return self A fresh context instance.
     */
    public static function empty(): self
    {
        return new self();
    }

    /**
     * Reads a value from the data store.
     *
     * @param string $key The data key.
     * @param mixed $default Default value if key doesn't exist.
     * @return mixed The value or default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Checks if a key exists in the data store.
     *
     * @param string $key The data key.
     * @return bool True if the key exists.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Returns a new context with the given key-value pair added.
     *
     * @param string $key The data key.
     * @param mixed $value The value.
     * @return self A new context instance.
     */
    public function with(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->data[$key] = $value;

        return $clone;
    }

    /**
     * Returns a new context with updated status.
     *
     * @param AgentStatus $status The new status.
     * @return self A new context instance.
     */
    public function withStatus(AgentStatus $status): self
    {
        $clone = clone $this;
        $clone->status = $status;

        return $clone;
    }

    /**
     * Returns a new context with step count incremented by 1.
     *
     * @return self A new context instance.
     */
    public function incrementStep(): self
    {
        $clone = clone $this;
        $clone->stepCount++;

        return $clone;
    }

    /**
     * Returns a new context with cost increased by the given amount.
     *
     * @param float $additional Additional cost in USD.
     * @return self A new context instance.
     */
    public function withCost(float $additional): self
    {
        $clone = clone $this;
        $clone->totalCost += $additional;

        return $clone;
    }

    /**
     * Returns a new context with the given plan.
     *
     * @param Plan $plan The new plan.
     * @return self A new context instance.
     */
    public function withPlan(Plan $plan): self
    {
        $clone = clone $this;
        $clone->plan = $plan;

        return $clone;
    }

    /**
     * Returns all data as an associative array.
     *
     * @return array<string, mixed> All stored data.
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Returns the current agent status.
     *
     * @return AgentStatus The current status.
     */
    public function getStatus(): AgentStatus
    {
        return $this->status;
    }

    /**
     * Returns the current step count.
     *
     * @return int Number of steps executed.
     */
    public function getStepCount(): int
    {
        return $this->stepCount;
    }

    /**
     * Returns the total cost accrued.
     *
     * @return float Total cost in USD.
     */
    public function getTotalCost(): float
    {
        return $this->totalCost;
    }

    /**
     * Returns the active plan, or null if none has been set.
     *
     * @return Plan|null The current plan.
     */
    public function getPlan(): ?Plan
    {
        return $this->plan;
    }

    /**
     * Returns elapsed time in milliseconds since context creation.
     *
     * @return float Milliseconds elapsed.
     */
    public function getElapsedMs(): float
    {
        return (microtime(true) - $this->startedAt) * 1000;
    }
}
