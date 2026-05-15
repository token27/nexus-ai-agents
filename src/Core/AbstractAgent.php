<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Core;

use Token27\NexusAI\Agents\Contract\AgentInterface;
use Token27\NexusAI\Agents\Contract\AgentMiddlewareInterface;
use Token27\NexusAI\Agents\Contract\ExecutorInterface;
use Token27\NexusAI\Agents\Contract\PlannerInterface;
use Token27\NexusAI\Agents\Contract\ReflectorInterface;
use Token27\NexusAI\Agents\Enum\AgentStatus;
use Token27\NexusAI\Agents\Enum\ReflectionAction;
use Token27\NexusAI\Agents\Observability\Event\AgentCompleted;
use Token27\NexusAI\Agents\Observability\Event\AgentFailed;
use Token27\NexusAI\Agents\Observability\Event\AgentReflection;
use Token27\NexusAI\Agents\Observability\Event\AgentStarted;
use Token27\NexusAI\Agents\Observability\Event\AgentStep;
use Token27\NexusAI\Agents\Planning\PlanStep;
use Token27\NexusAI\Observability\EventBus;

/**
 * Base implementation of the autonomous agent loop.
 *
 * Orchestrates the Plan → Execute → Reflect cycle with:
 * - Budget and step-limit guardrails
 * - Onion middleware pipeline for cross-cutting concerns
 * - Optional EventBus integration for observability
 * - Full execution trace for debugging
 *
 * Concrete agents extend this class and implement getName()
 * and getDescription(). The constructor receives Planner,
 * Executor, and Reflector implementations (strategy pattern).
 *
 * @see \Token27\NexusAI\Agents\Contract\AgentInterface
 * @see \Token27\NexusAI\Agents\Planning\PlannerFactory
 */
abstract class AbstractAgent implements AgentInterface
{
    /** @var array<AgentMiddlewareInterface> Registered middlewares. */
    private array $middlewares = [];

    /**
     * @param AgentConfig $config Immutable agent configuration.
     * @param PlannerInterface $planner Planning strategy implementation.
     * @param ExecutorInterface $executor Step execution implementation.
     * @param ReflectorInterface $reflector Reflection/decision implementation.
     * @param EventBus|null $eventBus Optional EventBus for observability events.
     */
    public function __construct(
        protected readonly AgentConfig $config,
        protected readonly PlannerInterface $planner,
        protected readonly ExecutorInterface $executor,
        protected readonly ReflectorInterface $reflector,
        protected readonly ?EventBus $eventBus = null,
    ) {
    }

    /** {@inheritdoc} */
    abstract public function getName(): string;

    /** {@inheritdoc} */
    abstract public function getDescription(): ?string;

    /** {@inheritdoc} */
    public function getConfig(): AgentConfig
    {
        return $this->config;
    }

