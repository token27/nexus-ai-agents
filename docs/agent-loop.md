# Agent Loop

The core of every agent is the **Plan → Execute → Reflect** cycle implemented in `AbstractAgent`.

## Lifecycle

```
run(input)
│
├── 1. PLAN    → Planner::plan(input, ctx) → Plan with PlanStep[]
│
├── 2. LOOP over steps:
│   ├── Budget check (fail if exceeded)
│   ├── Step limit check (fail if exceeded)
│   │
│   ├── EXECUTE → Executor::execute(step, ctx) → updated ctx
│   │   └── Middleware pipeline wraps execution
│   │
│   └── REFLECT → Reflector::reflect(ctx, output) → ReflectionResult
│       ├── Continue  → next step
│       ├── Finish    → exit loop with output
│       ├── Replan    → generate new plan, restart
│       ├── Retry     → re-execute same step
│       ├── AskUser   → suspend for human input
│       └── Abort     → fail with reason
│
└── 3. FINALIZE → AgentResult
```

## AgentContext

An immutable state container flowing through the loop:

```php
$ctx->get('key', 'default');    // Read
$ctx->with('key', 'value');     // Write (returns new instance)
$ctx->getStepCount();           // Steps executed
$ctx->getTotalCost();           // Cost accrued
$ctx->getPlan();                // Active Plan
$ctx->getStatus();              // AgentStatus enum
$ctx->getElapsedMs();           // Time since creation
```

## AgentConfig

```php
use Token27\NexusAI\Agents\Core\AgentConfig;

$config = new AgentConfig(
    budget: 10.0,                                 // Max USD
    maxSteps: 50,                                  // Max iterations
    planningStrategy: PlanningStrategy::ReAct,     // Strategy enum
    provider: 'openai',                            // Default provider
    model: 'gpt-4o',                               // Default model
);
```

## Extending AbstractAgent

```php
use Token27\NexusAI\Agents\Core\AbstractAgent;

final class ResearchAgent extends AbstractAgent
{
    public function getName(): string
    {
        return 'researcher';
    }

    public function getDescription(): ?string
    {
        return 'Researches topics and provides summaries';
    }
}
```

Instantiate with constructor injection:

```php
$agent = new ResearchAgent(
    config: $config,
    planner: PlannerFactory::create(PlanningStrategy::ReAct, $driver),
    executor: new WorkflowExecutor($runner, new StepMapper()),
    reflector: new LLMReflector($driver),
    eventBus: $eventBus,
);
```

---

> **Next:** [Planning Strategies →](planning-strategies.md)
