# Multi-Agent

Coordinate multiple agents for complex tasks.

## OrchestratorAgent

Decomposes tasks and dispatches subtasks to worker agents:

```php
use Token27\NexusAI\Agents\MultiAgent\OrchestratorAgent;
use Token27\NexusAI\Agents\Core\AgentConfig;

$orchestrator = new OrchestratorAgent('coordinator', 'Coordinates research team');
$orchestrator->registerWorker($researchAgent);
$orchestrator->registerWorker($writerAgent);
$orchestrator->registerWorker($reviewerAgent);

// Orchestrate decomposes the task and dispatches to workers (round-robin)
$result = $orchestrator->orchestrate('Write and review an article about PHP 8.4');
```

## AgentTeam

Manages a team of named agents:

```php
use Token27\NexusAI\Agents\MultiAgent\AgentTeam;

$team = new AgentTeam();
$team->register($researchAgent);
$team->register($writerAgent);

$agent = $team->get('researcher');       // Get by name
$all = $team->all();                     // All agents
$team->has('writer');                    // Check existence
```

## Handoff

Transfer execution between agents:

```php
use Token27\NexusAI\Agents\MultiAgent\Handoff;

$handoff = new Handoff();
$result = $handoff->transfer(
    from: $researchAgent,
    to: $writerAgent,
    context: 'Write based on these findings',
);
// The receiving agent gets context['handoff_from'] = source agent name
```

## MessageBus

Asynchronous message passing between agents:

```php
use Token27\NexusAI\Agents\MultiAgent\MessageBus;

$bus = new MessageBus();

// Send message
$bus->send('writer', ['research' => 'PHP 8.4 features...']);

// Check and receive
if ($bus->hasMessages('writer')) {
    $messages = $bus->receive('writer');
}
```

The `OrchestratorAgent` uses the MessageBus internally to pass context to workers.

---

> **Next:** [Memory →](memory.md)
