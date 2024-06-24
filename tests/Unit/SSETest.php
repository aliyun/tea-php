<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Util\StreamUtil;
use AlibabaCloud\Dara\Models\SSEEvent;
use AlibabaCloud\Dara\Dara;
use AlibabaCloud\Dara\Request;
// use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 * @method void setUpBeforeClass()
 * @method void tearDownAfterClass()
 */
class SSETest extends TestCase
{
    /**
     * @var resource
     */
    private $pid = 0;

    /**
     * @before
     */
    protected function initialize()
    {
        $server = dirname(__DIR__) . \DIRECTORY_SEPARATOR . 'Mock' . \DIRECTORY_SEPARATOR . 'SSEServer.php';
        $command = "php -S localhost:8000 $server > /dev/null 2>&1 & echo $!";
        // $command = "php -S localhost:8000 $server";
        $output = shell_exec($command);
        $this->pid = (int)trim($output);
        sleep(1);
    }

    /**
     * @after
     */
    protected function cleanup()
    {
        shell_exec('kill '.$this->pid);
    }

    public function testSSE()
    {

        $request = new Request();
        $request->method = 'GET';
        $request->protocol = 'http';
        $request->headers['host'] = 'localhost:8000';
        $request->headers['Accept'] = 'text/event-stream';
        $request->pathname = '/sse';
        
        $response = Dara::send($request, [
            'stream' => true,
        ]);
        $ret = [];


        foreach(StreamUtil::readAsSSE($response->getBody()) as $index => $event) {
            $ret[] = $event;
            self::assertTrue($event instanceof SSEEvent);
            self::assertEquals($event->data, '{"count":' . $index .'}');
            self::assertEquals($event->event, 'flow');
            self::assertEquals($event->id, 'sse-test');
            self::assertEquals($event->retry, 3000);
        }

        self::assertEquals(\count($ret), 5);
    }

    public function testSSEWithoutSpace()
    {

        $request = new Request();
        $request->method = 'GET';
        $request->protocol = 'http';
        $request->headers['host'] = 'localhost:8000';
        $request->headers['Accept'] = 'text/event-stream';
        $request->pathname = '/sse_with_no_spaces';
        
        $response = Dara::send($request, [
            'stream' => true,
        ]);
        $ret = [];


        foreach(StreamUtil::readAsSSE($response->getBody()) as $index => $event) {
            $ret[] = $event;
            self::assertTrue($event instanceof SSEEvent);
            self::assertEquals($event->data, '{"count":' . $index .'}');
            self::assertEquals($event->event, 'flow');
            self::assertEquals($event->id, 'sse-test');
            self::assertEquals($event->retry, 3000);
        }

        self::assertEquals(\count($ret), 5);
    }

    public function testSSEWithInvalidRetry()
    {

        $request = new Request();
        $request->method = 'GET';
        $request->protocol = 'http';
        $request->headers['host'] = 'localhost:8000';
        $request->headers['Accept'] = 'text/event-stream';
        $request->pathname = '/sse_invalid_retry';
        
        $response = Dara::send($request, [
            'stream' => true,
        ]);
        $ret = [];


        foreach(StreamUtil::readAsSSE($response->getBody()) as $index => $event) {
            $ret[] = $event;
            self::assertTrue($event instanceof SSEEvent);
            self::assertEquals($event->data, '{"count":' . $index .'}');
            self::assertEquals($event->event, 'flow');
            self::assertEquals($event->id, 'sse-test');
            self::assertEquals($event->retry, null);
        }

        self::assertEquals(\count($ret), 5);
    }

    public function testSSEWithDivided()
    {

        $request = new Request();
        $request->method = 'GET';
        $request->protocol = 'http';
        $request->headers['host'] = 'localhost:8000';
        $request->headers['Accept'] = 'text/event-stream';
        $request->pathname = '/sse_with_data_divided';
        
        $response = Dara::send($request, [
            'stream' => true,
        ]);
        $ret = [];


        foreach(StreamUtil::readAsSSE($response->getBody()) as $index => $event) {
            $ret[] = $event;
            self::assertTrue($event instanceof SSEEvent);
            if($index === 1) {
                self::assertEquals($event->data, '{"count":2,"tag":"divided"}');
            } elseif($index > 1) {
                self::assertEquals($event->data, '{"count":' . ($index + 1) .'}');
            } else {
                self::assertEquals($event->data, '{"count":' . $index .'}');
            }
            
            self::assertEquals($event->event, 'flow');
            self::assertEquals($event->id, 'sse-test');
            self::assertEquals($event->retry, 3000);
        }

        self::assertEquals(\count($ret), 4);
    }
}