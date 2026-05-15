<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Memory\MemoryTrimmer;
use Token27\NexusAI\Agents\Memory\WorkingMemory;

final class MemoryTrimmerTest extends TestCase
{
    public function test_trim_below_threshold(): void
    {
        $memory = new WorkingMemory();
        $memory->store('key', 'small value');

        $trimmer = new MemoryTrimmer(maxTokens: 1000);
        $trimmer->trim($memory);

        // Should still have the data
        $this->assertTrue($memory->has('key'));
    }

    public function test_trim_exceeding_threshold(): void
    {
        $memory = new WorkingMemory();

        // Fill with large data
        for ($i = 0; $i < 100; $i++) {
            $memory->store("key_{$i}", str_repeat('data ', 50));
        }

        $trimmer = new MemoryTrimmer(maxTokens: 100);
        $trimmer->trim($memory);

        $all = $memory->all();

        // Should have fewer entries than original
        $this->assertLessThan(100, count($all));
    }

    public function test_trim_empty_memory(): void
    {
        $memory = new WorkingMemory();
        $trimmer = new MemoryTrimmer();

        $trimmer->trim($memory);

        $this->assertSame([], $memory->all());
    }
}
