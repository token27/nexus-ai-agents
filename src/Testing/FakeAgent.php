<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Testing;

use Token27\NexusAI\Agents\Core\AgentResult;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;
use Token27\NexusAI\Agents\Enum\ReflectionAction;
use Token27\NexusAI\Agents\Planning\Plan;
use Token27\NexusAI\Agents\Planning\PlanStep;
use Token27\NexusAI\Agents\Reflection\ReflectionResult;

/**
 * Test double for agent execution without HTTP calls.
 *
 * Provides a deterministic agent that returns preconfigured
 * outputs, plans, and reflections. Includes assertion helpers
 * for verifying agent behavior in tests.
 *
 * Usage:
 *   $fake = FakeAgent::named('test')
 *       ->willReturnStepOutputs('step1 result', 'final output')
 *       ->build();
 *
 *   $result = $fake->run('do something');
 *   $fake->assertCompleted();
 *   $fake->assertStepCount(2);
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 */
final class FakeAgent
{
    private string $name;

    private ?string $description = null;

    private ?Plan $plan = null;

    /** @var array<string> Step outputs to return in sequence. */
    private array $stepOutputs = [];

    /** @var array<ReflectionResult> Reflection results to return in sequence. */
    private array $reflections = [];

    /** @var AgentResult|null Last execution result. */
    private ?AgentResult $lastResult = null;

    /** @var int Number of executions. */
    private int $runCount = 0;

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Creates a new FakeAgent builder with the given name.
     */
    public static function named(string $name): self
    {
        return new self($name);
    }

    /**
     * Sets a description for the fake agent.
     */
    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Configures the outputs that each step execution will return.
     *
     * @param string ...$outputs Step outputs in execution order.
     */
    public function willReturnStepOutputs(string ...$outputs): self
    {
        $this->stepOutputs = $outputs;

        return $this;
    }

    /**
     * Configures a custom plan for the agent.
     */
    public function willReturnPlan(Plan $plan): self
    {
        $this->plan = $plan;

        return $this;
    }

    /**
     * Configures custom reflection results.
     *
     * @param ReflectionResult ...$reflections Reflection results in order.
     */
    public function willReturnReflections(ReflectionResult ...$reflections): self
    {
        $this->reflections = $reflections;

        return $this;
    }

    /**
     * Sets the planning strategy (no-op in FakeAgent, kept for API compatibility).
     */
    public function withStrategy(PlanningStrategy $strategy): self
    {
        // FakeAgent ignores strategy — it uses preconfigured outputs
        unset($strategy);

        return $this;
    }

    /**
     * Builds the fake agent.
     *
     * @return self This instance (acts as both builder and agent).
     */
    public function build(): self
    {
        return $this;
    }

    /**
     * Runs the fake agent with preconfigured behavior.
     *
     * @param string $input The task input.
     * @param array<string, mixed> $context Optional initial context.
     * @return AgentResult The execution result.
     */
    public function run(string $input, array $context = []): AgentResult
    {
        $this->runCount++;

        // Build plan from step outputs if no custom plan is set
        $plan = $this->plan ?? $this->buildDefaultPlan($input);

        $steps = $plan->steps;
        $trace = [];
        $finalOutput = '';

        foreach ($steps as $i => $step) {
            $output = $this->stepOutputs[$i] ?? $step->description;
            $finalOutput = $output;

            $trace[] = [
                'phase' => 'execute',
                'step' => $i + 1,
                'action' => $step->action,
                'output' => $output,
            ];

            // Check reflection
            if (isset($this->reflections[$i])) {
                $reflection = $this->reflections[$i];
                $trace[] = [
                    'phase' => 'reflection',
                    'action' => $reflection->action->value,
                    'reasoning' => $reflection->reasoning,
                ];

                if ($reflection->action === ReflectionAction::Finish) {
                    break;
                }

                if ($reflection->action === ReflectionAction::Abort) {
                    $this->lastResult = new AgentResult(
                        status: AgentStatus::Failed,
                        output: $reflection->reasoning ?? 'Aborted',
                        totalSteps: $i + 1,
                        elapsedMs: 0.0,
                        totalCost: 0.0,
                        trace: $trace,
                    );

                    return $this->lastResult;
                }
            }
        }

        $this->lastResult = new AgentResult(
            status: AgentStatus::Completed,
            output: $finalOutput,
            totalSteps: count($steps),
            elapsedMs: 0.0,
            totalCost: 0.0,
            trace: $trace,
        );

        return $this->lastResult;
    }

    // ── Assertions ───────────────────────────────────────────────

    /**
     * Asserts the agent completed successfully.
     *
     * @throws \RuntimeException If assertion fails.
     */
    public function assertCompleted(): void
    {
        $this->assertHasResult();

        if ($this->lastResult->status !== AgentStatus::Completed) {
            throw new \RuntimeException(sprintf(
                'Expected agent to complete, but status was: %s',
                $this->lastResult->status->value,
            ));
        }
    }

    /**
     * Asserts the agent failed.
     *
     * @throws \RuntimeException If assertion fails.
     */
    public function assertFailed(): void
    {
        $this->assertHasResult();

        if ($this->lastResult->status !== AgentStatus::Failed) {
            throw new \RuntimeException(sprintf(
                'Expected agent to fail, but status was: %s',
                $this->lastResult->status->value,
            ));
        }
    }

    /**
     * Asserts the number of steps executed.
     *
     * @throws \RuntimeException If assertion fails.
     */
    public function assertStepCount(int $expected): void
    {
        $this->assertHasResult();

        if ($this->lastResult->totalSteps !== $expected) {
            throw new \RuntimeException(sprintf(
                'Expected %d steps, but got %d.',
                $expected,
                $this->lastResult->totalSteps,
            ));
        }
    }

    /**
     * Asserts the output contains a given string.
     *
     * @throws \RuntimeException If assertion fails.
     */
    public function assertOutputContains(string $substring): void
    {
        $this->assertHasResult();

        if (!str_contains($this->lastResult->output, $substring)) {
            throw new \RuntimeException(sprintf(
                'Expected output to contain "%s", but got: %s',
                $substring,
                $this->lastResult->output,
            ));
        }
    }

    /**
     * Asserts the agent was run the expected number of times.
     *
     * @throws \RuntimeException If assertion fails.
     */
    public function assertRunCount(int $expected): void
    {
        if ($this->runCount !== $expected) {
            throw new \RuntimeException(sprintf(
                'Expected %d runs, but got %d.',
                $expected,
                $this->runCount,
            ));
        }
    }

    /**
     * Returns the last execution result.
     */
    public function getLastResult(): ?AgentResult
    {
        return $this->lastResult;
    }

    // ── Internal helpers ─────────────────────────────────────────

    /**
     * Builds a default plan based on step outputs count.
     */
    private function buildDefaultPlan(string $input): Plan
    {
        $count = max(count($this->stepOutputs), 1);
        $steps = [];

        for ($i = 0; $i < $count; $i++) {
            $isLast = $i === $count - 1;
            $steps[] = new PlanStep(
                description: $isLast ? 'Produce final output.' : sprintf('Step %d of the plan.', $i + 1),
                action: $isLast ? 'output' : 'act',
            );
        }

        return new Plan(
            steps: $steps,
            goal: $input,
            reasoning: 'FakeAgent auto-generated plan.',
        );
    }

    /**
     * Asserts that the agent has been run at least once.
     */
    private function assertHasResult(): void
    {
        if ($this->lastResult === null) {
            throw new \RuntimeException('Agent has not been run yet. Call run() first.');
        }
    }
}
