<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents;

use Token27\NexusAI\Agents\Contract\AgentInterface;
use Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface;
use Token27\NexusAI\Agents\Contract\ExecutorInterface;
use Token27\NexusAI\Agents\Contract\PlannerInterface;
use Token27\NexusAI\Agents\Contract\ReflectorInterface;
use Token27\NexusAI\Agents\Core\AbstractAgent;
use Token27\NexusAI\Agents\Core\AgentConfig;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;
use Token27\NexusAI\Agents\Execution\PassthroughExecutor;
use Token27\NexusAI\Agents\Execution\StepMapper;
use Token27\NexusAI\Agents\Execution\WorkflowExecutor;
use Token27\NexusAI\Agents\Planning\PlannerFactory;
use Token27\NexusAI\Agents\Reflection\AlwaysFinishReflector;
use Token27\NexusAI\Agents\Reflection\LLMReflector;
use Token27\NexusAI\Contract\DriverInterface;
use Token27\NexusAI\Driver\DriverRegistry;
use Token27\NexusAI\Observability\EventBus;
use Token27\NexusAI\Workflows\Runner\WorkflowRunner;

/**
 * Fluent builder for constructing agent instances.
 *
 * Provides a chainable API that wires together the agent's
 * components: planner, executor (with WorkflowRunner), and reflector.
 *
 * Usage:
 *   $agent = AgentBuilder::named('researcher')
 *       ->withDescription('Research assistant')
 *       ->withProvider('openai', 'gpt-4o')
 *       ->withStrategy(PlanningStrategy::ReAct)
 *       ->withBudget(1.00)
 *       ->withMaxSteps(20)
 *       ->withWorkflowRunner($runner)
 *       ->build();
 *
 * @see \Token27\NexusAI\Agents\Core\AbstractAgent
 * @see \Token27\NexusAI\Agents\Execution\WorkflowExecutor
 */
final class AgentBuilder
{
    private string $name;

    private ?string $description = null;

    private PlanningStrategy $strategy = PlanningStrategy::ReAct;

    private string $provider = 'openai';

    private string $model = 'gpt-4o';

    private float $budget = 10.0;

    private int $maxSteps = 50;

    private ?DriverInterface $driver = null;

    private ?DriverRegistry $driverRegistry = null;

    private ?WorkflowRunner $workflowRunner = null;

    private ?PlannerInterface $planner = null;

    private ?ExecutorInterface $executor = null;

    private ?ReflectorInterface $reflector = null;

    private ?EventBus $eventBus = null;

    /** @var array<AgentMiddlewareInterface> */
    private array $middlewares = [];

    private function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Creates a new builder with the given agent name.
     */
    public static function named(string $name): self
    {
        return new self($name);
    }

    /**
     * Sets the agent description.
     */
    public function withDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Sets the planning strategy (ReAct, ChainOfThought, PlanAndExecute).
     */
    public function withStrategy(PlanningStrategy $strategy): self
    {
        $this->strategy = $strategy;

        return $this;
    }

    /**
     * Sets the LLM provider and model for all components.
     */
    public function withProvider(string $provider, string $model = 'gpt-4o'): self
    {
        $this->provider = $provider;
        $this->model = $model;

        return $this;
    }

    /**
     * Sets the LLM driver directly (alternative to withDriverRegistry).
     */
    public function withDriver(DriverInterface $driver): self
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Sets the driver registry (for multi-provider support).
     */
    public function withDriverRegistry(DriverRegistry $registry): self
    {
        $this->driverRegistry = $registry;

        return $this;
    }

    /**
     * Sets the WorkflowRunner for step execution.
     *
     * Required for WorkflowExecutor to function. If not set,
     * a PassthroughExecutor will be used instead.
     */
    public function withWorkflowRunner(WorkflowRunner $runner): self
    {
        $this->workflowRunner = $runner;

        return $this;
    }

    /**
     * Sets a custom planner (overrides strategy-based factory).
     */
    public function withPlanner(PlannerInterface $planner): self
    {
        $this->planner = $planner;

        return $this;
    }

