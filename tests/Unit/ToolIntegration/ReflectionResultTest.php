<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\ToolIntegration;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\Enum\ReflectionAction;
use Token27\NexusAI\Agents\Reflection\ReflectionResult;

final class ReflectionResultTest extends TestCase
{
    public function test_continue(): void
    {
        $result = ReflectionResult::continue(reasoning: 'Proceed');

        $this->assertSame(ReflectionAction::Continue, $result->action);
        $this->assertSame('Proceed', $result->reasoning);
    }

    public function test_finish(): void
    {
        $result = ReflectionResult::finish();

        $this->assertSame(ReflectionAction::Finish, $result->action);
        $this->assertNull($result->reasoning);
    }

    public function test_replan(): void
    {
        $result = ReflectionResult::replan(reasoning: 'Plan invalid');

        $this->assertSame(ReflectionAction::Replan, $result->action);
    }

    public function test_abort(): void
    {
        $result = ReflectionResult::abort(reasoning: 'Cannot complete');

        $this->assertSame(ReflectionAction::Abort, $result->action);
        $this->assertSame('Cannot complete', $result->reasoning);
    }
}
