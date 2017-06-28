<?php

namespace Core\Tests\Services;

use Core\Services\DateTime;
use Core\Services\Contracts\DateTime as DateTimeContract;
use Core\Services\DI;
use Core\Testing\TestCase;
use DateTimeZone;
use InvalidArgumentException;

class DateTimeTest extends TestCase
{
    protected function setUp()
    {
        DI::getInstance()->get('config')
            ->set('app.timezone', 'Europe/Berlin')
            ->set('app.first_dow', 1);
        DateTime::setDefaultTimezone('Europe/Berlin');
        DateTime::setFirstDayOfWeek(1);
    }

//    protected function tearDown()
//    {
//    }

    ///////////////////////////////////////////////////////////////////
    // Create a DateTime Instance

    public function testConstruct()
    {
        $dt = new DateTime('2017-02-03 04:05:06');
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
        $this->assertSame('Europe/Berlin', $dt->getTimezone()->getName());

        $dt = new DateTime('2017-02-03 04:05:06', new DateTimeZone('Europe/London'));
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
        $this->assertSame('Europe/London', $dt->getTimezone()->getName());

        $dt = new DateTime('2017-02-03 04:05:06', 'Europe/London');
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
        $this->assertSame('Europe/London', $dt->getTimezone()->getName());
    }

    public function testCopy()
    {
        $dt = new DateTime('2017-02-03 04:05:06', 'Europe/London');
        $dt2 = $dt->copy();
        $this->assertInstanceOf(DateTimeContract::class, $dt2);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
        $this->assertSame('Europe/London', $dt->getTimezone()->getName());
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
        $dt = DateTime::createFromParts([2017, 2, 3], new DateTimeZone('Europe/London')); // todo extra test für TZ
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 00:00:00', $dt->toDateTimeString());
        $this->assertSame('Europe/London', $dt->getTimezone()->getName());

        $dt = DateTime::createFromParts([2017, 2, 3, 4, 5, 6]);
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
    }

    public function testCreateFromTimestamp()
    {
        $dt = DateTime::createFromTimestamp(strtotime('2017-02-03 04:05:06'));
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
    }

    public function testCreateFromFormat()
    {
        $dt = DateTime::createFromFormat('Y,m,d,H,i,s', '2017,02,03,04,05,06');
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());

