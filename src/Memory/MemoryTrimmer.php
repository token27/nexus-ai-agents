<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Memory;

/**
 * Trims working memory when token count exceeds a configurable limit.
 *
 * Estimates token count as strlen(json) / 4 (rough approximation
 * since ~4 chars ≈ 1 token for English text). Removes oldest
 * entries (FIFO) until under the limit.
 *
 * @see \Token27\NexusAI\Agents\Memory\WorkingMemory
 * @see \Token27\NexusAI\Agents\Memory\MemoryManager
 */
final readonly class MemoryTrimmer
{
    /** Characters-per-token estimation ratio. */
    private const CHARS_PER_TOKEN = 4;

    /**
     * @param int $maxTokens Maximum token budget before trimming.
     */
    public function __construct(
        private int $maxTokens = 8000,
    ) {
    }

    /**
     * Trims the given working memory if estimated tokens exceed limit.
     *
     * Removes the oldest entries first (FIFO eviction).
     *
     * @param WorkingMemory $memory The working memory to trim.
     */
    public function trim(WorkingMemory $memory): void
    {
        $all = $memory->all();

        if ($all === []) {
            return;
        }

        $estimatedTokens = $this->estimateTokens($all);

        if ($estimatedTokens <= $this->maxTokens) {
            return;
        }

        // Remove oldest entries until under budget
        $entries = $all;
        while ($this->estimateTokens($entries) > $this->maxTokens && $entries !== []) {
            array_shift($entries);
        }

        // Rebuild memory with trimmed entries
        $memory->clear();
        foreach ($entries as $key => $value) {
            $memory->store($key, $value);
        }
    }

    /**
     * Estimates token count from an array of data.
     *
     * Uses the rough heuristic: ~4 characters per token.
     *
     * @param array<string, mixed> $data The data to estimate.
     * @return int Estimated token count.
     */
    private function estimateTokens(array $data): int
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR);

        return (int) ceil(mb_strlen($json) / self::CHARS_PER_TOKEN);
    }
}
