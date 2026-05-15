<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Memory\EpisodicMemory;

final class EpisodicMemoryTest extends TestCase
{
    public function test_store_and_retrieve(): void
    {
        $memory = new EpisodicMemory();
        $memory->store('session_1', 'Conversation about weather');

        $this->assertSame('Conversation about weather', $memory->retrieve('session_1'));
    }

    public function test_fifo_eviction(): void
    {
        $memory = new EpisodicMemory(maxEntries: 3);

        $memory->store('a', 1);
        $memory->store('b', 2);
        $memory->store('c', 3);
        $memory->store('d', 4);

        $this->assertFalse($memory->has('a')); // Evicted
        $this->assertTrue($memory->has('b'));
        $this->assertTrue($memory->has('c'));
        $this->assertTrue($memory->has('d'));
    }

    public function test_get_entries(): void
    {
        $memory = new EpisodicMemory();
        $memory->store('a', 1);
        $memory->store('b', 2);
        $memory->store('c', 3);

        $entries = $memory->getEntries(2);

        $this->assertCount(2, $entries);
    }
}
