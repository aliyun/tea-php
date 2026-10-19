<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Models\ExtendsParameters;
use AlibabaCloud\Dara\Models\FileField;
use AlibabaCloud\Dara\Models\RuntimeOptions;
use AlibabaCloud\Dara\Models\SSEEvent;
use PHPUnit\Framework\TestCase;

class RuntimeOptionsTest extends TestCase
{
    public function testToMapAndFromMap()
    {
        $extends = new ExtendsParameters([
            'headers' => ['k' => 'v'],
            'queries' => ['q' => '1'],
        ]);
        $this->assertEquals([
            'headers' => ['k' => 'v'],
            'queries' => ['q' => '1'],
        ], $extends->toMap());
        $this->assertEquals([], (new ExtendsParameters())->toMap());

        $fromExtends = ExtendsParameters::fromMap([
            'headers' => ['h' => '1'],
            'queries' => ['a' => 'b'],
        ]);
        $this->assertEquals('1', $fromExtends->headers['h']);
        $this->assertEquals([], ExtendsParameters::fromMap()->toMap());

        $options = new RuntimeOptions([
            'autoretry' => true,
            'ignoreSSL' => true,
            'key' => 'key',
            'cert' => 'cert',
            'ca' => 'ca',
            'maxAttempts' => 4,
            'backoffPolicy' => 'Fixed',
            'backoffPeriod' => 20,
            'readTimeout' => 1000,
            'connectTimeout' => 2000,
            'httpProxy' => 'http://proxy',
            'httpsProxy' => 'https://proxy',
            'noProxy' => 'localhost',
            'maxIdleConns' => 8,
            'localAddr' => '127.0.0.1',
            'socks5Proxy' => 'socks5://127.0.0.1',
            'socks5NetWork' => 'tcp',
            'keepAlive' => false,
            'extendsParameters' => $extends,
            'webSocketPingInterval' => 1,
            'webSocketPongTimeout' => 2,
            'webSocketEnableReconnect' => true,
            'webSocketReconnectInterval' => 3,
            'webSocketMaxReconnectTimes' => 4,
            'webSocketWriteTimeout' => 5,
            'webSocketHandshakeTimeout' => 6,
        ]);
        $options->validate();
        $map = $options->toMap();
        $this->assertEquals(true, $map['autoretry']);
        $this->assertEquals(4, $map['max_attempts']);
        $this->assertEquals('Fixed', $map['backoff_policy']);
        $this->assertEquals(20, $map['backoff_period']);
        $this->assertEquals('v', $map['extendsParameters']['headers']['k']);
        $this->assertEquals(6, $map['webSocketHandshakeTimeout']);

        $restored = RuntimeOptions::fromMap($map);
        $this->assertEquals(4, $restored->maxAttempts);
        $this->assertEquals('Fixed', $restored->backoffPolicy);
        $this->assertEquals('v', $restored->extendsParameters->headers['k']);
        $this->assertEquals(3, $restored->webSocketReconnectInterval);
        $this->assertEquals([], RuntimeOptions::fromMap()->toMap());
    }

    public function testFileFieldAndSseEvent()
    {
        $field = new FileField([
            'filename' => 'a.txt',
            'contentType' => 'text/plain',
            'content' => 'hi',
        ]);
        $this->assertEquals('a.txt', $field->filename);
        $field->validate();

        $event = new SSEEvent([
            'data' => 'payload',
            'id' => '1',
            'event' => 'ping',
            'retry' => 10,
        ]);
        $event->validate();
        $this->assertEquals([
            'data' => 'payload',
            'id' => '1',
            'event' => 'ping',
            'retry' => 10,
        ], $event->toMap());
        $this->assertEquals($event->toArray(), $event->toMap());
        $this->assertEquals([], (new SSEEvent())->toArray());

        $restored = @SSEEvent::fromMap([
            'data' => ['part' => 'x'],
            'id' => '9',
            'event' => 'flow',
            'retry' => 3,
        ]);
        $this->assertNull($restored);
        $this->assertNull(@SSEEvent::fromMap(['data' => []]));
        $this->assertNull(@SSEEvent::fromMap());
    }
}
