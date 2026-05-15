<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Memory;

use Token27\NexusAI\Agents\Contract\MemoryInterface;

/**
 * Volatile in-memory key-value store implementing MemoryInterface.
 *
 * Stores data in a PHP array. All data is lost when the process ends.
 * Serves as the default backend for all three memory types in v1.0.
 *
 * @see \Token27\NexusAI\Agents\Contract\MemoryInterface
 * @see \Token27\NexusAI\Agents\Memory\WorkingMemory
 */
final class InMemoryStore implements MemoryInterface
{
    /** @var array<string, mixed> The storage array. */
    private array $data = [];

    public function store(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function retrieve(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function all(): array
    {
        return $this->data;
    }
}
