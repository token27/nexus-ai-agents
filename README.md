# NexusAI Agents

[![CI](https://github.com/token27/nexus-ai-agents/actions/workflows/ci.yml/badge.svg)](https://github.com/token27/nexus-ai-agents/actions)
[![Latest Version](https://img.shields.io/packagist/v/token27/nexus-ai-agents.svg?style=flat-square)](https://packagist.org/packages/token27/nexus-ai-agents)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

An autonomous agent framework for PHP 8.2+. Implements the **Plan → Execute → Reflect** loop with LLM-powered planning, workflow-based execution, and adaptive reflection. Built on [NexusAI](https://github.com/token27/nexus-ai) and [NexusAI Workflows](https://github.com/token27/nexus-ai-workflows).

## Features

- **Autonomous agent loop** — Plan → Execute → Reflect with configurable strategies
- **3 planning strategies** — ReAct, Chain-of-Thought, Plan-and-Execute — all LLM-powered with static fallbacks
- **WorkflowRunner integration** — each plan step executes as a workflow via StepMapper + WorkflowExecutor
- **LLM Reflection** — dynamic decision-making: Continue, Finish, Replan, Retry, AskUser, Abort
- **AgentBuilder** — fluent chainable API with auto-wiring of components
- **Multi-agent orchestration** — OrchestratorAgent, AgentTeam, Handoff, MessageBus
- **Memory system** — Working, Episodic, and Semantic memory with MemoryManager
- **Middleware pipeline** — BudgetGuard, MemoryInjection, Planning middleware
- **Budget & step limits** — configurable guardrails to prevent runaway agents
- **Observability** — 6 typed Event DTOs (AgentStarted, AgentStep, AgentReflection, AgentCompleted, AgentFailed, AgentToolCall)
- **FakeAgent** — deterministic test doubles with assertion helpers

## Installation

```bash
composer require token27/nexus-ai-agents
```

## Quick Start

### Using AgentBuilder (recommended)

```php
use Token27\NexusAI\Agents\AgentBuilder;
use Token27\NexusAI\Agents\Enum\PlanningStrategy;

$agent = AgentBuilder::named('researcher')
    ->withDescription('AI research assistant')
    ->withProvider('openai', 'gpt-4o')
    ->withStrategy(PlanningStrategy::ReAct)
    ->withDriver($driver)
    ->withWorkflowRunner($runner)
    ->withBudget(1.00)
    ->withMaxSteps(20)
    ->build();

$result = $agent->run('What are the latest trends in PHP?');

echo $result->output;       // Final answer
echo $result->totalSteps;   // Steps executed
echo $result->totalCost;    // Cost in USD
```

### Minimal (no LLM, static planning)

```php
$agent = AgentBuilder::named('simple')
    ->withStrategy(PlanningStrategy::ChainOfThought)
    ->build();

$result = $agent->run('Analyze this problem');
```

## Architecture

```
AgentBuilder::named('agent')
    ->withDriver($driver)           ← LLM for planning + reflection
    ->withWorkflowRunner($runner)   ← Workflow engine for execution
    ->build()

Agent::run(input)
    ├── Planner::plan(input, ctx)          ← LLM generates Plan with steps
    │   └── ReAct | CoT | PlanAndExecute
    │
    ├── WorkflowExecutor::execute(step)    ← Each step runs as a workflow
    │   └── StepMapper → WorkflowBuilder → WorkflowRunner
    │
    └── LLMReflector::reflect(ctx, output) ← LLM evaluates results
        └── Continue | Finish | Replan | Retry | Abort
```

## Documentation

Full documentation is available in the [`docs/`](docs/) directory:

- [Getting Started](docs/getting-started.md)
- [Agent Loop](docs/agent-loop.md)
- [Planning Strategies](docs/planning-strategies.md)
- [Execution](docs/execution.md)
- [Reflection](docs/reflection.md)
- [AgentBuilder](docs/agent-builder.md)
- [Multi-Agent](docs/multi-agent.md)
- [Memory](docs/memory.md)
- [Middleware](docs/middleware.md)
- [Observability](docs/observability.md)
- [Testing](docs/testing.md)

## Requirements

- PHP 8.2 or higher
- [token27/nexus-ai](https://github.com/token27/nexus-ai) ^1.0
- [token27/nexus-ai-workflows](https://github.com/token27/nexus-ai-workflows) ^1.0

## License

MIT. Please see [LICENSE](LICENSE) for more information.
