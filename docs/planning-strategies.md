# Planning Strategies

Planners generate execution plans by calling the LLM. Each strategy uses a different prompt template and parsing logic, with automatic fallback to static plans if the LLM fails.

## ReAct (Reasoning + Acting)

Interleaves reasoning and action. Best for **tool-using agents**.

```php
use Token27\NexusAI\Agents\Enum\PlanningStrategy;

$agent = AgentBuilder::named('tool-agent')
    ->withStrategy(PlanningStrategy::ReAct)
    ->withDriver($driver)
    ->build();
```

**LLM prompt pattern:**

```
Thought: [reasoning]
Action: [what to do]
Observation: [result]
...
Final Answer: [conclusion]
```

**Generated steps:** `think → act → observe → output`

## Chain-of-Thought

Step-by-step reasoning before answering. Best for **complex reasoning tasks**.

```php
$agent = AgentBuilder::named('reasoner')
    ->withStrategy(PlanningStrategy::ChainOfThought)
    ->withDriver($driver)
    ->build();
```

**LLM prompt pattern:**

```
Step 1: [breakdown]
Step 2: [reasoning]
...
Conclusion: [answer]
```

**Generated steps:** `think → think → ... → output`

## Plan-and-Execute

Full plan upfront, then execute sequentially. Best for **structured multi-step tasks**.

```php
$agent = AgentBuilder::named('planner')
    ->withStrategy(PlanningStrategy::PlanAndExecute)
    ->withDriver($driver)
    ->build();
```

**LLM prompt pattern:**

```
PLAN:
1. [step]
2. [step]
...
FINALIZE: [compile results]
```

**Generated steps:** `think → act → act → ... → output`

## PlannerFactory

Create planners programmatically:

```php
use Token27\NexusAI\Agents\Planning\PlannerFactory;

$planner = PlannerFactory::create(
    strategy: PlanningStrategy::ReAct,
    driver: $driver,               // optional — null = static plans only
    provider: 'openai',
    model: 'gpt-4o',
);
```

## Plan Structure

```php
use Token27\NexusAI\Agents\Planning\Plan;
use Token27\NexusAI\Agents\Planning\PlanStep;

$plan = new Plan(
    steps: [
        new PlanStep(description: 'Analyze', action: 'think', expectedOutput: 'Understanding'),
        new PlanStep(description: 'Execute', action: 'act', arguments: ['tool' => 'search']),
        new PlanStep(description: 'Conclude', action: 'output'),
    ],
    goal: 'Research PHP trends',
    reasoning: 'Using ReAct for tool-based research',
);
```

## Static Fallback

If no driver is provided or the LLM call fails, each planner returns a sensible static plan (4 steps) so the agent can still function.

---

> **Next:** [Execution →](execution.md)
