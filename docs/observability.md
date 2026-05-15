# Observability

The agent emits structured events via NexusAI's `EventBus` at each phase of the loop.

## Setup

```php
use Token27\NexusAI\Observability\EventBus;

$eventBus = new EventBus();
$eventBus->listen('agent.*', function (string $event, $source, $data) {
    echo "[{$event}] {$data->agentName}\n";
});

$agent = AgentBuilder::named('observable')
    ->withEventBus($eventBus)
    ->build();
```

## Event DTOs

| Event | DTO Class | Emitted When |
|-------|-----------|--------------|
| `agent.started` | `AgentStarted` | `run()` begins |
| `agent.step` | `AgentStep` | A plan step completes |
| `agent.reflection` | `AgentReflection` | Reflector makes a decision |
| `agent.completed` | `AgentCompleted` | Agent finishes successfully |
| `agent.failed` | `AgentFailed` | Agent fails (budget, abort, error) |
| `agent.tool_call` | `AgentToolCall` | A tool is invoked |

## Event Data

```php
// AgentStarted
$data->agentName;   // string
$data->input;       // string
$data->timestamp;   // float

// AgentStep
$data->agentName;   // string
$data->stepNumber;  // int
$data->stepAction;  // string ('think', 'act', 'observe', 'output')
$data->stepOutput;  // string
$data->elapsedMs;   // float

// AgentReflection
$data->agentName;   // string
$data->action;      // string ('continue', 'finish', 'replan', etc.)
$data->reasoning;   // string|null

// AgentCompleted
$data->agentName;   // string
$data->output;      // string
$data->totalSteps;  // int
$data->elapsedMs;   // float
$data->totalCost;   // float

// AgentFailed
$data->agentName;   // string
$data->error;       // string
$data->totalSteps;  // int
$data->elapsedMs;   // float
```

## Listening to Specific Events

```php
use Token27\NexusAI\Agents\Observability\Event\AgentCompleted;

$eventBus->listen('agent.completed', function (string $event, $source, AgentCompleted $data) {
    echo "Agent '{$data->agentName}' finished in {$data->elapsedMs}ms, costing \${$data->totalCost}\n";
});
```

---

> **Next:** [Testing →](testing.md)
