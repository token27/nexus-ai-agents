<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Contract\ExecutorInterface;
use Token27\NexusAI\Agents\Contract\PlannerInterface;
use Token27\NexusAI\Agents\Contract\ReflectorInterface;
use Token27\NexusAI\Agents\Core\AbstractAgent;
use Token27\NexusAI\Agents\Core\AgentConfig;
use Token27\NexusAI\Agents\Core\AgentContext;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\Planning\Plan;
use Token27\NexusAI\Agents\Planning\PlanStep;
use Token27\NexusAI\Agents\Reflection\ReflectionResult;

final class AbstractAgentTest extends TestCase
{
    public function test_agent_completes_successfully(): void
    {
        $planner = $this->createMock(PlannerInterface::class);
        $planner->method('plan')->willReturn(new Plan(
            steps: [
                new PlanStep(description: 'Think', action: 'think'),
                new PlanStep(description: 'Output', action: 'output'),
            ],
            goal: 'Test',
        ));

        $executor = $this->createMock(ExecutorInterface::class);
        $executor->method('execute')->willReturnCallback(
            fn (PlanStep $step, AgentContext $ctx): AgentContext => $ctx->with('_last_output', $step->description),
        );

        $reflector = $this->createMock(ReflectorInterface::class);
        $reflector->method('reflect')->willReturnCallback(
            fn (AgentContext $ctx, string $output): ReflectionResult => match ($output) {
                'Think' => ReflectionResult::continue(),
                'Output' => ReflectionResult::finish(),
                default => ReflectionResult::continue(),
            },
        );

        $agent = $this->createConcreteAgent(
            name: 'test-agent',
            config: new AgentConfig(),
            planner: $planner,
            executor: $executor,
            reflector: $reflector,
        );

        $result = $agent->run('Test input');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(AgentStatus::Completed, $result->status);
        $this->assertSame('Output', $result->output);
        $this->assertSame(2, $result->totalSteps);
    }

    public function test_agent_handles_budget_exceeded(): void
    {
        $planner = $this->createMock(PlannerInterface::class);
        $planner->method('plan')->willReturn(new Plan(
            steps: [
                new PlanStep(description: 'Step 1'),
                new PlanStep(description: 'Step 2'),
            ],
        ));

        $executor = $this->createMock(ExecutorInterface::class);
        $executor->method('execute')->willReturnCallback(
            fn (PlanStep $step, AgentContext $ctx): AgentContext => $ctx->withCost(1000.0)->with('_last_output', 'expensive'),
        );

        $reflector = $this->createMock(ReflectorInterface::class);
        $reflector->method('reflect')->willReturn(ReflectionResult::continue());

        $agent = $this->createConcreteAgent(
            name: 'budget-agent',
            config: new AgentConfig(budget: 1.0),
            planner: $planner,
            executor: $executor,
            reflector: $reflector,
        );

        $result = $agent->run('Expensive task');

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('Budget exceeded', $result->output);
    }

    public function test_agent_handles_step_limit(): void
    {
        $planner = $this->createMock(PlannerInterface::class);
        $planner->method('plan')->willReturn(new Plan(
            steps: [
                new PlanStep(description: 'Step 1'),
                new PlanStep(description: 'Step 2'),
                new PlanStep(description: 'Step 3'),
            ],
        ));

        $executor = $this->createMock(ExecutorInterface::class);
        $executor->method('execute')->willReturnCallback(
            fn (PlanStep $step, AgentContext $ctx): AgentContext => $ctx->with('_last_output', 'ok'),
        );

        $reflector = $this->createMock(ReflectorInterface::class);
        $reflector->method('reflect')->willReturn(ReflectionResult::continue());

        $agent = $this->createConcreteAgent(
            name: 'limit-agent',
            config: new AgentConfig(maxSteps: 1),
            planner: $planner,
            executor: $executor,
            reflector: $reflector,
        );

        $result = $agent->run('Test');

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('Step limit exceeded', $result->output);
    }

