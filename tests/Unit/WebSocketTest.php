<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\AbstractWebSocketHandler;
use AlibabaCloud\Dara\DefaultWebSocketClient;
use AlibabaCloud\Dara\Request;
use AlibabaCloud\Dara\WebSocketMessage;
use AlibabaCloud\Dara\WebSocketMessageType;
use AlibabaCloud\Dara\WebSocketSessionInfo;
use AlibabaCloud\Dara\WebSocketUtil;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MockWebSocketHandler extends AbstractWebSocketHandler
{
    public $connectedCalled = false;
    public $messageReceivedCount = 0;
    public $errorCount = 0;
    public $closedCalled = false;
    /** @var WebSocketMessage|null */
    public $lastMessage;

    public function afterConnectionEstablished($session)
    {
        $this->connectedCalled = true;
    }

    public function handleRawMessage($session, $message)
    {
        $this->messageReceivedCount++;
        $this->lastMessage = $message;
    }

    public function handleError($session, $err)
    {
        $this->errorCount++;
    }

    public function afterConnectionClosed($session, $code, $reason)
    {
        $this->closedCalled = true;
    }
}

class ErrorOnConnectHandler extends MockWebSocketHandler
{
    public function afterConnectionEstablished($session)
    {
        throw new Exception('test error on connection');
    }
}

/**
 * @internal
 */
class WebSocketTest extends TestCase
{
    /** @var int */
    private $pid = 0;

    /** @var int */
    private $port = 18080;

    /**
     * @before
     */
    protected function startMockServer()
    {
        // Avoid `: void` / random_int() so PHP 5.6 / 7.0 CI matrix stays compatible.
        $this->port = 18080 + mt_rand(1, 1000);
        $server = dirname(__DIR__) . '/Mock/WebSocketServer.php';
        $command = sprintf('php %s %d > /dev/null 2>&1 & echo $!', escapeshellarg($server), $this->port);
        $output = shell_exec($command);
        $this->pid = (int) trim($output);
        usleep(300000);
    }

    /**
     * @after
     */
    protected function stopMockServer()
    {
        if ($this->pid > 0) {
            shell_exec('kill ' . $this->pid);
            $this->pid = 0;
        }
    }

    public function testWebSocketClientCreation()
    {
        $handler = new MockWebSocketHandler();
        $client = WebSocketUtil::newDefaultWebSocketClient($handler);
        self::assertNotNull($client);
        self::assertFalse($client->isConnected());

        $this->expectException(\InvalidArgumentException::class);
        WebSocketUtil::newDefaultWebSocketClient(null);
    }

    public function testWebSocketMessageTypes()
    {
        self::assertSame(0, WebSocketMessageType::TEXT);
        self::assertSame(1, WebSocketMessageType::BINARY);
    }

    public function testWebSocketSessionInfo()
    {
        $session = new WebSocketSessionInfo('test-session-123', '', microtime(true), '192.168.1.1:8080', '192.168.1.2:12345', []);
        self::assertSame('test-session-123', $session->sessionID);
        $session->attributes['key1'] = 'value1';
        $session->attributes['key2'] = 123;
        self::assertSame('value1', $session->attributes['key1']);
        self::assertSame(123, $session->attributes['key2']);
    }

    public function testMockHandler()
    {
        $handler = new MockWebSocketHandler();
        $session = new WebSocketSessionInfo('test', '', microtime(true), '', '', []);
        $handler->afterConnectionEstablished($session);
        self::assertTrue($handler->connectedCalled);

        $msg = new WebSocketMessage(WebSocketMessageType::TEXT, 'test message');
        $handler->handleRawMessage($session, $msg);
        $handler->handleRawMessage($session, $msg);
        self::assertSame(2, $handler->messageReceivedCount);
        self::assertSame('test message', $handler->lastMessage->payload);

        $handler->handleError($session, new Exception('deadline'));
        self::assertSame(1, $handler->errorCount);

        $handler->afterConnectionClosed($session, 1000, 'Normal');
        self::assertTrue($handler->closedCalled);
    }

    public function testConvertToWebSocketMessageType()
    {
        $tests = [
            ['text', WebSocketMessageType::TEXT],
            ['binary', WebSocketMessageType::BINARY],
            ['ping', WebSocketMessageType::PING],
            ['pong', WebSocketMessageType::PONG],
            ['close', WebSocketMessageType::CLOSE],
            ['unknown', WebSocketMessageType::BINARY],
        ];
        foreach ($tests as $test) {
            self::assertSame($test[1], WebSocketUtil::convertToWebSocketMessageType($test[0]));
        }
    }

