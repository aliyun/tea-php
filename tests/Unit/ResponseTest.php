<?php
namespace AlibabaCloud\Tea\Tests\Unit;
use GuzzleHttp\Psr7\Response as PsrResponse;
use AlibabaCloud\Tea\Response;
use PHPUnit\Framework\TestCase;
/**
 * Class ResponseTest.
 *
 * @internal
 * @coversNothing
 */
class ResponseTest extends TestCase {
	public static function testResponseInit() {
		$rawResponse = new PsrResponse(200, [
		            'host' => 'www.alibaba.com'
		        ], '{"AppId":"test", "ClassId":"test", "UserId":123}', '1.1', 'for test');
		$response = new Response($rawResponse);
		self::assertEquals(200, $response->statusCode);
		self::assertEquals('www.alibaba.com', $response->headers['host'][0]);
		self::assertEquals('{"AppId":"test", "ClassId":"test", "UserId":123}', $response->__toString());
	}
	public static function testResponseDot() {
		$rawResponse = new PsrResponse(200, [
		            'host' => 'www.alibaba.com'
		        ], '{"AppId":"test", "ClassId":"test", "UserId":123}', '1.1', 'for test');
		$response = new Response($rawResponse);
		self::assertEquals('test', $response->__get('AppId'));
		$response->add('ProductId', 'pop');
		self::assertTrue($response->__isset('ProductId'));
		$response->__set('ProductId', 'ecs');
		self::assertEquals('ecs', $response->__get('ProductId'));
		self::assertEquals('ecs', $response->get('ProductId'));
		$response->__unset('ProductId');
		self::assertFalse($response->__isset('ProductId'));
		self::assertFalse($response->has('ProductId'));
		self::assertEquals([
		            'AppId' => 'test',
		            'ClassId' => 'test',
		            'UserId' => 123
		        ], $response->toArray());
		self::assertEquals([
		            'AppId' => 'test',
		            'ClassId' => 'test',
		            'UserId' => 123
		        ], $response->all());
		self::assertEquals([
		            'AppId' => 'test',
		            'ClassId' => 'test',
		            'UserId' => 123
		        ], $response->flatten());
		$response->clear('ClassId');
		self::assertEquals([], $response->get('ClassId'));
		$response->delete('UserId');
		self::assertFalse($response->__isset('UserId'));
		self::assertFalse($response->isEmpty());
		$response->merge('AppId', ['test2', 'test1']);
		self::assertEquals([0 => 'test', 1 => 'test2', 2 => 'test1'], $response->get('AppId'));
		$response->mergeRecursiveDistinct('AppId', ['test3']);
		self::assertEquals([0 => 'test3', 1 => 'test2', 2 => 'test1'], $response->get('AppId'));
		self::assertEquals('pop', $response->pull('ProductId', 'pop'));
		$response->push('ProductId', 'pop');
		self::assertEquals([0 => 'pop'], $response->get('ProductId'));
		$response->replace('ProductId', 'ecs');
		self::assertEquals([0 => 'ecs'], $response->pull('ProductId', 'pop'));
		$response->setArray([
		            'host' => 'www.alibaba.com'
		        ]);
		self::assertEquals('{"host":"www.alibaba.com"}', $response->toJson());
		self::assertEmpty($response->offsetExists('ProductId'));
		self::assertEquals('www.alibaba.com', $response->offsetGet('host'));
		$response->offsetSet('test', 'test');
		self::assertEquals('test', $response->offsetGet('test'));
		$response->offsetUnset('test');
		self::assertEquals(1, $response->count());
	}
}