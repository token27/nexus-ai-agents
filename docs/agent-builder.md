# AgentBuilder

Fluent API for constructing agents with automatic component wiring.

## Basic Usage

```php
use Token27\NexusAI\Agents\AgentBuilder;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;

$agent = AgentBuilder::named('my-agent')
    ->withDescription('What this agent does')
    ->withProvider('openai', 'gpt-4o')
    ->withStrategy(PlanningStrategy::ReAct)
    ->withDriver($driver)
    ->withWorkflowRunner($runner)
    ->build();
```

## Configuration Methods

| Method | Description | Default |
|--------|-------------|---------|
| `withDescription(string)` | Agent description | `null` |
| `withStrategy(PlanningStrategy)` | Planning strategy | `ReAct` |
| `withProvider(string, string)` | Provider + model | `openai`, `gpt-4o` |
| `withDriver(DriverInterface)` | LLM driver (direct) | `null` |
| `withDriverRegistry(DriverRegistry)` | Multi-provider registry | `null` |
| `withWorkflowRunner(WorkflowRunner)` | Workflow execution engine | `null` |
| `withBudget(float)` | Max cost in USD | `10.0` |
| `withMaxSteps(int)` | Max iterations | `50` |
| `withEventBus(EventBus)` | Observability | `null` |
| `withMiddleware(AgentMiddlewareInterface)` | Add middleware | none |

## Override Components

Override auto-wired components:

```php
$agent = AgentBuilder::named('custom')
    ->withPlanner($myPlanner)         // Custom planner
    ->withExecutor($myExecutor)       // Custom executor
    ->withReflector($myReflector)     // Custom reflector
    ->build();
```

## Auto-Wiring Rules

When you call `build()`, the builder auto-wires:

| Component | If available | Fallback |
|-----------|-------------|----------|
| **Planner** | `PlannerFactory::create()` with driver | Static plans |
| **Executor** | `WorkflowExecutor` (if `WorkflowRunner` set) | `PassthroughExecutor` |
| **Reflector** | `LLMReflector` (if driver set) | `AlwaysFinishReflector` |

## Full Example

```php
$agent = AgentBuilder::named('research-assistant')
    ->withDescription('Researches topics using ReAct reasoning')
    ->withProvider('openai', 'gpt-4o')
    ->withStrategy(PlanningStrategy::ReAct)
    ->withDriver($openaiDriver)
    ->withWorkflowRunner($runner)
    ->withBudget(2.00)
    ->withMaxSteps(15)
    ->withEventBus($eventBus)
    ->withMiddleware(new BudgetGuardMiddleware(2.00))
    ->build();

$result = $agent->run('Compare PHP and Python for web development');
```

---

> **Next:** [Multi-Agent →](multi-agent.md)
