<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\AbstractWebSocketHandler;
use AlibabaCloud\Dara\DefaultWebSocketClient;
use AlibabaCloud\Dara\Request;
use AlibabaCloud\Dara\WebSocketConnector;
use AlibabaCloud\Dara\WebSocketSessionInfo;
use AlibabaCloud\Dara\WebSocketUtil;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Factory;

class WebSocketExtraTest extends TestCase
{
    public function testWebSocketUtilBranches()
    {
        $handler = new ExtraHandler();
        $object = new \stdClass();
        $object->webSocketPingInterval = 5;
        $object->webSocketPongTimeout = 6;
        $object->webSocketEnableReconnect = true;
        $object->webSocketReconnectInterval = 7;
        $object->webSocketMaxReconnectTimes = 8;
        $object->webSocketWriteTimeout = 9;
        $object->webSocketHandshakeTimeout = 10;
        $object->webSocketHandler = $handler;

        $this->assertEquals(5, WebSocketUtil::getWebSocketPingInterval($object));
        $this->assertEquals(6, WebSocketUtil::getWebSocketPongTimeout($object));
        $this->assertTrue(WebSocketUtil::getWebSocketEnableReconnect($object));
        $this->assertEquals(7, WebSocketUtil::getWebSocketReconnectInterval($object));
        $this->assertEquals(8, WebSocketUtil::getWebSocketMaxReconnectTimes($object));
        $this->assertEquals(9, WebSocketUtil::getWebSocketWriteTimeout($object));
        $this->assertEquals(10, WebSocketUtil::getWebSocketHandshakeTimeout($object));
        $this->assertSame($handler, WebSocketUtil::getWebSocketHandler($object));
        $this->assertSame($handler, WebSocketUtil::getWebSocketHandler(['webSocketHandler' => $handler]));
        $this->assertNull(WebSocketUtil::getWebSocketHandler(['webSocketHandler' => 'no']));
        $this->assertNull(WebSocketUtil::getWebSocketHandler(null));
        $this->assertNull(WebSocketUtil::getWebSocketPingInterval(null));
        $this->assertNull(WebSocketUtil::getWebSocketPingInterval([]));
        $this->assertNull(WebSocketUtil::getWebSocketPingInterval(new \stdClass()));

        $response = WebSocketUtil::newWebSocketResponse(101, 'Switching Protocols', [
            'X-Test' => 'value',
        ]);
        $this->assertEquals(101, $response->statusCode);
        $this->assertEquals('Switching Protocols', $response->statusMessage);
        $this->assertEquals(['value'], $response->headers['x-test']);

        $request = new Request();
        $request->protocol = 'HTTPS';
        $request->pathname = '/chat?existing=1';
        $request->headers = ['host' => 'example.com'];
        $request->query = ['b' => 'c'];
        $url = WebSocketUtil::buildWebSocketURL($request);
        $this->assertSame('wss://example.com/chat?existing=1&b=c', $url);

        $request->protocol = '';
        $request->pathname = '';
        $request->query = [];
        $this->assertSame('ws://example.com/', WebSocketUtil::buildWebSocketURL($request));

        $request->protocol = 'http';
        $this->assertSame('ws://example.com/', WebSocketUtil::buildWebSocketURL($request));

        try {
            WebSocketUtil::buildWebSocketURL(null);
            $this->fail('expected null request');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'request cannot be null'));
        }

        $bare = new Request();
        $bare->headers = [];
        try {
            WebSocketUtil::buildWebSocketURL($bare);
            $this->fail('expected missing domain');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'domain is required'));
        }

        try {
            WebSocketUtil::newWebSocketClientAndConnect($request, null);
            $this->fail('expected null runtime');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'runtimeObject'));
        }

        try {
            WebSocketUtil::newWebSocketClientAndConnect($request, []);
            $this->fail('expected missing handler');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'WebSocketHandler'));
        }

        $this->assertNotEmpty(WebSocketUtil::generateSessionID());
        $loop = Factory::create();
        $this->assertInstanceOf(WebSocketConnector::class, new WebSocketConnector($loop));
    }

    public function testClientGuardsAndSocketOptions()
    {
        $handler = new ExtraHandler();
        $client = new DefaultWebSocketClient($handler);

        try {
            new DefaultWebSocketClient(null);
            $this->fail('expected null handler');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'handler'));
        }

        try {
            $client->connect(null, []);
            $this->fail('expected null request');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'request'));
        }

        try {
            $client->connect(new Request(), null);
            $this->fail('expected null runtime');
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), 'runtimeObject'));
        }

        try {
            $client->sendText('x');
            $this->fail('expected not connected');
        } catch (\Exception $e) {
            $this->assertSame('not connected', $e->getMessage());
        }
        try {
            $client->sendBinary('x');
            $this->fail('expected not connected');
        } catch (\Exception $e) {
            $this->assertSame('not connected', $e->getMessage());
        }

        $this->setProp($client, 'state', 2);
        try {
            $client->sendText('x');
            $this->fail('expected nil connection');
        } catch (\Exception $e) {
            $this->assertSame('connection is nil', $e->getMessage());
        }
        try {
            $client->sendBinary('x');
            $this->fail('expected nil connection');
        } catch (\Exception $e) {
            $this->assertSame('connection is nil', $e->getMessage());
        }
        $this->setProp($client, 'state', 0);

        $client->pump();
        $client->disconnect();
        $client->close();

        $loop = Factory::create();
        $this->setProp($client, 'loop', $loop);
        $this->setProp($client, 'stopped', true);
        $client->pump(0);
        $this->setProp($client, 'stopped', false);
        $client->pump(0);

        $this->invoke($client, 'updateTimeoutConfig', [[
            'webSocketPingInterval' => 11,
            'webSocketReconnectInterval' => 12,
            'webSocketWriteTimeout' => 13,
            'readTimeout' => 14,
            'webSocketPongTimeout' => 15,
            'webSocketMaxReconnectTimes' => 2,
        ]]);
        $this->invoke($client, 'updateTimeoutConfig', [[
            'webSocketPingInterval' => 0,
            'webSocketReconnectInterval' => -1,
            'webSocketWriteTimeout' => null,
            'readTimeout' => 0,
            'webSocketPongTimeout' => 0,
            'webSocketMaxReconnectTimes' => 0,
        ]]);

        $request = new Request();
        $request->headers = [];
        $runtime = [
            'ignoreSSL' => true,
            'ca' => "-----BEGIN CERTIFICATE-----\nCA\n",
            'cert' => "-----BEGIN CERTIFICATE-----\nCERT\n",
            'key' => "-----BEGIN PRIVATE KEY-----\nKEY\n",
            'httpsProxy' => 'http://user:secret@127.0.0.1:9',
            'httpProxy' => 'http://127.0.0.1:9',
            'noProxy' => 'other.example',
        ];
        $connector = $this->invoke($client, 'buildSocketConnector', [
            $loop,
            $runtime,
            ['scheme' => 'wss', 'host' => 'example.com'],
            $request,
            1000,
        ]);
        $this->assertNotNull($connector);
        $this->assertSame('Basic ' . base64_encode('user:secret'), $request->headers['Proxy-Authorization']);

        $request->headers = null;
        $this->invoke($client, 'buildSocketConnector', [
            $loop,
            [
                'httpProxy' => 'http://user@127.0.0.1:9',
                'noProxy' => '',
            ],
            ['host' => 'example.com'],
            $request,
            1000,
        ]);
        $this->assertTrue(isset($request->headers['Proxy-Authorization']));

        $this->invoke($client, 'buildSocketConnector', [
            $loop,
            ['noProxy' => 'example.com', 'httpsProxy' => 'http://127.0.0.1:9'],
            ['scheme' => 'wss', 'host' => 'example.com'],
            $request,
            1000,
        ]);
        $this->invoke($client, 'buildSocketConnector', [
            $loop,
            ['socks5Proxy' => 'socks5://127.0.0.1:1080'],
            ['scheme' => 'ws', 'host' => 'example.com'],
            $request,
            1000,
        ]);

        foreach (glob(sys_get_temp_dir() . '/dara-ws-*') as $file) {
            @unlink($file);
        }
    }

    public function testReconnectGuardsAndPing()
    {
        $handler = new ExtraHandler();
        $client = new DefaultWebSocketClient($handler);

        $this->setProp($client, 'runtimeObject', ['webSocketEnableReconnect' => false]);
        $this->assertExceptionMessage($client, 'reconnect', [], 'reconnect is disabled');

        $this->setProp($client, 'runtimeObject', ['webSocketEnableReconnect' => '1']);
        $this->setProp($client, 'maxReconnectTimes', 2);
        $this->setProp($client, 'reconnectCount', 2);
        $this->assertExceptionMessage($client, 'reconnect', [], 'max reconnect times');

        $this->setProp($client, 'reconnectCount', 0);
        $this->setProp($client, 'maxReconnectTimes', 3);
        $this->setProp($client, 'runtimeObject', ['webSocketEnableReconnect' => 1]);
        $this->assertExceptionMessage($client, 'reconnectGracefully', [], 'graceful reconnection requires');

        $session = new WebSocketSessionInfo('sid-1');
        $request = new Request();
        $request->headers = null;
        $request->protocol = 'ws';
        $this->setProp($client, 'session', $session);
        $this->setProp($client, 'request', $request);
        $this->setProp($client, 'reconnectInterval', 1);
        $this->setProp($client, 'state', 0);
        $this->assertExceptionMessage($client, 'reconnectGracefully', [], 'domain is required');
        $this->assertEquals('sid-1', $request->headers['X-Acs-Ws-Session-Id']);

        $request->headers = ['X-Acs-Ws-Session-Id' => 'old'];
        $this->setProp($client, 'request', $request);
        $this->setProp($client, 'reconnectCount', 0);
        $this->assertExceptionMessage($client, 'reconnect', [], 'domain is required');
        $this->assertFalse(isset($request->headers['X-Acs-Ws-Session-Id']));

        $request->headers = null;
        $this->setProp($client, 'request', null);
        $this->setProp($client, 'session', $session);
        $this->setProp($client, 'reconnectCount', 0);
        $this->assertExceptionMessage($client, 'reconnect', [], 'request is nil');

        $loop = Factory::create();
        $this->invoke($client, 'startPingPong', []);
        $this->setProp($client, 'loop', $loop);
        $this->setProp($client, 'pingInterval', 0);
        $this->invoke($client, 'startPingPong', []);
        $this->invoke($client, 'stopPingPong', []);

        $conn = new FakeWebSocketConn();
        $this->setProp($client, 'conn', $conn);
        $this->setProp($client, 'pingInterval', 10);
        $this->setProp($client, 'pongTimeout', 10);
        $this->setProp($client, 'stopped', false);
        $this->setProp($client, 'pongReceived', false);
        $this->setProp($client, 'reconnecting', false);
        $this->setProp($client, 'session', new WebSocketSessionInfo('sid'));
        $this->setProp($client, 'request', null);
        $this->setProp($client, 'runtimeObject', ['webSocketEnableReconnect' => true]);
        $this->setProp($client, 'reconnectInterval', 1);
        $this->setProp($client, 'maxReconnectTimes', 1);
        $this->setProp($client, 'reconnectCount', 1);
        $this->setProp($client, 'state', 0);
        $this->invoke($client, 'startPingPong', []);
        $loop->addTimer(0.08, function () use ($loop) {
            $loop->stop();
        });
        $loop->run();
        $this->assertNotEmpty($conn->sent);
        $this->assertGreaterThanOrEqual(1, $handler->errors);

        $this->invoke($client, 'scheduleReconnect', [false]);
        $this->invoke($client, 'scheduleReconnect', [false]);

        $throwing = new ThrowingWebSocketConn();
        $loop = Factory::create();
        $this->setProp($client, 'loop', $loop);
        $this->setProp($client, 'conn', $throwing);
        $this->setProp($client, 'session', new WebSocketSessionInfo('sid'));
        $this->setProp($client, 'stopped', false);
        $this->setProp($client, 'pingInterval', 10);
        $this->setProp($client, 'runtimeObject', ['webSocketEnableReconnect' => false]);
        $this->invoke($client, 'startPingPong', []);
        $loop->addTimer(0.05, function () use ($loop) {
            $loop->stop();
        });
        $loop->run();
        $this->assertGreaterThanOrEqual(1, $handler->errors);

        $this->setProp($client, 'conn', $throwing);
        $this->setProp($client, 'loop', Factory::create());
        $this->setProp($client, 'session', new WebSocketSessionInfo('sid'));
        $this->setProp($client, 'state', 2);
        $this->invoke($client, 'cleanupConnection', [true]);
        $this->invoke($client, 'disconnectInternal', [1000, 'bye']);
        $this->assertGreaterThanOrEqual(1, $handler->closed);
    }

    private function assertExceptionMessage($client, $method, array $args, $needle)
    {
        try {
            call_user_func_array([$client, $method], $args);
            $this->fail('expected exception containing ' . $needle);
        } catch (\Exception $e) {
            $this->assertTrue(false !== strpos($e->getMessage(), $needle), $e->getMessage());
        }
    }

    private function setProp($object, $name, $value)
    {
        $property = new \ReflectionProperty('AlibabaCloud\\Dara\\DefaultWebSocketClient', $name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function invoke($object, $method, array $args)
    {
        $reflection = new \ReflectionMethod('AlibabaCloud\\Dara\\DefaultWebSocketClient', $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }
}

class ExtraHandler extends AbstractWebSocketHandler
{
    public $errors = 0;
    public $closed = 0;

    public function handleError($session, $err)
    {
        $this->errors++;
    }

    public function afterConnectionClosed($session, $code, $reason)
    {
        $this->closed++;
    }
}

class FakeWebSocketConn
{
    public $sent = [];

    public function send($data)
    {
        $this->sent[] = $data;
    }

    public function close()
    {
    }
}

class ThrowingWebSocketConn
{
    public function send($data)
    {
        throw new \Exception('send failed');
    }

    public function close()
    {
        throw new \Exception('close failed');
    }
}
