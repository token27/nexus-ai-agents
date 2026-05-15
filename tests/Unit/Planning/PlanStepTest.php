<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\Planning;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Planning\PlanStep;

final class PlanStepTest extends TestCase
{
    public function test_creation(): void
    {
        $step = new PlanStep(description: 'Do something', action: 'act');

        $this->assertSame('Do something', $step->description);
        $this->assertSame('act', $step->action);
        $this->assertSame([], $step->arguments);
        $this->assertSame('', $step->expectedOutput);
    }

    public function test_default_action_is_think(): void
    {
        $step = new PlanStep(description: 'Think about it');

        $this->assertSame('think', $step->action);
    }

    public function test_to_array(): void
    {
        $step = new PlanStep(
            description: 'Execute task',
            action: 'act',
            arguments: ['param' => 'value'],
            expectedOutput: 'Result',
        );

        $array = $step->toArray();

        $this->assertSame('Execute task', $array['description']);
        $this->assertSame('act', $array['action']);
        $this->assertSame(['param' => 'value'], $array['arguments']);
        $this->assertSame('Result', $array['expectedOutput']);
    }
}
