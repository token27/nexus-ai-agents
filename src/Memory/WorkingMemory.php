<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Memory;

use Token27\NexusAI\Agents\Contract\MemoryInterface;

/**
 * Short-term volatile memory for the current agent execution.
 *
 * Stores data relevant to the current step or session. Cleared
 * when the agent finishes execution. Backed by InMemoryStore.
 * TTL: current request only.
 *
 * @see \Token27\NexusAI\Agents\Enum\MemoryType::Working
 * @see \Token27\NexusAI\Agents\Memory\MemoryManager
 */
final class WorkingMemory implements MemoryInterface
{
    /** @var InMemoryStore The underlying storage. */
    private readonly InMemoryStore $store;

    public function __construct()
    {
        $this->store = new InMemoryStore();
    }

    public function store(string $key, mixed $value): void
    {
        $this->store->store($key, $value);
    }

    public function retrieve(string $key, mixed $default = null): mixed
    {
        return $this->store->retrieve($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->store->has($key);
    }

    public function delete(string $key): void
    {
        $this->store->delete($key);
    }

    public function clear(): void
    {
        $this->store->clear();
    }

    public function all(): array
    {
        return $this->store->all();
    }
}
