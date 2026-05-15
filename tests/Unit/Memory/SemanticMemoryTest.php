<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Memory;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Memory\SemanticMemory;

final class SemanticMemoryTest extends TestCase
{
    public function test_search_finds_string(): void
    {
        $memory = new SemanticMemory();
        $memory->store('fact_1', 'The sky is blue');
        $memory->store('fact_2', 'Grass is green');
        $memory->store('fact_3', 'Water is wet');

        $results = $memory->search('blue');

        $this->assertCount(1, $results);
        $this->assertSame('The sky is blue', $results['fact_1']);
    }

    public function test_search_case_insensitive(): void
    {
        $memory = new SemanticMemory();
        $memory->store('fact', 'Python is a programming language');

        $results = $memory->search('PYTHON');

        $this->assertCount(1, $results);
    }

    public function test_search_limit(): void
    {
        $memory = new SemanticMemory();
        $memory->store('a', 'apple');
        $memory->store('b', 'apple pie');
        $memory->store('c', 'apple sauce');
        $memory->store('d', 'apple juice');

        $results = $memory->search('apple', limit: 2);

        $this->assertCount(2, $results);
    }

    public function test_search_no_results(): void
    {
        $memory = new SemanticMemory();

        $results = $memory->search('nonexistent');

        $this->assertSame([], $results);
    }
}
