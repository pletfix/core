<?php

namespace Core\Tests\Services;

use Core\Services\DateTime;
use Core\Services\DI;
use Core\Testing\TestCase;
use DateTimeZone;
use RuntimeException;

class DateTimeTest extends TestCase
{
    ///////////////////////////////////////////////////////////////////
    // Create a DateTime Instance

    public function testConstruct()
    {
        // todo
    }

    public function testCopy()
    {
        // todo
    }

    public function testJsonSerialize()
    {
        // todo
    }

    public function testInstance()
    {
        // todo
    }

    public function testCreateFromParts()
    {
        $d = DateTime::createFromParts([2017, 2, 3], new DateTimeZone('Europe/London'));
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $d);
        $this->assertSame('2017-02-03 00:00:00', $d->toDateTimeString());
        $this->assertSame('Europe/London', $d->getTimezone()->getName());

        $d = DateTime::createFromParts([2017, 2, 3, 4, 5, 6]);
        $this->assertInstanceOf(\Core\Services\Contracts\DateTime::class, $d);
        $this->assertSame('2017-02-03 04:05:06', $d->toDateTimeString());
    }

    public function testCreateFromFormat()
    {
        // todo
    }

    public function testCreateFromLocaleFormat()
    {
        // todo
    }

    public function testCreateFromLocaleDateFormat()
    {
        // todo
    }

    public function testCreateFromLocaleTimeFormat()
    {
        // todo
    }

    public function testCreateFromTimestamp()
    {
        // todo
    }

    public function testCreateFromTimestampUTC()
    {
        // todo
    }

    ///////////////////////////////////////////////////////////////////
    // String Formatting

    public function testToDateTimeString()
    {
        // todo
    }

    public function testToDateString()
    {
        // todo
    }

    public function testToTimeString()
    {
        // todo
    }

    public function testToLocaleDateTimeString()
    {
        // todo
    }

    public function testToLocaleDateString()
    {
        // todo
    }

    public function testToLocaleTimeString()
    {
        // todo
    }

    public function testToAtomString()
    {
        // todo
    }

    public function testToCookieString()
    {
        // todo
    }

    public function testToIso8601String()
    {
        // todo
    }

    public function testToRfc822String()
    {
        // todo
    }

    public function testToRfc850String()
    {
        // todo
    }

    public function testToRfc1036String()
    {
        // todo
    }

    public function testToRfc1123String()
    {
        // todo
    }

    public function testToRfc2822String()
    {
        // todo
    }

    public function testToRfc3339String()
    {
        // todo
    }

    public function testToRssString()
    {
        // todo
    }

    public function testToW3cString()
    {
        // todo
    }

    ///////////////////////////////////////////////////////////////////
    // Parts of Date Time

    public function testGetYear()
    {
        // todo
    }

    public function testSetYear()
    {
        // todo
    }

    public function testGetMonth()
    {
        // todo
    }

    public function testSetMonth()
    {
        // todo
    }

    public function testGetDay()
    {
        // todo
    }

    public function testSetDay()
    {
        // todo
    }

    public function testGetHour()
    {
        // todo
    }

    public function testSetHour()
    {
        // todo
    }

    public function testGetMinute()
    {
        // todo
    }

    public function testSetMinute()
    {
        // todo
    }
    
    public function testGetSecond()
    {
        // todo
    }

    public function testSetSecond()
    {
        // todo
    }

    public function testSetDateTime()
    {
        // todo
    }

    public function testMicro()    
    {
        // todo
    }

    public function testDayOfWeek()
    {
        // todo
    }

    public function testDayOfYear()
    {
        // todo
    }

    public function testWeekOfMonth()
    {
        // todo
    }

    public function testWeekOfYear()
    {
        // todo
    }

    public function testDaysInMonth()
    {
        // todo
    }

    public function testTimestamp()
    {
        // todo
    }

    public function testAge()
    {
        // todo
    }

    public function testQuarter()
    {
        // todo
    }

    public function testIsLeapYear()
    {
        // todo
    }

    public function testIsSunday()
    {
        // todo
    }

    public function testIsMonday()
    {
        // todo
    }

    public function testIsTuesday()
    {
        // todo
    }

    public function testIsWednesday()
    {
        // todo
    }

    public function testIsThursday()
    {
        // todo
    }

    public function testIsFriday()
    {
        // todo
    }

    public function testIsSaturday()
    {
        // todo
    }

    ///////////////////////////////////////////////////////////////////
    // Addition and Subtraction

    public function testAddYears()
    {
        // todo
    }

    public function testSubYears()
    {
        // todo
    }

    public function testAddQuarters()
    {
        // todo
    }

    public function testSubQuarters()
    {
        // todo
    }

    public function testAddMonths()
    {
        // todo
    }

    public function testSubMonths()
    {
        // todo
    }

    public function testAddWeeks()
    {
        // todo
    }

    public function testSubWeeks()
    {
        // todo
    }

    public function testAddDays()
    {
        // todo
    }

    public function testSubDays()
    {
        // todo
    }

    public function testAddHours()
    {
        // todo
    }

    public function testSubHours()
    {
        // todo
    }

    public function testAddMinutes()
    {
        // todo
    }

    public function testSubMinutes()
    {
        // todo
    }

    public function testAddSeconds()
    {
        // todo
    }

    public function testSubSeconds()
    {
        // todo
    }

    ///////////////////////////////////////////////////////////////////
    // Differences

    public function testDiffInYears()
    {
        // todo
    }

    public function testDiffInQuarters()
    {
        // todo
    }

    public function testDiffInMonths()
    {
        // todo
    }

    public function testDiffInWeeks()
    {
        // todo
    }

    public function testDiffInDays()
    {
        // todo
    }

    public function testDiffInHours()
    {
        // todo
    }

    public function testDiffInMinutes()
    {
        // todo
    }

    public function testDiffInSeconds()
    {
        // todo
    }

    ///////////////////////////////////////////////////////////////////
    // Start Of and End Of

    public function testStartOfYear()
    {
        // todo
    }

    public function testEndOfYear()
    {
        // todo
    }

    public function testStartOfQuarter()
    {
        // todo
    }

    public function testEndOfQuarter()
    {
        // todo
    }

    public function testStartOfMonth()
    {
        // todo
    }

    public function testEndOfMonth()
    {
        // todo
    }

    public function testStartOfWeek()
    {
        // todo
    }

    public function testEndOfWeek()
    {
        // todo
    }

    public function testStartOfDay()
    {
        // todo
    }

    public function testEndOfDay()
    {
        // todo
    }

    public function testStartOfHour()
    {
        // todo
    }

    public function testEndOfHour()
    {
        // todo
    }

    public function testStartOfMinute()
    {
        // todo
    }

    public function testEndOfMinute()
    {
        // todo
    }

    ///////////////////////////////////////////////////////////////////
    // Locale

    public function testGetDefaultTimezone()
    {
        // todo
    }

    public function testSetDefaultTimezone()
    {
        // todo
    }

    public function testSetTimezone()
    {
        // todo
    }

    public function testGetFirstDayOfWeek()
    {
        // todo
    }

    public function testSetFirstDayOfWeek()
    {
        // todo
    }

    public function testGetLocale()
    {
        // todo
    }

    public function testSetLocale()
    {
        // todo
    }

    public function testSetLocaleFormat()
    {
        // todo
    }        
}