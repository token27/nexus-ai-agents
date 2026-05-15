<?php

declare(strict_types=1);

namespace Token27\NexusAI\Agents\Tests\Unit\MultiAgent;

use PHPUnit\Framework\TestCase;
use Token27\NexusAI\Agents\MultiAgent\MessageBus;

final class MessageBusTest extends TestCase
{
    public function test_send_and_receive(): void
    {
        $bus = new MessageBus();
        $bus->send('AgentA', 'AgentB', 'Hello!');

        $this->assertTrue($bus->hasMessages('AgentB'));

        $messages = $bus->receive('AgentB');

        $this->assertCount(1, $messages);
        $this->assertSame('AgentA', $messages[0]['from']);
        $this->assertSame('Hello!', $messages[0]['content']);
    }

    public function test_receive_clears_messages(): void
    {
        $bus = new MessageBus();
        $bus->send('A', 'B', 'Msg');

        $bus->receive('B');

        $this->assertFalse($bus->hasMessages('B'));
    }

    public function test_subscriber_notified(): void
    {
        $bus = new MessageBus();
        $received = [];

        $bus->subscribe(function (string $from, string $to, string $content) use (&$received): void {
            $received[] = compact('from', 'to', 'content');
        });

        $bus->send('Agent1', 'Agent2', 'Test');

        $this->assertCount(1, $received);
        $this->assertSame('Agent1', $received[0]['from']);
    }

    public function test_no_messages_for_unknown_agent(): void
    {
        $bus = new MessageBus();

        $this->assertFalse($bus->hasMessages('Unknown'));
        $this->assertSame([], $bus->receive('Unknown'));
    }
}
