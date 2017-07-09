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
    public static function setUpBeforeClass()
    {
        DI::getInstance()->get('config')
            ->set('app.timezone', 'Europe/Berlin')
            ->set('app.first_dow', DateTime::MONDAY);
    }

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
        $this->assertSame('2017-02-03T04:05:06+0100', (new DateTime('2017-02-03 04:05:06.654321'))->jsonSerialize());
    }

    public function testInstance()
    {
        $dt = DateTime::instance(new DateTime('2017-02-03 04:05:06'));
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());

        $dt = DateTime::instance(new \DateTime('2017-02-03 04:05:06'));
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());
    }

    public function testCreateFromParts()
    {
        $dt = DateTime::createFromParts([2017, 2, 3]);
        $this->assertInstanceOf(DateTimeContract::class, $dt);
        $this->assertSame('2017-02-03 00:00:00', $dt->toDateTimeString());

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
        $this->assertSame('2017-02-03 04:05:06', (string)$dt);
    }

    public function testSetAndGetParts()
    {
        $dt = new DateTime('2001-01-01');
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

    public function testStartAndEndOfWeek()
    {
        $this->assertSame(DateTime::MONDAY, DateTime::getFirstDayOfWeek());

        DateTime::setFirstDayOfWeek(DateTime::FRIDAY);
        $dt1 = (new DateTime('2017-02-03 04:05:06'))->startOfWeek();
        $dt2 = (new DateTime('2017-02-03 04:05:06'))->endOfWeek();
        $this->assertSame('2017-02-03 00:00:00', $dt1->toDateTimeString());
        $this->assertSame('2017-02-09 23:59:59', $dt2->toDateTimeString());
        $this->assertInstanceOf(DateTimeContract::class, $dt1);
        $this->assertInstanceOf(DateTimeContract::class, $dt2);
        $this->assertSame(DateTime::FRIDAY, DateTime::getFirstDayOfWeek());

        DateTime::setFirstDayOfWeek(DateTime::MONDAY);
        $dt1 = (new DateTime('2017-02-03 04:05:06'))->startOfWeek();
        $dt2 = (new DateTime('2017-02-03 04:05:06'))->endOfWeek();
        $this->assertSame('2017-01-30 00:00:00', $dt1->toDateTimeString());
        $this->assertSame('2017-02-05 23:59:59', $dt2->toDateTimeString());
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
        $this->assertSame(DateTime::SUNDAY,    (new DateTime('2017-02-05'))->dayOfWeek());
        $this->assertSame(DateTime::MONDAY,    (new DateTime('2017-02-06'))->dayOfWeek());
        $this->assertSame(DateTime::TUESDAY,   (new DateTime('2017-02-07'))->dayOfWeek());
        $this->assertSame(DateTime::WEDNESDAY, (new DateTime('2017-02-08'))->dayOfWeek());
        $this->assertSame(DateTime::THURSDAY,  (new DateTime('2017-02-09'))->dayOfWeek());
        $this->assertSame(DateTime::FRIDAY,    (new DateTime('2017-02-10'))->dayOfWeek());
        $this->assertSame(DateTime::SATURDAY,  (new DateTime('2017-02-11'))->dayOfWeek());
    }

    public function testDayOfYear()
    {
        $this->assertSame(33, (new DateTime('2017-02-03'))->dayOfYear());
    }

    public function testWeekOfMonth()
    {
        $this->assertSame(1, (new DateTime('2017-02-01'))->weekOfMonth()); // Mi
        $this->assertSame(1, (new DateTime('2017-02-05'))->weekOfMonth()); // So
        $this->assertSame(2, (new DateTime('2017-02-06'))->weekOfMonth()); // Mo
        $this->assertSame(2, (new DateTime('2017-02-12'))->weekOfMonth()); // So
        $this->assertSame(3, (new DateTime('2017-02-13'))->weekOfMonth()); // Mo
    }

    public function testWeekOfYear()
    {
        $this->assertSame(5, (new DateTime('2017-02-03'))->weekOfYear());
        $this->assertSame(53, (new DateTime('2016-01-01'))->weekOfYear());
        $this->assertSame(1, (new DateTime('2015-01-01'))->weekOfYear());
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

    public function testAddAndSub()
    {
        $dt = new DateTime('2017-02-03 04:05:06');

        $this->assertInstanceOf(DateTimeContract::class, $dt->addYears(5));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subYears(3));
        $this->assertSame('2019-02-03 04:05:06', $dt->toDateTimeString());

        $this->assertInstanceOf(DateTimeContract::class, $dt->addMonths(17));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subMonths(3));
        $this->assertSame('2020-04-03 04:05:06', $dt->toDateTimeString());

        $this->assertInstanceOf(DateTimeContract::class, $dt->addDays(35));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subDays(3));
        $this->assertSame('2020-05-05 04:05:06', $dt->toDateTimeString());

        $this->assertInstanceOf(DateTimeContract::class, $dt->addHours(29));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subHours(3));
        $this->assertSame('2020-05-06 06:05:06', $dt->toDateTimeString());

        $this->assertInstanceOf(DateTimeContract::class, $dt->addMinutes(65));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subMinutes(3));
        $this->assertSame('2020-05-06 07:07:06', $dt->toDateTimeString());

        $this->assertInstanceOf(DateTimeContract::class, $dt->addSeconds(65));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subSeconds(3));
        $this->assertSame('2020-05-06 07:08:08', $dt->toDateTimeString());
    }

    public function testAddAndSubQuarters()
    {
        $dt = new DateTime('2017-02-03 04:05:06');
        $this->assertInstanceOf(DateTimeContract::class, $dt->addQuarters(5));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subQuarters(3));
        $this->assertSame('2017-08-03 04:05:06', $dt->toDateTimeString());
    }

    public function testAddAndSubWeeks()
    {
        $dt = new DateTime('2017-02-03 04:05:06');
        $this->assertInstanceOf(DateTimeContract::class, $dt->addWeeks(5));
        $this->assertInstanceOf(DateTimeContract::class, $dt->subWeeks(3));
        $this->assertSame('2017-02-17 04:05:06', $dt->toDateTimeString());
    }

    public function testDiff()
    {
        $dt = new DateTime('2017-02-03 04:05:06');
        $this->assertSame(2, $dt->diffInYears(new DateTime('2015-02-03 04:05:06')));
        $this->assertSame(2, $dt->diffInQuarters(new DateTime('2016-08-03 04:05:06')));
        $this->assertSame(5, $dt->diffInMonths(new DateTime('2016-09-03 04:05:06')));
        $this->assertSame(3, $dt->diffInWeeks(new DateTime('2017-01-13 04:05:06')));
        $this->assertSame(6, $dt->diffInDays(new DateTime('2017-01-28 04:05:06')));
        $this->assertSame(3, $dt->diffInHours(new DateTime('2017-02-03 01:05:06')));
        $this->assertSame(3, $dt->diffInMinutes(new DateTime('2017-02-03 04:02:06')));
        $this->assertSame(56, $dt->diffInSeconds(new DateTime('2017-02-03 04:04:10')));

        $this->assertSame($dt->diffInYears(new DateTime), $dt->diffInYears());
        $this->assertSame($dt->diffInQuarters(new DateTime), $dt->diffInQuarters());
        $this->assertSame($dt->diffInMonths(new DateTime), $dt->diffInMonths());
        $this->assertSame($dt->diffInWeeks(new DateTime), $dt->diffInWeeks());
        $this->assertSame($dt->diffInDays(new DateTime), $dt->diffInDays());
        $this->assertSame($dt->diffInHours(new DateTime), $dt->diffInHours());
        $this->assertSame($dt->diffInMinutes(new DateTime), $dt->diffInMinutes());
        $this->assertSame($dt->diffInSeconds(new DateTime), $dt->diffInSeconds());
    }

    public function testStartAndEndOf()
    {
        $this->assertSame('2017-01-01 00:00:00', (new DateTime('2017-02-03 04:05:06'))->startOfYear()->toDateTimeString());
        $this->assertSame('2017-01-01 00:00:00', (new DateTime('2017-02-03 04:05:06'))->startOfQuarter()->toDateTimeString());
        $this->assertSame('2017-04-01 00:00:00', (new DateTime('2017-04-01 04:05:06'))->startOfQuarter()->toDateTimeString());
        $this->assertSame('2017-02-01 00:00:00', (new DateTime('2017-02-03 04:05:06'))->startOfMonth()->toDateTimeString());
        $this->assertSame('2017-02-03 00:00:00', (new DateTime('2017-02-03 04:05:06'))->startOfDay()->toDateTimeString());
        $this->assertSame('2017-02-03 04:00:00', (new DateTime('2017-02-03 04:05:06'))->startOfHour()->toDateTimeString());
        $this->assertSame('2017-02-03 04:05:00', (new DateTime('2017-02-03 04:05:06'))->startOfMinute()->toDateTimeString());
        $this->assertSame('2017-12-31 23:59:59', (new DateTime('2017-02-03 04:05:06'))->endOfYear()->toDateTimeString());
        $this->assertSame('2017-03-31 23:59:59', (new DateTime('2017-02-03 04:05:06'))->endOfQuarter()->toDateTimeString());
        $this->assertSame('2017-02-28 23:59:59', (new DateTime('2017-02-03 04:05:06'))->endOfMonth()->toDateTimeString());
        $this->assertSame('2017-02-03 23:59:59', (new DateTime('2017-02-03 04:05:06'))->endOfDay()->toDateTimeString());
        $this->assertSame('2017-02-03 04:59:59', (new DateTime('2017-02-03 04:05:06'))->endOfHour()->toDateTimeString());
        $this->assertSame('2017-02-03 04:05:59', (new DateTime('2017-02-03 04:05:06'))->endOfMinute()->toDateTimeString());
    }

    public function testLocale()
    {
        DI::getInstance()->get('config')
            ->set('app.locale', '~testlocale')
            ->set('app.fallback_locale', '~testfallback');
        DateTime::setLocale(null);

        $path1 = resource_path('lang/~testlocale');
        $path2 = resource_path('lang/~testfallback');
        @mkdir($path1);
        @mkdir($path2);
        file_put_contents($path1 . '/datetime.php', '<?php return [\'datetime\' => \'d.m.Y H:i\', \'date\' => \'d.m.Y\', \'time\' => \'H:i\'];');
        file_put_contents($path2 . '/datetime.php', '<?php return [\'datetime\' => \'Y_m_d_H_i\', \'date\' => \'Y_m_d\', \'time\' => \'H_i\'];');
        try {
            $dt = DateTime::createFromLocaleFormat('03.02.2017 04:05');
            $this->assertInstanceOf(DateTimeContract::class, $dt);
            $this->assertSame('2017-02-03 04:05:00', $dt->toDateTimeString());
            $this->assertSame('03.02.2017 04:05', $dt->toLocaleDateTimeString());
            $this->assertSame('03.02.2017', $dt->toLocaleDateString());
            $this->assertSame('04:05', $dt->toLocaleTimeString());

            $dt = DateTime::createFromLocaleDateFormat('03.02.2017');
            $this->assertInstanceOf(DateTimeContract::class, $dt);
            $this->assertSame('2017-02-03', $dt->toDateString());

            $dt = DateTime::createFromLocaleTimeFormat('04:05');
            $this->assertInstanceOf(DateTimeContract::class, $dt);
            $this->assertSame('04:05:00', $dt->toTimeString());

            DateTime::setLocaleFormat([
                'datetime' => 'YmdHi',
                'date'     => 'Ymd',
                'time'     => 'Hi',
            ]);
            $dt = DateTime::createFromLocaleFormat('201702030405');
            $this->assertSame('2017-02-03 04:05:00', $dt->toDateTimeString());
            $this->assertSame('201702030405', $dt->toLocaleDateTimeString());
            $this->assertSame('20170203', $dt->toLocaleDateString());
            $this->assertSame('0405', $dt->toLocaleTimeString());

            DateTime::setLocale('wrong'); // locale don't exist, fallback!
            $this->assertSame('wrong', $dt->getLocale());
            $dt = DateTime::createFromLocaleFormat('2017_02_03_04_05');
            $this->assertSame('2017-02-03 04:05:00', $dt->toDateTimeString());
            $this->assertSame('2017_02_03_04_05', $dt->toLocaleDateTimeString());
            $this->assertSame('2017_02_03', $dt->toLocaleDateString());
            $this->assertSame('04_05', $dt->toLocaleTimeString());
        }
        finally {
            @unlink($path1 . '/datetime.php');
            @unlink($path2 . '/datetime.php');
            @rmdir($path1);
            @rmdir($path2);
        }
    }

    public function testTimezone()
    {
        $this->assertInstanceOf(DateTimeZone::class, DateTime::getDefaultTimezone());
        $this->assertSame('Europe/Berlin', DateTime::getDefaultTimezone()->getName()); // UTC+1
        $dt = new DateTime('2017-02-03 04:05:06');
        $this->assertInstanceOf(DateTimeZone::class, $dt->getTimezone());
        $this->assertSame('Europe/Berlin', $dt->getTimezone()->getName());
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());

        DateTime::setDefaultTimezone('Europe/London'); // UTC+0
        $this->assertSame('Europe/London', DateTime::getDefaultTimezone()->getName());
        $this->assertSame('Europe/Berlin', $dt->getTimezone()->getName());
        $this->assertSame('2017-02-03 04:05:06', $dt->toDateTimeString());

        $dt = new DateTime('2017-02-03 04:05:06', 'Europe/London');
        $this->assertSame('Europe/London', $dt->getTimezone()->getName());
        $this->assertInstanceOf(DateTime::class, $dt->setTimezone('Asia/Aden')); // UTC+3
        $this->assertSame('Asia/Aden', $dt->getTimezone()->getName());
        $this->assertSame('2017-02-03 07:05:06', $dt->toDateTimeString());

        DateTime::setDefaultTimezone(new DateTimeZone('Europe/London'));
        $this->assertSame('Europe/London', DateTime::getDefaultTimezone()->getName());

        DateTime::setDefaultTimezone();
        $this->assertSame('Europe/Berlin', DateTime::getDefaultTimezone()->getName());

        $this->expectException(InvalidArgumentException::class);
        new DateTime('2017-02-03 04:05:06', 'wrong');
    }

}