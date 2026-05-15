<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Enum;

/**
 * Identifies the type of memory for storage and TTL.
 *
 * Three distinct memory concerns with different lifetimes:
 * - Working: Current request context (volatile, per-step)
 * - Episodic: Conversation history (persistent across sessions)
 * - Semantic: Facts and knowledge (long-term, future vector store)
 *
 * @see \Token27\NexusAI\Agents\Contract\MemoryInterface
 * @see \Token27\NexusAI\Agents\Memory\MemoryManager
 */
enum MemoryType: string
{
    /** Current request/step data. Array in memory. */
    case Working = 'working';

    /** Session history. File/DB backed. */
    case Episodic = 'episodic';

    /** Long-term knowledge. Vector store in future. */
    case Semantic = 'semantic';
}
