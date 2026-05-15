<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Memory\InMemoryStore;

final class InMemoryStoreTest extends TestCase
{
    public function test_store_and_retrieve(): void
    {
        $store = new InMemoryStore();
        $store->store('key', 'value');

        $this->assertSame('value', $store->retrieve('key'));
    }

    public function test_retrieve_default(): void
    {
        $store = new InMemoryStore();

        $this->assertNull($store->retrieve('missing'));
        $this->assertSame('default', $store->retrieve('missing', 'default'));
    }

    public function test_has(): void
    {
        $store = new InMemoryStore();
        $store->store('exists', true);

        $this->assertTrue($store->has('exists'));
        $this->assertFalse($store->has('nope'));
    }

    public function test_delete(): void
    {
        $store = new InMemoryStore();
        $store->store('key', 'value');
        $store->delete('key');

        $this->assertFalse($store->has('key'));
    }

    public function test_clear(): void
    {
        $store = new InMemoryStore();
        $store->store('a', 1);
        $store->store('b', 2);
        $store->clear();

        $this->assertSame([], $store->all());
    }

    public function test_all(): void
    {
        $store = new InMemoryStore();
        $store->store('a', 1);
        $store->store('b', 2);

        $this->assertSame(['a' => 1, 'b' => 2], $store->all());
    }
}
