<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Memory;

use Token27\NexusAI\Agents\Contract\MemoryInterface;

/**
 * Conversation history memory — persists across agent sessions.
 *
 * Stores past interactions and results. Evicts oldest entries
 * (FIFO) when exceeding the configured maximum.
 *
 * TTL: medium (multiple sessions).
 * Backend: InMemoryStore (v1.0), file/DB in future.
 *
 * @see \Token27\NexusAI\Agents\Enum\MemoryType::Episodic
 * @see \Token27\NexusAI\Agents\Memory\MemoryManager
 */
final class EpisodicMemory implements MemoryInterface
{
    /** @var InMemoryStore The underlying storage. */
    private readonly InMemoryStore $store;

    /**
     * @param int $maxEntries Maximum number of entries before FIFO eviction.
     */
    public function __construct(
        private readonly int $maxEntries = 100,
    ) {
        $this->store = new InMemoryStore();
    }

    public function store(string $key, mixed $value): void
    {
        $this->store->store($key, $value);

        // Evict oldest entry if over limit
        $all = $this->store->all();
        if (count($all) > $this->maxEntries) {
            $firstKey = array_key_first($all);
            $this->store->delete($firstKey);
        }
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

    /**
     * Returns the most recent N entries.
     *
     * @param int $limit Maximum number of entries to return.
     * @return array<string, mixed> The most recent entries.
     */
    public function getEntries(int $limit = 10): array
    {
        $all = $this->store->all();

        return array_slice($all, -$limit, $limit, true);
    }
}
