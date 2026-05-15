# Reflection

Reflectors evaluate step results and decide the next action in the agent loop.

## LLMReflector

Uses the LLM to evaluate outputs and make adaptive decisions:

```php
use Token27\NexusAI\Agents\Reflection\LLMReflector;

$reflector = new LLMReflector(
    driver: $driver,
    provider: 'openai',
    model: 'gpt-4o',
);
```

The LLM responds with one of these actions:

| Action | Effect | Use Case |
|--------|--------|----------|
| **Continue** | Proceed to next step | Step succeeded, more work to do |
| **Finish** | Exit loop with current output | Task is complete |
| **Replan** | Generate a new plan | Current approach isn't working |
| **Retry** | Re-execute same step | Transient failure |
| **AskUser** | Suspend for human input | Need clarification |
| **Abort** | Fail with reason | Task is impossible |

## AlwaysFinishReflector

Deterministic reflector — finishes on last step, continues otherwise:

```php
use Token27\NexusAI\Agents\Reflection\AlwaysFinishReflector;

$reflector = new AlwaysFinishReflector();
// Continue for steps 1..N-1, Finish on step N
```

## ReflectionResult

```php
use Token27\NexusAI\Agents\Reflection\ReflectionResult;

// Static factories
ReflectionResult::continue('Step looks good');
ReflectionResult::finish('Task complete');
ReflectionResult::replan('Need different approach');
ReflectionResult::abort('Cannot proceed');
```

## Custom Reflector

```php
use Token27\NexusAI\Agents\Contract\ReflectorInterface;

final class ScoreBasedReflector implements ReflectorInterface
{
    public function reflect(AgentContext $context, string $stepOutput): ReflectionResult
    {
        $score = $this->evaluate($stepOutput);

        return match (true) {
            $score >= 90 => ReflectionResult::finish('High quality output'),
            $score >= 50 => ReflectionResult::continue('Acceptable, continue'),
            $score >= 20 => ReflectionResult::replan('Quality too low'),
            default => ReflectionResult::abort('Impossible to improve'),
        };
    }
}
```

---

> **Next:** [AgentBuilder →](agent-builder.md)
