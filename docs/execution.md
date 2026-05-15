# Execution

Executors run individual plan steps. The key executor — `WorkflowExecutor` — bridges agents to the workflow engine.

## WorkflowExecutor

Converts each `PlanStep` into a mini-workflow and runs it through `WorkflowRunner`.

```php
use Token27\NexusAI\Agents\Execution\WorkflowExecutor;
use Token27\NexusAI\Agents\Execution\StepMapper;

$executor = new WorkflowExecutor(
    runner: $workflowRunner,
    mapper: new StepMapper('openai', 'gpt-4o'),
);
```

**Flow for each step:**

```
PlanStep → StepMapper::toWorkflow() → WorkflowBuilder → AINode → Workflow
Workflow → WorkflowRunner::run() → WorkflowResult
WorkflowResult → extract output → AgentContext::with('_last_output', output)
```

## StepMapper

Converts a `PlanStep` into a single-node workflow:

```php
use Token27\NexusAI\Agents\Execution\StepMapper;

$mapper = new StepMapper(provider: 'openai', model: 'gpt-4o');
$workflow = $mapper->toWorkflow($planStep, $agentContext);
```

The mapper builds context-aware prompts that include:

- Original user input
- Previous step results (chain of thought)
- Memory context (if available)
- Step description and expected output
- Action-specific system prompts

## PassthroughExecutor

No-op executor for testing and orchestration:

```php
use Token27\NexusAI\Agents\Execution\PassthroughExecutor;

$executor = new PassthroughExecutor();
// Simply returns: $ctx->with('_last_output', $step->description)
```

## Custom Executor

Implement `ExecutorInterface`:

```php
use Token27\NexusAI\Agents\Contract\ExecutorInterface;

final class MyExecutor implements ExecutorInterface
{
    public function execute(PlanStep $step, AgentContext $context): AgentContext
    {
        $result = $this->doWork($step);
        return $context->with('_last_output', $result);
    }
}
```

---

> **Next:** [Reflection →](reflection.md)