    /**
     * Adds a middleware to the agent's execution chain.
     *
     * Middlewares are called in the order they are added (outermost first).
     *
     * @param AgentMiddlewareInterface $middleware The middleware to add.
     */
    public function withMiddleware(AgentMiddlewareInterface $middleware): static
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /** {@inheritdoc} */
    public function run(string $input, array $context = []): AgentResult
    {
        $trace = [];
        $ctx = new AgentContext(data: $context);

        // Seed the input
        $ctx = $ctx->with('_input', $input);

        // Emit AgentStarted
        $this->eventBus?->emit('agent.started', $this, new AgentStarted(
            agentName: $this->getName(),
            input: $input,
            timestamp: microtime(true),
        ));

        // ── PLANNING PHASE ──
        $ctx = $ctx->withStatus(AgentStatus::Planning);
        $plan = $this->planner->plan($input, $ctx);
        $ctx = $ctx->withPlan($plan);
        $trace[] = [
            'phase' => 'planning',
            'goal' => $plan->goal,
            'reasoning' => $plan->reasoning,
            'stepCount' => count($plan->steps),
        ];

        // ── EXECUTION LOOP ──
        $finalOutput = '';
        $currentStepIndex = 0;

        while ($currentStepIndex < count($plan->steps)) {
            // Budget check
            if ($ctx->getTotalCost() > $this->config->budget) {
                return $this->fail(
                    ctx: $ctx,
                    trace: $trace,
                    error: sprintf(
                        'Budget exceeded: $%.4f used of $%.2f limit.',
                        $ctx->getTotalCost(),
                        $this->config->budget,
                    ),
                );
            }

            // Step limit check
            if ($ctx->getStepCount() >= $this->config->maxSteps) {
                return $this->fail(
                    ctx: $ctx,
                    trace: $trace,
                    error: sprintf(
                        'Step limit exceeded: %d steps executed (max: %d).',
                        $ctx->getStepCount(),
                        $this->config->maxSteps,
                    ),
                );
            }

            $step = $plan->steps[$currentStepIndex];

            // Execute step through middleware chain
            $ctx = $ctx->withStatus(AgentStatus::Executing);
            $ctx = $this->executeWithMiddleware($ctx, $step);
            $ctx = $ctx->incrementStep();

            $stepOutput = $ctx->get('_last_output', '');

            // Emit AgentStep
            $this->eventBus?->emit('agent.step', $this, new AgentStep(
                agentName: $this->getName(),
                stepNumber: $ctx->getStepCount(),
                stepAction: $step->action,
                stepOutput: $stepOutput,
                elapsedMs: $ctx->getElapsedMs(),
            ));

            $trace[] = [
                'phase' => 'execute',
                'step' => $currentStepIndex + 1,
                'action' => $step->action,
                'description' => $step->description,
                'output' => $stepOutput,
            ];

            // ── REFLECTION PHASE ──
            $ctx = $ctx->withStatus(AgentStatus::Reflecting);
            $reflection = $this->reflector->reflect($ctx, $stepOutput);

            // Emit AgentReflection
            $this->eventBus?->emit('agent.reflection', $this, new AgentReflection(
                agentName: $this->getName(),
                action: $reflection->action->value,
                reasoning: $reflection->reasoning,
            ));

            $trace[] = [
                'phase' => 'reflection',
                'action' => $reflection->action->value,
                'reasoning' => $reflection->reasoning,
            ];

            // Act on the reflection
            switch ($reflection->action) {
                case ReflectionAction::Finish:
                    $finalOutput = $stepOutput;
                    break 2; // Exit both switch and while

                case ReflectionAction::Continue:
                    $currentStepIndex++;
                    break;

                case ReflectionAction::Replan:
                    $ctx = $ctx->withStatus(AgentStatus::Planning);
                    $plan = $this->planner->plan($input, $ctx);
                    $ctx = $ctx->withPlan($plan);
                    $currentStepIndex = 0;
                    $trace[] = [
                        'phase' => 'replanning',
                        'goal' => $plan->goal,
                    ];
                    break;

                case ReflectionAction::Retry:
                    // Stay on the same step, don't increment index
                    break;

                case ReflectionAction::AskUser:
                    $ctx = $ctx->withStatus(AgentStatus::WaitingForApproval);
                    $ctx = $ctx->with('_human_question', $reflection->reasoning ?? 'Approval required.');

                    return $this->fail(
                        ctx: $ctx,
                        trace: $trace,
                        error: 'Human-in-the-loop required. Agent paused for approval.',
                    );

                case ReflectionAction::Abort:
                    return $this->fail(
                        ctx: $ctx,
                        trace: $trace,
                        error: $reflection->reasoning ?? 'Agent aborted by reflection.',
                    );
            }
        }

        // ── FINALIZATION ──
        $result = new AgentResult(
            status: AgentStatus::Completed,
            output: $finalOutput,
            totalSteps: $ctx->getStepCount(),
            elapsedMs: $ctx->getElapsedMs(),
            totalCost: $ctx->getTotalCost(),
            trace: $trace,
            metadata: $ctx->all(),
        );

        // Emit AgentCompleted
        $this->eventBus?->emit('agent.completed', $this, new AgentCompleted(
            agentName: $this->getName(),
            output: $finalOutput,
            totalSteps: $result->totalSteps,
            elapsedMs: $result->elapsedMs,
            totalCost: $result->totalCost,
        ));

        return $result;
    }

    /**
     * Executes a plan step through the middleware chain.
     *
     * Builds an onion middleware pipeline using array_reduce + array_reverse,
     * following the same pattern as nexus-ai's Pipeline.
     *
     * @param AgentContext $ctx The current context.
     * @param PlanStep $step The step to execute.
     * @return AgentContext Updated context after execution.
     */
    private function executeWithMiddleware(AgentContext $ctx, PlanStep $step): AgentContext
    {
        // The core handler: execute the step via the executor
        $core = function (AgentContext $context) use ($step): AgentContext {
            return $this->executor->execute($step, $context);
        };

        // Wrap in middleware layers (innermost first)
        foreach (array_reverse($this->middlewares) as $middleware) {
            $core = function (AgentContext $context) use ($middleware, $core): AgentContext {
                return $middleware->process($context, $core);
            };
        }

        return $core($ctx);
    }

    /**
     * Creates a failed AgentResult and emits AgentFailed event.
     *
     * @param AgentContext $ctx The current context.
     * @param array<int, mixed> $trace The execution trace.
     * @param string $error The error message.
     * @return AgentResult A failed result.
     */
    private function fail(AgentContext $ctx, array $trace, string $error): AgentResult
    {
        $result = new AgentResult(
            status: AgentStatus::Failed,
            output: $error,
            totalSteps: $ctx->getStepCount(),
            elapsedMs: $ctx->getElapsedMs(),
            totalCost: $ctx->getTotalCost(),
            trace: $trace,
            metadata: $ctx->all(),
        );

        // Emit AgentFailed
        $this->eventBus?->emit('agent.failed', $this, new AgentFailed(
            agentName: $this->getName(),
            error: $error,
            totalSteps: $result->totalSteps,
            elapsedMs: $result->elapsedMs,
        ));

        return $result;
    }
}
