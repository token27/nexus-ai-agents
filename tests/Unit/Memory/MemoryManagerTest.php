<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Memory\EpisodicMemory;
use Token27\NexusAI\Agents\Memory\MemoryManager;
use Token27\NexusAI\Agents\Memory\SemanticMemory;
use Token27\NexusAI\Agents\Memory\WorkingMemory;

final class MemoryManagerTest extends TestCase
{
    public function test_build_context_empty(): void
    {
        $manager = new MemoryManager(
            working: new WorkingMemory(),
            episodic: new EpisodicMemory(),
            semantic: new SemanticMemory(),
        );

        $context = $manager->buildContext();

        $this->assertSame('', $context);
    }

    public function test_build_context_with_working_memory(): void
    {
        $working = new WorkingMemory();
        $working->store('task', 'Write report');

        $manager = new MemoryManager(
            working: $working,
            episodic: new EpisodicMemory(),
            semantic: new SemanticMemory(),
        );

        $context = $manager->buildContext();

        $this->assertStringContainsString('Working Memory', $context);
        $this->assertStringContainsString('Write report', $context);
    }

    public function test_reset_working(): void
    {
        $working = new WorkingMemory();
        $working->store('temp', 'data');

        $manager = new MemoryManager(
            working: $working,
            episodic: new EpisodicMemory(),
            semantic: new SemanticMemory(),
        );

        $manager->resetWorking();

        $this->assertSame([], $working->all());
    }
}
