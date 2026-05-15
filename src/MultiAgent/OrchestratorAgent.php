<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\MultiAgent;

use Token27\NexusAI\Agents\Contract\AgentInterface;
use Token27\NexusAI\Agents\Core\AbstractAgent;
use Token27\NexusAI\Agents\Core\AgentConfig;
use Token27\NexusAI\Agents\Core\AgentResult;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\Execution\PassthroughExecutor;
use Token27\NexusAI\Agents\Planning\PlannerFactory;
use Token27\NexusAI\Agents\Reflection\AlwaysFinishReflector;

/**
 * Special agent that coordinates a team of worker agents.
 *
 * Decomposes a complex task into subtasks, dispatches them to
 * registered workers (round-robin assignment), collects results,
 * and compiles a final aggregated output.
 *
 * Extends AbstractAgent to inherit the full agent loop, but
 * overrides the orchestration logic.
 *
 * @see \Token27\NexusAI\Agents\MultiAgent\MessageBus
 * @see \Token27\NexusAI\Agents\MultiAgent\AgentTeam
 */
final class OrchestratorAgent extends AbstractAgent
{
    /** @var array<AgentInterface> Registered worker agents. */
    private array $workers = [];

    /** @var MessageBus Shared message bus. */
    private readonly MessageBus $bus;

    /** @var string Agent name. */
    private readonly string $name;

    /** @var string|null Agent description. */
    private readonly ?string $description;

    /**
     * @param string $name Agent name.
     * @param string|null $description Agent description.
     * @param AgentConfig $config Agent configuration.
     */
    public function __construct(
        string $name,
        ?string $description = null,
        AgentConfig $config = new AgentConfig(),
    ) {
        $planner = PlannerFactory::create($config->planningStrategy);

        parent::__construct(
            config: $config,
            planner: $planner,
            executor: new PassthroughExecutor(),
            reflector: new AlwaysFinishReflector(),
        );

        $this->name = $name;
        $this->description = $description;
        $this->bus = new MessageBus();
    }

    /** {@inheritdoc} */
    public function getName(): string
    {
        return $this->name;
    }

    /** {@inheritdoc} */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Registers a worker agent.
     *
     * @param AgentInterface $agent The worker agent.
     * @return $this
     */
    public function registerWorker(AgentInterface $agent): self
    {
        $this->workers[] = $agent;

        return $this;
    }

    /**
     * Returns the shared message bus.
     *
     * @return MessageBus The message bus.
     */
    public function getBus(): MessageBus
    {
        return $this->bus;
    }

    /**
     * Orchestrates a complex task across all workers.
     *
     * 1. Decomposes the task into subtasks
     * 2. Dispatches each subtask to a worker (round-robin)
     * 3. Collects results
     * 4. Compiles into a final response
     *
     * @param string $task The task to orchestrate.
     * @return AgentResult The compiled result.
     */
    public function orchestrate(string $task): AgentResult
    {
        $startTime = microtime(true);

        if ($this->workers === []) {
            return new AgentResult(
                status: AgentStatus::Failed,
                output: 'No workers registered for orchestration.',
                totalSteps: 0,
                elapsedMs: 0.0,
                totalCost: 0.0,
            );
        }

        // 1. Decompose the task into subtasks
        $subtasks = $this->decomposeTask($task);

        // 2. Dispatch to workers (round-robin)
        $results = [];
        $workerCount = count($this->workers);

        foreach ($subtasks as $i => $subtask) {
            $workerIndex = $i % $workerCount;
            $worker = $this->workers[$workerIndex];

            // Deliver any pending messages to the worker
            $context = [];
            if ($this->bus->hasMessages($worker->getName())) {
                $context['_messages'] = $this->bus->receive($worker->getName());
            }

            $result = $worker->run($subtask, $context);
            $results[] = [
                'worker' => $worker->getName(),
                'subtask' => $subtask,
                'output' => $result->output,
                'success' => $result->isSuccess(),
            ];
        }

        // 3. Compile results
        $compiledOutput = $this->compileResults($task, $results);

        return new AgentResult(
            status: AgentStatus::Completed,
            output: $compiledOutput,
            totalSteps: count($subtasks),
            elapsedMs: (microtime(true) - $startTime) * 1000,
            totalCost: 0.0,
            trace: $results,
        );
    }

    /**
     * Decomposes a task into subtasks.
     *
     * Simple approach: split on newlines, semicolons, or numbered items.
     * Complex decomposition would use an LLM planner.
     *
     * @param string $task The task to decompose.
     * @return array<string> List of subtasks.
     */
    private function decomposeTask(string $task): array
    {
        // Try to split on numbered items (1. task A, 2. task B)
        if (preg_match_all('/\d+[.)]\s*(.+?)(?=\d+[.)]|\z)/s', $task, $matches) && count($matches[1]) > 1) {
            return array_map('trim', $matches[1]);
        }

        // Split on newlines
        $lines = array_filter(array_map('trim', explode("\n", $task)), static fn (string $line): bool => $line !== '');

        if (count($lines) > 1) {
            return array_values($lines);
        }

        // Single task — return as-is
        return [$task];
    }

    /**
     * Compiles worker results into a final output.
     *
     * @param string $task The original task.
     * @param array<int, mixed> $results Worker results.
     * @return string The compiled output.
     */
    private function compileResults(string $task, array $results): string
    {
        $sections = [sprintf("Task: %s\n", $task)];

        foreach ($results as $i => $result) {
            $status = $result['success'] ? '✓' : '✗';
            $sections[] = sprintf(
                "%s Worker: %s\n   Subtask: %s\n   Output: %s",
                $status,
                $result['worker'],
                $result['subtask'],
                $result['output'],
            );
        }

        return implode("\n\n", $sections);
    }
}