    public function test_agent_handles_reflection_abort(): void
    {
        $planner = $this->createMock(PlannerInterface::class);
        $planner->method('plan')->willReturn(new Plan(
            steps: [new PlanStep(description: 'Step')],
        ));

        $executor = $this->createMock(ExecutorInterface::class);
        $executor->method('execute')->willReturnCallback(
            fn (PlanStep $step, AgentContext $ctx): AgentContext => $ctx->with('_last_output', 'failed'),
        );

        $reflector = $this->createMock(ReflectorInterface::class);
        $reflector->method('reflect')->willReturn(ReflectionResult::abort('Cannot proceed'));

        $agent = $this->createConcreteAgent(
            name: 'abort-agent',
            config: new AgentConfig(),
            planner: $planner,
            executor: $executor,
            reflector: $reflector,
        );

        $result = $agent->run('Test');

        $this->assertTrue($result->isFailed());
        $this->assertStringContainsString('Cannot proceed', $result->output);
    }

    public function test_agent_handles_replan(): void
    {
        $callCount = 0;
        $planner = $this->createMock(PlannerInterface::class);
        $planner->method('plan')->willReturnCallback(function () use (&$callCount): Plan {
            $callCount++;

            return new Plan(
                steps: [new PlanStep(description: "Plan {$callCount}")],
                goal: 'Test',
            );
        });

        $executor = $this->createMock(ExecutorInterface::class);
        $executor->method('execute')->willReturnCallback(
            fn (PlanStep $step, AgentContext $ctx): AgentContext => $ctx->with('_last_output', $step->description),
        );

        $reflector = $this->createMock(ReflectorInterface::class);
        $reflector->method('reflect')->willReturnCallback(
            fn (AgentContext $ctx, string $output): ReflectionResult => match ($output) {
                'Plan 1' => ReflectionResult::replan('Bad plan'),
                'Plan 2' => ReflectionResult::finish(),
                default => ReflectionResult::continue(),
            },
        );

        $agent = $this->createConcreteAgent(
            name: 'replan-agent',
            config: new AgentConfig(maxSteps: 10),
            planner: $planner,
            executor: $executor,
            reflector: $reflector,
        );

        $result = $agent->run('Test');

        $this->assertTrue($result->isSuccess());
        $this->assertSame('Plan 2', $result->output);
        $this->assertSame(2, $callCount); // Planned twice
    }

    public function test_with_middleware(): void
    {
        $planner = $this->createMock(PlannerInterface::class);
        $planner->method('plan')->willReturn(new Plan(
            steps: [new PlanStep(description: 'Step')],
        ));

        $executor = $this->createMock(ExecutorInterface::class);
        $executor->method('execute')->willReturnCallback(
            fn (PlanStep $step, AgentContext $ctx): AgentContext => $ctx->with('_last_output', 'done'),
        );

        $reflector = $this->createMock(ReflectorInterface::class);
        $reflector->method('reflect')->willReturn(ReflectionResult::finish());

        $middleware = new class () implements \Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface {
            public function process(AgentContext $context, callable $next): AgentContext
            {
                $context = $context->with('_middleware_applied', true);
                return $next($context);
            }
        };

        $agent = $this->createConcreteAgent(
            name: 'middleware-agent',
            config: new AgentConfig(),
            planner: $planner,
            executor: $executor,
            reflector: $reflector,
        );

        $agent->withMiddleware($middleware);

        $result = $agent->run('Test');

        $this->assertTrue($result->isSuccess());
    }

    public function test_get_config(): void
    {
        $config = new AgentConfig(provider: 'anthropic');
        $agent = $this->createConcreteAgent(
            name: 'config-agent',
            config: $config,
            planner: $this->createMock(PlannerInterface::class),
            executor: $this->createMock(ExecutorInterface::class),
            reflector: $this->createMock(ReflectorInterface::class),
        );

        $this->assertSame($config, $agent->getConfig());
    }

    private function createConcreteAgent(
        string $name,
        AgentConfig $config,
        PlannerInterface $planner,
        ExecutorInterface $executor,
        ReflectorInterface $reflector,
    ): AbstractAgent {
        return new class ($name, $config, $planner, $executor, $reflector) extends AbstractAgent {
            private readonly string $name;

            public function __construct(
                string $name,
                AgentConfig $config,
                PlannerInterface $planner,
                ExecutorInterface $executor,
                ReflectorInterface $reflector,
            ) {
                parent::__construct(
                    config: $config,
                    planner: $planner,
                    executor: $executor,
                    reflector: $reflector,
                );
                $this->name = $name;
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): ?string
            {
                return 'Test agent';
            }
        };
    }
}
