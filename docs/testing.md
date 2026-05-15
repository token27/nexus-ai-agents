# Testing

## FakeAgent

Deterministic test double that simulates agent behavior without LLM calls:

```php
use Token27\NexusAI\Agents\Testing\FakeAgent;

$fake = FakeAgent::named('test-agent')
    ->willReturnStepOutputs('step 1 result', 'step 2 result', 'final answer')
    ->build();

$result = $fake->run('Do something complex');

// Assertions
$fake->assertCompleted();
$fake->assertStepCount(3);
$fake->assertOutputContains('final answer');
$fake->assertRunCount(1);
```

## Preconfigured Plans

```php
use Token27\NexusAI\Agents\Planning\Plan;
use Token27\NexusAI\Agents\Planning\PlanStep;

$fake = FakeAgent::named('custom-plan')
    ->willReturnPlan(new Plan(
        steps: [
            new PlanStep(description: 'Research', action: 'act'),
            new PlanStep(description: 'Summarize', action: 'output'),
        ],
        goal: 'Research and summarize',
    ))
    ->willReturnStepOutputs('research data', 'final summary')
    ->build();
```

## Preconfigured Reflections

```php
use Token27\NexusAI\Agents\Reflection\ReflectionResult;

$fake = FakeAgent::named('reflect-test')
    ->willReturnStepOutputs('output 1', 'output 2')
    ->willReturnReflections(
        ReflectionResult::continue('Keep going'),
        ReflectionResult::finish('Done'),
    )
    ->build();
```

## Assertion Methods

| Method | Asserts |
|--------|---------|
| `assertCompleted()` | Agent finished successfully |
| `assertFailed()` | Agent failed |
| `assertStepCount(int)` | Exact number of steps |
| `assertOutputContains(string)` | Output includes substring |
| `assertRunCount(int)` | Number of `run()` calls |

## PHPUnit Example

```php
final class MyAgentTest extends TestCase
{
    public function test_agent_produces_summary(): void
    {
        $fake = FakeAgent::named('summarizer')
            ->willReturnStepOutputs(
                'Raw research data about PHP 8.4',
                'PHP 8.4 introduces property hooks and asymmetric visibility.',
            )
            ->build();

        $result = $fake->run('Summarize PHP 8.4 features');

        $this->assertTrue($result->isSuccess());
        $this->assertStringContainsString('PHP 8.4', $result->output);
        $fake->assertStepCount(2);
    }

    public function test_agent_handles_abort(): void
    {
        $fake = FakeAgent::named('aborter')
            ->willReturnStepOutputs('failed attempt')
            ->willReturnReflections(ReflectionResult::abort('Task impossible'))
            ->build();

        $result = $fake->run('Impossible task');

        $this->assertTrue($result->isFailed());
        $fake->assertFailed();
    }
}
```

## Testing with AgentBuilder

For integration-style tests using real components but fake LLM:

```php
use Token27\NexusAI\Driver\Fake\FakeDriver;
use Token27\NexusAI\Response\TextResponse;
use Token27\NexusAI\Enum\FinishReason;
use Token27\NexusAI\Workflows\Runner\FakeWorkflowRunner;

$fakeDriver = new FakeDriver();
$fakeDriver->willReturn(new TextResponse(text: 'Plan: 1. Think 2. Act', finishReason: FinishReason::Stop));

$fakeRunner = new FakeWorkflowRunner();
$fakeRunner->willReturnText('AI generated output');

$agent = AgentBuilder::named('integration-test')
    ->withDriver($fakeDriver)
    ->withWorkflowRunner($fakeRunner)
    ->build();

$result = $agent->run('Test input');
```

---

> Back to [README](../README.md)