        $this->expectException(InvalidArgumentException::class);
        DateTime::createFromFormat('wrong', '2017,02,03,04,05,06');
    }

    public function testCreateFromTimestampUTC()
    {
        $dt = DateTime::createFromTimestampUTC(strtotime('2017-02-03 04:05:06 UTC'));
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
        $this->assertSame('+00:00', $dt->getTimezone()->getName());
    }

    ///////////////////////////////////////////////////////////////////
    // String Formatting

    public function testToString()
    {
        $dt = new DateTime('2017-02-03 04:05:06');
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
        $this->assertSame('2017-02-03', $dt->toDateString());
        $this->assertSame('04:05:06', $dt->toTimeString());
        $this->assertSame('2017-02-03T04:05:06+01:00', $dt->toAtomString());
        $this->assertSame('Friday, 03-Feb-2017 04:05:06 CET', $dt->toCookieString());
        $this->assertSame('2017-02-03T04:05:06+0100', $dt->toIso8601String());
        $this->assertSame('Fri, 03 Feb 17 04:05:06 +0100', $dt->toRfc822String());
        $this->assertSame('Friday, 03-Feb-17 04:05:06 CET', $dt->toRfc850String());
        $this->assertSame('Fri, 03 Feb 17 04:05:06 +0100', $dt->toRfc1036String());
        $this->assertSame('Fri, 03 Feb 2017 04:05:06 +0100', $dt->toRfc1123String());
        $this->assertSame('Fri, 03 Feb 2017 04:05:06 +0100', $dt->toRfc2822String());
        $this->assertSame('2017-02-03T04:05:06+01:00', $dt->toRfc3339String());
        $this->assertSame('Fri, 03 Feb 2017 04:05:06 +0100', $dt->toRssString());
        $this->assertSame('2017-02-03T04:05:06+01:00', $dt->toW3cString());
    }
    
    ///////////////////////////////////////////////////////////////////
    // Parts of Date Time

    public function testSetAndGetParts()
    {
        $dt = new DateTime;
        $this->assertInstanceOf(DateTimeContract::class, $dt->setYear(2017));
        $this->assertInstanceOf(DateTimeContract::class, $dt->setMonth(2));
        $this->assertInstanceOf(DateTimeContract::class, $dt->setDay(3));
        $this->assertInstanceOf(DateTimeContract::class, $dt->setHour(4));
        $this->assertInstanceOf(DateTimeContract::class, $dt->setMinute(5));
        $this->assertInstanceOf(DateTimeContract::class, $dt->setSecond(6));
        $this->assertSame(2017, $dt->getYear());
        $this->assertSame(2, $dt->getMonth());
        $this->assertSame(3, $dt->getDay());
        $this->assertSame(4, $dt->getHour());
        $this->assertSame(5, $dt->getMinute());
        $this->assertSame(6, $dt->getSecond());
    }

    public function testSetDateTime()
    {
        $dt = new DateTime;
        $this->assertInstanceOf(DateTimeContract::class, $dt->setDateTime(2017, 2, 3, 4, 5, 6));
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
    }

    public function testMicro()    
    {
        $dt = new DateTime('2017-02-03 04:05:06.654321');
        $this->assertSame(654321, $dt->micro());

        $dt = DateTime::createFromFormat('Y,m,d,H,i,s,u', '2017,02,03,04,05,06,654321');
        $this->assertSame(654321, $dt->micro());
    }

    public function testDayOfWeek()
    {
        $this->assertSame(0, (new DateTime('2017-02-05'))->dayOfWeek()); // Sunday
        $this->assertSame(1, (new DateTime('2017-02-06'))->dayOfWeek()); // Monday
        $this->assertSame(2, (new DateTime('2017-02-07'))->dayOfWeek());
        $this->assertSame(3, (new DateTime('2017-02-08'))->dayOfWeek());
        $this->assertSame(4, (new DateTime('2017-02-09'))->dayOfWeek());
        $this->assertSame(5, (new DateTime('2017-02-10'))->dayOfWeek());
        $this->assertSame(6, (new DateTime('2017-02-11'))->dayOfWeek());
    }

    public function testDayOfYear()
    {
        $this->assertSame(33, (new DateTime('2017-02-03'))->dayOfYear());
    }

    public function testWeekOfMonth()
    {
        $this->assertSame(1, (new DateTime('2017-02-01'))->weekOfMonth());
        $this->assertSame(1, (new DateTime('2017-02-05'))->weekOfMonth()); // So
        $this->assertSame(2, (new DateTime('2017-02-06'))->weekOfMonth()); // Mo
        $this->assertSame(2, (new DateTime('2017-02-12'))->weekOfMonth()); // So
        $this->assertSame(3, (new DateTime('2017-02-13'))->weekOfMonth()); // Mo
    }

    public function testWeekOfYear()
    {
        $this->assertSame(5, (new DateTime('2017-02-03'))->weekOfYear()); // todo andere Jahreswechsel prüfen
    }

    public function testDaysInMonth()
    {
        $this->assertSame(28, (new DateTime('2017-02-03'))->daysInMonth());
    }

    public function testTimestamp()
    {
        $this->assertSame(strtotime('2017-02-03 04:05:06'), (new DateTime('2017-02-03 04:05:06'))->timestamp());
    }

    public function testAge()
    {
        $dt = new DateTime('2010-02-03 04:05:06');
        $this->assertSame($dt->diffInYears(), $dt->age());
    }

    public function testQuarter()
    {
        $this->assertSame(1, (new DateTime('2017-02-03'))->quarter());
        $this->assertSame(2, (new DateTime('2017-05-03'))->quarter());
    }

    public function testIsLeapYear()
    {
        $this->assertTrue((new DateTime('2016-02-03'))->isLeapYear());
        $this->assertFalse((new DateTime('2017-02-03'))->isLeapYear());
    }

    public function testIsWeekday()
    {
        $this->assertTrue((new DateTime('2017-02-05'))->isSunday());
        $this->assertTrue((new DateTime('2017-02-06'))->isMonday());
        $this->assertTrue((new DateTime('2017-02-07'))->isTuesday());
        $this->assertTrue((new DateTime('2017-02-08'))->isWednesday());
        $this->assertTrue((new DateTime('2017-02-09'))->isThursday());
        $this->assertTrue((new DateTime('2017-02-10'))->isFriday());
        $this->assertTrue((new DateTime('2017-02-11'))->isSaturday());

        $this->assertFalse((new DateTime('2017-04-05'))->isSunday());
        $this->assertFalse((new DateTime('2017-04-06'))->isMonday());
        $this->assertFalse((new DateTime('2017-04-07'))->isTuesday());
        $this->assertFalse((new DateTime('2017-04-08'))->isWednesday());
        $this->assertFalse((new DateTime('2017-04-09'))->isThursday());
        $this->assertFalse((new DateTime('2017-04-10'))->isFriday());
        $this->assertFalse((new DateTime('2017-04-11'))->isSaturday());
    }

    ///////////////////////////////////////////////////////////////////
    // Addition and Subtraction

    public function testAddAndSub()
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

    public function testLocale()
    {
        // testCreateFromLocaleFormat
        // testCreateFromLocaleDateFormat
        // testCreateFromLocaleTimeFormat

        // testToLocaleDateTimeString
        // testToLocaleDateString
        // testToLocaleTimeString

//        DI::getInstance()->get('config')->set('app.locale', '~testlocale')->set('app.fallback_locale', '~testfallback');
//        $path1 = resource_path('lang/~testlocale');
//        $path2 = resource_path('lang/~testfallback');
//        @mkdir($path1);
//        @mkdir($path2);
//        file_put_contents($path1 . '/datetime.php', '<?php return [\'datetime\' => \'d.m.Y H:i\', \'date\' => \'d.m.Y\', \'time\' => \'H:i\'];');
//        file_put_contents($path2 . '/datetime.php', '<?php return [\'datetime\' => \'Y-m-d H:i\', \'date\' => \'Y-m-d\', \'time\' => \'H:i\'];');
//        try {
//
//        }
//        finally {
//            @unlink($path1 . '/datetime.php');
//            @unlink($path2 . '/datetime.php');
//            @rmdir($path1);
//            @rmdir($path2);
//        }

        DateTime::setLocaleFormat([
            'datetime' => 'Y-m-d H:i',
            'date'     => 'Y-m-d',
            'time'     => 'H:i',
        ]);

//
//        $dt = datetime('2017-03-04 05:06', null, 'locale');
//        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
//        $this->assertInstanceOf(DateTimeContract::class, $dt);
//        $this->assertSame('2017-03-04 05:06:00', $dt->toDateTimeString());
//
//        $dt = datetime('2017-03-04', null, 'locale.date');
//        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
//        $this->assertInstanceOf(DateTimeContract::class, $dt);
//        $this->assertSame('2017-03-04', $dt->toDateString());
//
//        $dt = datetime('05:06', null, 'locale.time');
//        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
//        $this->assertInstanceOf(DateTimeContract::class, $dt);
//        $this->assertSame($todayString . ' 05:06:00', $dt->toDateTimeString());
//
//        $dt = datetime('201703040506', null, 'Ymdhi');
//        /** @noinspection PhpUnnecessaryFullyQualifiedNameInspection */
//        $this->assertInstanceOf(DateTimeContract::class, $dt);
//        $this->assertSame('2017-03-04 05:06:00', $dt->toDateTimeString());
    }

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

    ///////////////////////////////////////////////////////////////////
    // Timezone

    // todo
}