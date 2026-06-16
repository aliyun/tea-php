<?php

namespace AlibabaCloud\Dara\Tests;

use AlibabaCloud\Dara\Date;
use AlibabaCloud\Dara\Exception\DaraException;
use PHPUnit\Framework\TestCase;
use DateTime;
use DateTimeZone;
use DateInterval;

/**
 * @internal
 * @coversNothing
 */
class DateTest extends TestCase
{

    public function testConstructWithNow()
    {
        $date = new Date();
        $currentTime = new DateTime();
        $this->assertEquals($currentTime->format('Y-m-d H:i:s'), $date->format('yyyy-MM-DD HH:mm:ss'));
    }

    public function testConstructWithDateTimeString()
    {
        $datetime = '2023-03-01 12:00:00';
        $date = new Date($datetime);
        $this->assertEquals($datetime, $date->format('yyyy-MM-DD HH:mm:ss'));
    }

     /**
     * @expectedException DaraException
     * @expectedExceptionMessage 2023-03-01 12:00:00 +0000 UTC is not a valid time str.
     * @throws DaraException
     */
    public function testConstructWithWrongType()
    {
        $this->expectException(DaraException::class);
        $this->expectExceptionMessage('2023-03-01 12:00:00 +0000 UTC is not a valid time str.');
        $datetimeUTC = '2023-03-01 12:00:00 +0000 UTC';
        $dateWithUTC = new Date($datetimeUTC);
    }

    public function testConstructWithUTC()
    {
        $datetimeUTC = '2023-03-01 12:00:00.426237 +0000 UTC';
        $dateWithUTC = new Date($datetimeUTC);
        
        $referenceDateTime = new DateTime('2023-03-01 12:00:00.426237', new DateTimeZone('UTC'));
        
        $this->assertEquals($referenceDateTime->getTimestamp(), $dateWithUTC->unix());
        
        $formattedDateTime = $dateWithUTC->UTC();
        $expectedFormattedDateTime = $referenceDateTime->format('Y-m-d H:i:s.u O \\U\\T\\C');
        $this->assertEquals($expectedFormattedDateTime, $formattedDateTime);
    }

    public function testFormat()
    {
        $datetime = '2023-03-01 12:00:00';
        $date = new Date($datetime);
        $this->assertEquals('2023-03-01 12:00 PM', $date->format('yyyy-MM-DD hh:mm A'));
    }

    public function testUTC()
    {
        $datetime = '2023-03-01T12:00:00+08:00';
        $date = new Date($datetime);
        $utcDateStr = $date->UTC();
        $this->assertEquals('2023-03-01 04:00:00.000000 +0000 UTC', $utcDateStr);
    }

    public function testUnix()
    {
        $datetime = '1970-01-01 00:00:00';
        $date = new Date($datetime);
        $this->assertEquals(0, $date->unix());

        $datetime = '2023-12-31T08:00:00+08:00';
        $date = new Date($datetime);
        $this->assertEquals(1703980800, $date->unix());
    }

    public function testAddSub()
    {
        $datetime = '2023-03-01 12:00:00';
        $date = new Date($datetime);
        $date->add('d', 1);
        $expectedDate = (new DateTime($datetime))->add(new DateInterval('P1D'));
        $this->assertEquals($expectedDate->format('Y-m-d H:i:s'), $date->format('yyyy-MM-DD HH:mm:ss'));

        $date->sub('d', 1); // 减去1天
        $this->assertEquals($datetime, $date->format('yyyy-MM-DD HH:mm:ss'));
    }
    
    public function testDiff()
    {
        $datetime1 = '2023-03-01 12:00:00';
        $datetime2 = '2023-04-01 12:00:00';
        $date1 = new Date($datetime1);
        $date2 = new Date($datetime2);
        $diffInSeconds = $date1->diff($date2);
        $this->assertEquals(31 * 24 * 60 * 60, $diffInSeconds); // 31天的总秒数
    }

    public function testHourMinuteSecond()
    {
        $datetime = '2023-03-01 12:34:56';
        $date = new Date($datetime);
        $this->assertEquals(12, $date->hour());
        $this->assertEquals(34, $date->minute());
        $this->assertEquals(56, $date->second());
    }

    public function testMonthYearDay()
    {
        $datetime = '2023-03-01 12:00:00';
        $date = new Date($datetime);
        $this->assertEquals(3, $date->month());
        $this->assertEquals(2023, $date->year());
        $this->assertEquals(1, $date->dayOfMonth());
    }

    public function testDayOfWeekWeekOfYear()
    {
        $datetime = '2023-03-01'; 
        $date = new Date($datetime);
        $this->assertEquals(3, $date->dayOfWeek());

        $this->assertEquals(9, $date->weekOfYear());

        $datetime1 = '2023-12-31 12:00:00'; 
        $date1 = new Date($datetime1);
        $this->assertEquals(31, $date1->dayOfMonth());
        $this->assertEquals(7, $date1->dayOfWeek());

        $this->assertEquals(52, $date1->weekOfYear());
    }
}