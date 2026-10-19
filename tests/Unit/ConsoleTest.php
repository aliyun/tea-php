<?php

namespace AlibabaCloud\Dara\Tests\Unit;

use AlibabaCloud\Dara\Util\Console;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\TestCase;

class ConsoleTest extends TestCase
{
    public function testLogLevels()
    {
        $logger = Console::logger();
        $handler = new TestHandler();
        Console::pushHandler($handler);

        Console::log('log-line');
        Console::info('info-line');
        Console::warning('warn-line');
        Console::debug('debug-line');
        Console::error('error-line');

        $this->assertSame($logger, Console::logger());
        $this->assertTrue($handler->hasInfoRecords() || $handler->hasDebugRecords() || $handler->hasErrorRecords());
        $this->assertNotEmpty($handler->getRecords());
    }
}
