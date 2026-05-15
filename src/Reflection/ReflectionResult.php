<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Reflection;

use Token27\NexusAI\Agents\Enum\ReflectionAction;

/**
 * Result of the reflection phase in the agent loop.
 *
 * Encapsulates the decision (what action to take next) and the
 * reasoning behind that decision. Returned by ReflectorInterface
 * and consumed by AbstractAgent to control the loop.
 *
 * @see \Token27\NexusAI\Agents\Enum\ReflectionAction
 * @see \Token27\NexusAI\Agents\Contract\ReflectorInterface
 */
final readonly class ReflectionResult
{
    /**
     * @param ReflectionAction $action The decided next action.
     * @param string|null $reasoning The reasoning behind the decision.
     * @param array<string, mixed> $metadata Additional metadata.
     */
    public function __construct(
        public ReflectionAction $action,
        public ?string $reasoning = null,
        public array $metadata = [],
    ) {
    }

    /**
     * Creates a Continue result.
     *
     * @param string|null $reasoning Optional reasoning.
     */
    public static function continue(?string $reasoning = null): self
    {
        return new self(action: ReflectionAction::Continue, reasoning: $reasoning);
    }

    /**
     * Creates a Finish result.
     *
     * @param string|null $reasoning Optional reasoning.
     */
    public static function finish(?string $reasoning = null): self
    {
        return new self(action: ReflectionAction::Finish, reasoning: $reasoning);
    }

    /**
     * Creates a Replan result.
     *
     * @param string|null $reasoning Optional reasoning.
     */
    public static function replan(?string $reasoning = null): self
    {
        return new self(action: ReflectionAction::Replan, reasoning: $reasoning);
    }

    /**
     * Creates an Abort result.
     *
     * @param string $reasoning Why the agent is aborting.
     */
    public static function abort(string $reasoning): self
    {
        return new self(action: ReflectionAction::Abort, reasoning: $reasoning);
    }
}
