<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Memory;

/**
 * Orchestrates the three types of agent memory.
 *
 * Builds context strings for injection into prompts by combining
 * working memory with recent episodic memory entries. Manages
 * trimming and cleanup across memory types.
 *
 * @see \Token27\NexusAI\Agents\Memory\WorkingMemory
 * @see \Token27\NexusAI\Agents\Memory\EpisodicMemory
 * @see \Token27\NexusAI\Agents\Memory\SemanticMemory
 */
final class MemoryManager
{
    /**
     * @param WorkingMemory $working Short-term volatile memory.
     * @param EpisodicMemory $episodic Conversation history.
     * @param SemanticMemory $semantic Long-term knowledge.
     * @param MemoryTrimmer $trimmer Token budget trimmer.
     */
    public function __construct(
        public readonly WorkingMemory $working,
        public readonly EpisodicMemory $episodic,
        public readonly SemanticMemory $semantic,
        public readonly MemoryTrimmer $trimmer = new MemoryTrimmer(),
    ) {
    }

    /**
     * Builds a context string for prompt injection.
     *
     * Combines working memory data with the last 5 episodic memory
     * entries, formatted as a readable context block.
     *
     * @return string The combined memory context.
     */
    public function buildContext(): string
    {
        $parts = [];

        // Working memory (current context)
        $workingData = $this->working->all();
        if ($workingData !== []) {
            $parts[] = "Working Memory:\n" . json_encode($workingData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        }

        // Recent episodic memory (conversation history)
        $recentEpisodes = $this->episodic->getEntries(5);
        if ($recentEpisodes !== []) {
            $parts[] = "Recent History:\n" . json_encode($recentEpisodes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Resets working memory, keeping episodic and semantic intact.
     */
    public function resetWorking(): void
    {
        $this->working->clear();
    }

    /**
     * Trims working memory using the configured trimmer.
     */
    public function trimWorking(): void
    {
        $this->trimmer->trim($this->working);
    }
}
