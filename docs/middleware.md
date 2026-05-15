# Middleware

Agent middleware wraps step execution in an onion pipeline, same pattern as NexusAI's Pipeline.

## How It Works

```
withMiddleware(A)
withMiddleware(B)

Execution order: A → B → Executor → B → A
```

## BudgetGuardMiddleware

Prevents steps from executing if budget is exceeded:

```php
use Token27\NexusAI\Agents\Middleware\BudgetGuardMiddleware;

$agent = AgentBuilder::named('guarded')
    ->withMiddleware(new BudgetGuardMiddleware(maxBudget: 5.00))
    ->build();
```

## MemoryInjectionMiddleware

Injects memory context into the agent context before each step:

```php
use Token27\NexusAI\Agents\Middleware\MemoryInjectionMiddleware;

$agent = AgentBuilder::named('with-memory')
    ->withMiddleware(new MemoryInjectionMiddleware($memoryManager))
    ->build();
```

## PlanningMiddleware

Adds planning metadata to the context:

```php
use Token27\NexusAI\Agents\Middleware\PlanningMiddleware;

$agent = AgentBuilder::named('planned')
    ->withMiddleware(new PlanningMiddleware())
    ->build();
```

## Custom Middleware

Implement `AgentMiddlewareInterface`:

```php
use Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface;
use Token27\NexusAI\Agents\Core\AgentContext;

final class TimingMiddleware implements AgentMiddlewareInterface
{
    public function process(AgentContext $context, callable $next): AgentContext
    {
        $start = microtime(true);
        $result = $next($context);
        $elapsed = (microtime(true) - $start) * 1000;

        return $result->with('_step_time_ms', $elapsed);
    }
}
```

---

> **Next:** [Observability →](observability.md)
