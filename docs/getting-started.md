# Getting Started

## Requirements

- PHP 8.2 or higher
- [token27/nexus-ai](https://github.com/token27/nexus-ai) ^1.0
- [token27/nexus-ai-workflows](https://github.com/token27/nexus-ai-workflows) ^1.0

## Installation

```bash
composer require token27/nexus-ai-agents
```

## Your First Agent

### With AgentBuilder (recommended)

```php
use Token27\NexusAI\Agents\AgentBuilder;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;
use Token27\NexusAI\Driver\DriverRegistry;
use Token27\NexusAI\Workflows\Runner\WorkflowRunner;

// 1. Set up drivers
$registry = new DriverRegistry();
$registry->register('openai', fn () => /* your driver */);
$driver = $registry->get('openai');

// 2. Set up workflow runner
$runner = new WorkflowRunner($registry);

// 3. Build the agent
$agent = AgentBuilder::named('assistant')
    ->withDescription('A helpful AI assistant')
    ->withProvider('openai', 'gpt-4o')
    ->withStrategy(PlanningStrategy::ReAct)
    ->withDriver($driver)
    ->withWorkflowRunner($runner)
    ->withBudget(5.00)
    ->withMaxSteps(20)
    ->build();

// 4. Run
$result = $agent->run('Explain the SOLID principles in PHP');

echo $result->output;       // The AI-generated explanation
echo $result->totalSteps;   // Number of steps executed
echo $result->elapsedMs;    // Execution time in ms
echo $result->totalCost;    // Cost in USD
```

### Minimal (no LLM)

For testing or prototyping without API keys:

```php
$agent = AgentBuilder::named('simple')
    ->withStrategy(PlanningStrategy::ChainOfThought)
    ->build();

$result = $agent->run('Hello');
// Uses static plans + PassthroughExecutor + AlwaysFinishReflector
```

## AgentResult

```php
$result->status;       // AgentStatus::Completed | AgentStatus::Failed
$result->output;       // string — final answer
$result->totalSteps;   // int — steps executed
$result->elapsedMs;    // float — total time
$result->totalCost;    // float — cost in USD
$result->trace;        // array — full execution trace (planning, steps, reflections)
$result->metadata;     // array — context data
$result->isSuccess();  // bool
$result->isFailed();   // bool
```

---

> **Next:** [Agent Loop →](agent-loop.md)
