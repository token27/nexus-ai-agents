<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Memory;

use Token27\NexusAI\Agents\Contract\MemoryInterface;

/**
 * Long-term knowledge and facts memory.
 *
 * Stores persistent knowledge that doesn't change between sessions.
 * Supports keyword-based search for retrieving relevant facts.
 *
 * TTL: long-term (permanent knowledge).
 * Backend: InMemoryStore in v1.0, vector store in future (nexus-ai-rag).
 *
 * @see \Token27\NexusAI\Agents\Enum\MemoryType::Semantic
 * @see \Token27\NexusAI\Agents\Memory\MemoryManager
 */
final class SemanticMemory implements MemoryInterface
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

    /**
     * Searches stored facts by keyword matching.
     *
     * Uses case-insensitive substring matching via stripos().
     * In future versions (nexus-ai-rag), this will use vector similarity.
     *
     * @param string $query The search query.
     * @param int $limit Maximum number of results.
     * @return array<string, mixed> Matching entries.
     */
    public function search(string $query, int $limit = 5): array
    {
        $results = [];

        foreach ($this->store->all() as $key => $value) {
            if (is_string($value) && stripos($value, $query) !== false) {
                $results[$key] = $value;
            } elseif (is_array($value) && stripos(json_encode($value, JSON_THROW_ON_ERROR), $query) !== false) {
                $results[$key] = $value;
            }

            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }
}
