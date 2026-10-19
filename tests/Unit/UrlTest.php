<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testParseAndEmptyAccessors()
    {
        $url = Url::parse('https://user:pass@example.com:443/a?b=1#h');
        $this->assertNull($url->path());
        $this->assertSame('', $url->pathname());
        $this->assertSame('', $url->protocol());
        $this->assertSame('', $url->hostname());
        $this->assertNull($url->host());
        $this->assertSame('', $url->port());
        $this->assertSame('', $url->hash());
        $this->assertSame('', $url->search());
        $this->assertSame('', $url->auth());
        $this->silence(function () use ($url) {
            $this->assertNull($url->href());
        });
    }

    public function testPopulatedAccessors()
    {
        $url = new Url('https://example.com/a');
        $this->setProp($url, 'path', '/already');
        $this->assertSame('?', $url->path());

        $this->setProp($url, 'host', 'example.com');
        $this->assertSame('', $url->host());

        $this->silence(function () use ($url) {
            $this->setProp($url, 'pathname', '/a');
            $this->setProp($url, 'protocol', 'https');
            $this->setProp($url, 'hostname', 'example.com');
            $this->setProp($url, 'port', '443');
            $this->setProp($url, 'hash', 'top');
            $this->setProp($url, 'search', 'a=1');
            $this->setProp($url, 'auth', 'user:pass');
            $url->pathname();
            $url->protocol();
            $url->hostname();
            $url->port();
            $url->hash();
            $url->search();
            $url->auth();
        });
    }

    public function testEncodeHelpers()
    {
        $this->assertNull(Url::percentEncode(null));
        $this->assertSame('a%20b%2A~', Url::percentEncode('a b*~'));

        $thrown = false;
        $this->silence(function () use (&$thrown) {
            try {
                Url::urlEncode('a b');
            } catch (\InvalidArgumentException $e) {
                $thrown = true;
            }
        });
        $this->assertTrue($thrown);

        $encoded = null;
        $this->silence(function () use (&$encoded) {
            $encoded = Url::pathEncode('/a b');
        });
        $this->assertNull($encoded);
    }

    private function setProp($object, $name, $value)
    {
        $property = new \ReflectionProperty('AlibabaCloud\\Dara\\Url', $name);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function silence($callback)
    {
        set_error_handler(function () {
            return true;
        });
        try {
            $callback();
        } finally {
            restore_error_handler();
        }
    }
}
