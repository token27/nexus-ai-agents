<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Contract;

/**
 * Unified interface for the three types of agent memory.
 *
 * Working, Episodic, and Semantic memory all implement a common
 * key-value store contract. The TTL and eviction strategy differ
 * per implementation, but the CRUD interface is shared.
 *
 * Inspired by PSR-16 SimpleCache, simplified for agent memory needs.
 *
 * @see \Token27\NexusAI\Agents\Enum\MemoryType
 * @see \Token27\NexusAI\Agents\Memory\InMemoryStore
 */
interface MemoryInterface
{
    /**
     * Stores a value under the given key.
     *
     * @param string $key The storage key.
     * @param mixed $value The value to store.
     */
    public function store(string $key, mixed $value): void;

    /**
     * Retrieves a value by key.
     *
     * @param string $key The storage key.
     * @param mixed $default Default value if key doesn't exist.
     * @return mixed The stored value or default.
     */
    public function retrieve(string $key, mixed $default = null): mixed;

    /**
     * Checks if a key exists in memory.
     *
     * @param string $key The storage key.
     * @return bool True if the key exists.
     */
    public function has(string $key): bool;

    /**
     * Removes a key from memory.
     *
     * @param string $key The storage key.
     */
    public function delete(string $key): void;

    /**
     * Removes all entries from memory.
     */
    public function clear(): void;

    /**
     * Returns all stored entries.
     *
     * @return array<string, mixed> All key-value pairs.
     */
    public function all(): array;
}