    public function testDefaultWebSocketClientConnectSuccessful()
    {
        $handler = new MockWebSocketHandler();
        $client = WebSocketUtil::newDefaultWebSocketClient($handler);
        $request = new Request();
        $request->protocol = 'ws';
        $request->pathname = '/';
        $request->headers = ['host' => '127.0.0.1:' . $this->port];

        $runtimeObject = [
            'connectTimeout' => 5000,
            'readTimeout' => 30000,
            'webSocketPingInterval' => 0,
            'webSocketEnableReconnect' => false,
        ];

        $response = $client->connect($request, $runtimeObject);
        self::assertNotNull($response);
        self::assertTrue($client->isConnected());
        self::assertTrue($handler->connectedCalled);
        self::assertNotNull($client->getSessionInfo());
        self::assertNotEmpty($client->getSessionInfo()->sessionID);
        $client->disconnect();
    }

    public function testDefaultWebSocketClientInvalidUrl()
    {
        $handler = new MockWebSocketHandler();
        $client = WebSocketUtil::newDefaultWebSocketClient($handler);
        $request = new Request();
        $request->protocol = 'ws';
        $request->headers = [];

        $this->expectException(InvalidArgumentException::class);
        $client->connect($request, ['connectTimeout' => 5000]);
    }

    public function testDefaultWebSocketClientConnectionTimeout()
    {
        $handler = new MockWebSocketHandler();
        $client = WebSocketUtil::newDefaultWebSocketClient($handler);
        $request = new Request();
        $request->protocol = 'ws';
        $request->pathname = '/';
        $request->headers = ['host' => '127.0.0.1:59999'];

        $runtimeObject = [
            'connectTimeout' => 100,
            'webSocketHandshakeTimeout' => 100,
        ];

        $this->expectException(\Exception::class);
        $client->connect($request, $runtimeObject);
        self::assertFalse($handler->connectedCalled);
    }

    public function testDefaultWebSocketClientHandlerErrorOnConnect()
    {
        $handler = new ErrorOnConnectHandler();
        $client = WebSocketUtil::newDefaultWebSocketClient($handler);
        $request = new Request();
        $request->protocol = 'ws';
        $request->pathname = '/';
        $request->headers = ['host' => '127.0.0.1:' . $this->port];

        $this->expectException(\Exception::class);
        $client->connect($request, ['connectTimeout' => 5000, 'webSocketPingInterval' => 0]);
    }

    public function testWebSocketReconnectWhenAlreadyConnected()
    {
        $handler = new MockWebSocketHandler();
        $request = new Request();
        $request->protocol = 'ws';
        $request->pathname = '/';
        $request->headers = ['host' => '127.0.0.1:' . $this->port];

        $runtimeObject = [
            'webSocketEnableReconnect' => true,
            'webSocketMaxReconnectTimes' => 3,
            'webSocketReconnectInterval' => 1000,
            'webSocketHandshakeTimeout' => 5000,
            'webSocketPingInterval' => 0,
            'webSocketHandler' => $handler,
            'connectTimeout' => 5000,
        ];

        list($client) = WebSocketUtil::newWebSocketClientAndConnect($request, $runtimeObject);
        self::assertTrue($client->isConnected());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('already connected');
        $client->reconnect();
        $client->close();
    }

    public function testDefaultWebSocketClientReceivesMessagesInMainProcess()
    {
        $handler = new MockWebSocketHandler();
        $client = WebSocketUtil::newDefaultWebSocketClient($handler);
        $request = new Request();
        $request->protocol = 'ws';
        $request->pathname = '/';
        $request->headers = ['host' => '127.0.0.1:' . $this->port];

        $runtimeObject = [
            'connectTimeout' => 5000,
            'readTimeout' => 30000,
            'webSocketPingInterval' => 0,
            'webSocketEnableReconnect' => false,
        ];

        $client->connect($request, $runtimeObject);
        self::assertTrue($client->isConnected());

        $client->sendText('hello websocket');
        $client->pump(1000);

        self::assertSame(1, $handler->messageReceivedCount);
        self::assertSame('hello websocket', $handler->lastMessage->payload);
        $client->disconnect();
    }
}
