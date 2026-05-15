<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Memory\WorkingMemory;

final class WorkingMemoryTest extends TestCase
{
    public function test_store_and_retrieve(): void
    {
        $memory = new WorkingMemory();
        $memory->store('key', 'value');

        $this->assertSame('value', $memory->retrieve('key'));
    }

    public function test_clear(): void
    {
        $memory = new WorkingMemory();
        $memory->store('a', 1);
        $memory->store('b', 2);
        $memory->clear();

        $this->assertSame([], $memory->all());
    }

    public function test_delete(): void
    {
        $memory = new WorkingMemory();
        $memory->store('key', 'value');
        $memory->delete('key');

        $this->assertFalse($memory->has('key'));
    }

    public function test_all(): void
    {
        $memory = new WorkingMemory();
        $memory->store('a', 1);

        $this->assertSame(['a' => 1], $memory->all());
    }
}