    /**
     * Sets a custom executor (overrides auto-wiring).
     */
    public function withExecutor(ExecutorInterface $executor): self
    {
        $this->executor = $executor;

        return $this;
    }

    /**
     * Sets a custom reflector (overrides auto-wiring).
     */
    public function withReflector(ReflectorInterface $reflector): self
    {
        $this->reflector = $reflector;

        return $this;
    }

    /**
     * Sets the maximum budget in USD.
     */
    public function withBudget(float $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * Sets the maximum number of steps.
     */
    public function withMaxSteps(int $maxSteps): self
    {
        $this->maxSteps = $maxSteps;

        return $this;
    }

    /**
     * Sets the EventBus for observability.
     */
    public function withEventBus(EventBus $eventBus): self
    {
        $this->eventBus = $eventBus;

        return $this;
    }

    /**
     * Adds a middleware to the agent's pipeline.
     */
    public function withMiddleware(AgentMiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * Builds and returns the configured agent.
     *
     * Auto-wires:
     * - Planner: PlannerFactory::create() with driver
     * - Executor: WorkflowExecutor (if WorkflowRunner set) or PassthroughExecutor
     * - Reflector: LLMReflector (if driver set) or AlwaysFinishReflector
     *
     * @return AgentInterface The built agent.
     */
    public function build(): AgentInterface
    {
        $config = new AgentConfig(
            budget: $this->budget,
            maxSteps: $this->maxSteps,
        );

        $driver = $this->resolveDriver();
        $planner = $this->resolvePlanner($driver);
        $executor = $this->resolveExecutor();
        $reflector = $this->resolveReflector($driver);

        $name = $this->name;
        $description = $this->description;

        // Create a concrete anonymous agent class
        $agent = new class ($config, $planner, $executor, $reflector, $this->eventBus, $name, $description) extends AbstractAgent {
            public function __construct(
                AgentConfig $config,
                PlannerInterface $planner,
                ExecutorInterface $executor,
                ReflectorInterface $reflector,
                ?EventBus $eventBus,
                private readonly string $agentName,
                private readonly ?string $agentDescription,
            ) {
                parent::__construct($config, $planner, $executor, $reflector, $eventBus);
            }

            public function getName(): string
            {
                return $this->agentName;
            }

            public function getDescription(): ?string
            {
                return $this->agentDescription;
            }
        };

        // Apply middlewares
        foreach ($this->middlewares as $middleware) {
            $agent->withMiddleware($middleware);
        }

        return $agent;
    }

    /**
     * Resolves the LLM driver from explicit driver or registry.
     */
    private function resolveDriver(): ?DriverInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        if ($this->driverRegistry !== null) {
            return $this->driverRegistry->resolve($this->provider);
        }

        return null;
    }

    /**
     * Resolves the planner: custom or factory-created.
     */
    private function resolvePlanner(?DriverInterface $driver): PlannerInterface
    {
        if ($this->planner !== null) {
            return $this->planner;
        }

        return PlannerFactory::create($this->strategy, $driver, $this->provider, $this->model);
    }

    /**
     * Resolves the executor: custom, workflow-based, or passthrough.
     */
    private function resolveExecutor(): ExecutorInterface
    {
        if ($this->executor !== null) {
            return $this->executor;
        }

        if ($this->workflowRunner !== null) {
            $mapper = new StepMapper($this->provider, $this->model);

            return new WorkflowExecutor($this->workflowRunner, $mapper);
        }

        return new PassthroughExecutor();
    }

    /**
     * Resolves the reflector: custom, LLM-based, or always-finish.
     */
    private function resolveReflector(?DriverInterface $driver): ReflectorInterface
    {
        if ($this->reflector !== null) {
            return $this->reflector;
        }

        if ($driver !== null) {
            return new LLMReflector($driver, $this->provider, $this->model);
        }

        return new AlwaysFinishReflector();
    }
}
