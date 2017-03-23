<?php

namespace Core\Services;

use Core\Services\Contracts\DateTime as DateTimeContract;
use DateTimeInterface;
use DateTimeZone;
use DateTime as BaseDateTime;
use InvalidArgumentException;

/**
 * A simple PHP API extension for DateTime
 *
 * Function createTimeZone() und create() based on Carbon (https://github.com/briannesbitt/Carbon/blob/1.22.1/LICENSE MIT license).
 * Function createFromFormat(), createFromDateTime(), and functions for addition/subtraction based on Chronos
 *
 * @see https://github.com/briannesbitt/Carbon/blob/1.22.1/src/Carbon/Carbon.php
 * @see https://github.com/cakephp/chronos/blob/1.1.0/src/Traits/FactoryTrait.php
 * @see https://github.com/cakephp/chronos/blob/1.1.0/src/Traits/ModifierTrait.php
 */
class DateTime extends BaseDateTime implements DateTimeContract
{
    /**
     * The day constants
     */
    const SUNDAY    = 0;
    const MONDAY    = 1;
    const TUESDAY   = 2;
    const WEDNESDAY = 3;
    const THURSDAY  = 4;
    const FRIDAY    = 5;
    const SATURDAY  = 6;

    /**
     * First day of week
     *
     * @var int
     */
    protected static $weekStartsAt = self::MONDAY;

    /**
     * Format to use for __toString.
     *
     * @var string
     */
    protected static $toStringFormat = 'Y-m-d H:i:s';

    /**
     * The default timezone
     *
     * @var DateTimeZone
     */
    private static $timezone;

    /**
     * The default locale
     *
     * @var string
     */
    private static $locale; // todo locale richtig aus config lesen, testen

    /**
     * The actual locale
     *
     * @var string
     */
    private $actualLocale; // todo entfernen

    /**
     * Create a new DateTime instance.
     *
     * @param int|DateTimeInterface|array|string|null $dateTime
     * @param DateTimeZone|string|null $timezone
     * @param string|null $format
     */
    public function __construct($dateTime = null, $timezone = null, $format = null)
    {
        if (is_int($dateTime)) {
            parent::__construct(null, static::createTimeZone($timezone))->setTimestamp($dateTime);
        }
        else {
            if ($dateTime instanceof DateTimeInterface) {
                if ($timezone === null) {
                    $timezone = $dateTime->getTimezone();
                }
                $dateTime = $dateTime->format('Y-m-d H:i:s.u');
            }
            else if (is_array($dateTime)) {
                $year   = isset($dateTime[0]) ? $dateTime[0] : date('Y');
                $month  = isset($dateTime[1]) ? $dateTime[1] : date('n');
                $day    = isset($dateTime[2]) ? $dateTime[2] : date('j');
                $hour   = isset($dateTime[3]) ? $dateTime[3] : 0; //date('G');
                $minute = isset($dateTime[4]) ? $dateTime[4] : 0; //date('i');
                $second = isset($dateTime[5]) ? $dateTime[5] : 0; //date('s');
                $micro  = isset($dateTime[6]) ? $dateTime[6] : 0; //date('u');
                $dateTime = sprintf('%04s-%02s-%02s %02s:%02s:%02s.%06s', $year, $month, $day, $hour, $minute, $second, $micro);
            }
            else if ($format !== null) {
                if ($format == 'locale') {
                    $format = self::localeFormat();
                }
                else if ($format == 'locale.date') {
                    $format = self::localeFormat('date');
                }
                else if ($format == 'locale.time') {
                    $format = self::localeFormat('time');
                }
                if (($dt = parent::createFromFormat($format, $dateTime)) === false) {
                    $errors = parent::getLastErrors();
                    throw new InvalidArgumentException(implode(PHP_EOL, $errors['errors']));
                }
                $dateTime = $dt->format('Y-m-d H:i:s.u');
            }

            parent::__construct($dateTime, static::createTimeZone($timezone));
        }
    }

    /**
     * Format the instance as a string using the default setting.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format(static::$toStringFormat);
    }

    /**
     * Returns a ISO8601 representation of the instance.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return (string)$this->toIso8601String();
    }

    /**
     * Get a copy of the instance
     *
     * Todo oder cloning()
     *
     * @return static
     */
    public function copy()
    {
        return clone $this;
    }

    ///////////////////////////////////////////////////////////////////
    // String Formatting

    /**
     * Format the instance as date and time.
     *
     * @return string
     */
    public function toDateTimeString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Format the instance as date
     *
     * @return string
     */
    public function toDateString()
    {
        return $this->format('Y-m-d');
    }

    /**
     * Format the instance as time
     *
     * @return string
     */
    public function toTimeString()
    {
        return $this->format('H:i:s');
    }

    /**
     * Format the instance as date and time.
     *
     * @return string
     */
    public function toLocaleDateTimeString()
    {
        return $this->format(self::localeFormat());
    }

    /**
     * Format the instance as date
     *
     * @return string
     */
    public function toLocaleDateString()
    {
        return $this->format(self::localeFormat('date'));
    }

    /**
     * Format the instance as time
     *
     * @return string
     */
    public function toLocaleTimeString()
    {
        return $this->format(self::localeFormat('time'));
    }

    /**
     * Format the instance as ATOM
     *
     * @return string
     */
    public function toAtomString()
    {
        return $this->format(static::ATOM);
    }

    /**
     * Format the instance as COOKIE
     *
     * @return string
     */
    public function toCookieString()
    {
        return $this->format(static::COOKIE);
    }

    /**
     * Format the instance as ISO8601
     *
     * @return string
     */
    public function toIso8601String()
    {
        // TODO
        // Note: This format is not compatible with ISO-8601! Use DateTime::ATOM or DATE_ATOM instead.
        // see http://de2.php.net/manual/en/class.datetime.php#datetime.constants.iso8601

        return $this->format(static::ISO8601);
    }

    /**
     * Format the instance as RFC822
     *
     * @return string
     */
    public function toRfc822String()
    {
        return $this->format(static::RFC822);
    }

    /**
     * Format the instance as RFC850
     *
     * @return string
     */
    public function toRfc850String()
    {
        return $this->format(static::RFC850);
    }

    /**
     * Format the instance as RFC1036
     *
     * @return string
     */
    public function toRfc1036String()
    {
        return $this->format(static::RFC1036);
    }

    /**
     * Format the instance as RFC1123
     *
     * @return string
     */
    public function toRfc1123String()
    {
        return $this->format(static::RFC1123);
    }

    /**
     * Format the instance as RFC2822
     *
     * @return string
     */
    public function toRfc2822String()
    {
        return $this->format(static::RFC2822);
    }

    /**
     * Format the instance as RFC3339
     *
     * @return string
     */
    public function toRfc3339String()
    {
        return $this->format(static::RFC3339);
    }

    /**
     * Format the instance as RSS
     *
     * @return string
     */
    public function toRssString()
    {
        return $this->format(static::RSS);
    }

    /**
     * Format the instance as W3C
     *
     * @return string
     */
    public function toW3cString()
    {
        return $this->format(static::W3C);
    }

    ///////////////////////////////////////////////////////////////////
    // Parts of Date Time

    /**
     * Get the year.
     *
     * @return int
     */
    public function getYear()
    {
        return (int)$this->format('Y');
    }

    /**
     * Set the year.
     *
     * @param $year
     * @return $this
     */
    public function setYear($year)
    {
        $this->setDate($year, $this->getMonth(), $this->getDay());

        return $this;
    }

    /**
     * Get the month.
     *
     * @return int
     */
    public function getMonth()
    {
        return (int)$this->format('n');
    }

    /**
     * Set the month.
     *
     * @param $month
     * @return $this
     */
    public function setMonth($month)
    {
        $this->setDate($this->getYear(), $month, $this->getDay());

        return $this;
    }

    /**
     * Get the day of month.
     *
     * @return int
     */
    public function getDay()
    {
        return (int)$this->format('j');
    }

    /**
     * Set the day of month.
     *
     * @param $day
     * @return $this
     */
    public function setDay($day)
    {
        $this->setDate($this->getYear(), $this->getMonth(), $day);

        return $this;
    }

    /**
     * Get the hour.
     *
     * @return int
     */
    public function getHour()
    {
        return (int)$this->format('G');
    }

    /**
     * Set the hour.
     *
     * @param $hour
     * @return $this
     */
    public function setHour($hour)
    {
        $this->setTime($hour, $this->getMinute(), $this->getSecond());

        return $this;
    }

    /**
     * Get the minute.
     *
     * @return int
     */
    public function getMinute()
    {
        return (int)$this->format('i');
    }

    /**
     * Set the minute.
     *
     * @param $minute
     * @return $this
     */
    public function setMinute($minute)
    {
        $this->setTime($this->getHour(), $minute, $this->getSecond());

        return $this;
    }

    /**
     * Get the second.
     *
     * @return int
     */
    public function getSecond()
    {
        return (int)$this->format('s');
    }

    /**
     * Set the second.
     *
     * @param $second
     * @return $this
     */
    public function setSecond($second)
    {
        $this->setTime($this->getHour(), $this->getMinute(), $second);

        return $this;
    }

    /**
     * Set the date and time all together
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @return static
     */
    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0)
    {
        return $this->setDate($year, $month, $day)->setTime($hour, $minute, $second);
    }

    /**
     * Returns the microseconds.
     *
     * @return int
     */
    public function micro()
    {
        return (int)$this->format('u');
    }

    /**
     * Returns the day of the week.
     *
     * @return int 0 (for Sunday) through 6 (for Saturday)
     */
    public function dayOfWeek()
    {
        return (int)$this->format('w');
    }

    /**
     * Returns the day of the year (starting from 0).
     *
     * @return int 0 through 365
     */
    public function dayOfYear()
    {
        return (int)$this->format('z');
    }

    /**
     * Week number of month.
     *
     * Weeks starting on Monday.
     *
     * @return int
     */
    public function weekOfMonth()
    {
        return (int)ceil($this->getDay() / 7);
    }

    /**
     * ISO-8601 week number of year.
     *
     * Weeks starting on Monday.
     *
     * @return int
     */
    public function weekOfYear()
    {
        return (int)$this->format('W');
    }

    /**
     * Number of days in the month.
     *
     * @return int
     */
    public function daysInMonth()
    {
        return (int)$this->format('t');
    }

    /**
     * Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).
     *
     * @return int
     */
    public function timestamp()
    {
        return (int)$this->format('U');
    }

    /**
     * Alias for diffInYears().
     *
     * @return int
     */
    public function age()
    {
        return (int)$this->diffInYears();
    }

    /**
     * Returns the quarter of the year.
     *
     * @return int
     */
    public function quarter()
    {
        return (int)ceil($this->getMonth() / 3);
    }

    /**
     * Determines if the instance is a leap year
     *
     * @return bool
     */
    public function isLeapYear()
    {
        return $this->format('L') === '1';
    }

    /**
     * Checks if this day is a Sunday.
     *
     * @return bool
     */
    public function isSunday()
    {
        return $this->dayOfWeek() === static::SUNDAY;
    }

    /**
     * Checks if this day is a Monday.
     *
     * @return bool
     */
    public function isMonday()
    {
        return $this->dayOfWeek() === static::MONDAY;
    }

    /**
     * Checks if this day is a Tuesday.
     *
     * @return bool
     */
    public function isTuesday()
    {
        return $this->dayOfWeek() === static::TUESDAY;
    }

    /**
     * Checks if this day is a Wednesday.
     *
     * @return bool
     */
    public function isWednesday()
    {
        return $this->dayOfWeek() === static::WEDNESDAY;
    }

    /**
     * Checks if this day is a Thursday.
     *
     * @return bool
     */
    public function isThursday()
    {
        return $this->dayOfWeek() === static::THURSDAY;
    }

    /**
     * Checks if this day is a Friday.
     *
     * @return bool
     */
    public function isFriday()
    {
        return $this->dayOfWeek() === static::FRIDAY;
    }

    /**
     * Checks if this day is a Saturday.
     *
     * @return bool
     */
    public function isSaturday()
    {
        return $this->dayOfWeek() === static::SATURDAY;
    }

    ///////////////////////////////////////////////////////////////////
    // Addition and Subtraction

    /**
     * Add years to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addYears($value)
    {
        $value = (int)$value;

        return $this->modify("$value year");
    }

    /**
     * Subtract years from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subYears($value)
    {
        $value = (int)$value;

        return $this->modify("-$value year");
    }

    /**
     * Add quarters to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addQuarters($value)
    {
        $value = (int)$value * 3;

        return $this->modify("$value month");
    }

    /**
     * Subtract quarters from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subQuarters($value)
    {
        $value = (int)$value * 3;

        return $this->modify("-$value month");
    }

    /**
     * Add months to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addMonths($value)
    {
        $value = (int)$value;

        return $this->modify("$value month");
    }

    /**
     * Subtract months from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subMonths($value)
    {
        $value = (int)$value;

        return $this->modify("-$value month");
    }

    /**
     * Add weeks to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addWeeks($value)
    {
        $value = (int)$value;

        return $this->modify("$value week");
    }

    /**
     * Subtract weeks from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subWeeks($value)
    {
        $value = (int)$value;

        return $this->modify("-$value week");
    }

    /**
     * Add days to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addDays($value)
    {
        $value = (int)$value;

        return $this->modify("$value day");
    }

    /**
     * Subtract days from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subDays($value)
    {
        $value = (int)$value;

        return $this->modify("-$value day");
    }

    /**
     * Add hours to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addHours($value)
    {
        $value = (int)$value;

        return $this->modify("$value hour");
    }

    /**
     * Subtract hours from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subHours($value)
    {
        $value = (int)$value;

        return $this->modify("-$value hour");
    }

    /**
     * Add minutes to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addMinutes($value)
    {
        $value = (int)$value;

        return $this->modify("$value minute");
    }

    /**
     * Subtract minutes from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subMinutes($value)
    {
        $value = (int)$value;

        return $this->modify("-$value minute");
    }

    /**
     * Add seconds to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addSeconds($value)
    {
        $value = (int)$value;

        return $this->modify("$value second");
    }

    /**
     * Subtract seconds from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subSeconds($value)
    {
        $value = (int)$value;

        return $this->modify("-$value second");
    }

    ///////////////////////////////////////////////////////////////////
    // Differences

    /**
     * Get the difference in years.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInYears(DateTimeInterface $dt = null, $abs = true)
    {
        if ($dt === null) {
            $dt = new static(null, $this->getTimezone());
        }

        return (int)$this->diff($dt, $abs)->format('%r%y');
    }

    /**
     * Get the difference in quarters.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInQuarters(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInMonths($dt, $abs) / 3);
    }

    /**
     * Get the difference in months.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInMonths(DateTimeInterface $dt = null, $abs = true)
    {
        if ($dt === null) {
            $dt = new static(null, $this->getTimezone());
        }

        return $this->diffInYears($dt, $abs) * 12 + (int)$this->diff($dt, $abs)->format('%r%m');
    }

    /**
     * Get the difference in weeks.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInWeeks(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInDays($dt, $abs) / 7);
    }

    /**
     * Get the difference in days.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInDays(DateTimeInterface $dt = null, $abs = true)
    {
        if ($dt === null) {
            $dt = new static(null, $this->getTimezone());
        }

        return (int)$this->diff($dt, $abs)->format('%r%a');
    }

    /**
     * Get the difference in hours.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInHours(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInSeconds($dt, $abs) / 3600);
    }

    /**
     * Get the difference in minutes.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInMinutes(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInSeconds($dt, $abs) / 60);
    }

    /**
     * Get the difference in seconds.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInSeconds(DateTimeInterface $dt = null, $abs = true)
    {
        if ($dt === null) {
            $dt = new static(null, $this->getTimezone());
        }

        $value = $dt->getTimestamp() - $this->getTimestamp();

        return $abs ? abs($value) : $value;
    }




    ///////////////////////////////////////////////////////////////////
    // Start Of and End Of

    /**
     * Resets the date to the first day of the year and the time to 00:00:00
     *
     * @return static
     */
    public function startOfYear()
    {
        return $this->setMonth(1)->startOfMonth();
    }

    /**
     * Resets the date to end of the year and time to 23:59:59
     *
     * @return static
     */
    public function endOfYear()
    {
        return $this->setMonth(12)->endOfMonth();
    }

    /**
     * Resets the date to the beginning of the current quarter, 1st day of months, 00:00
     *
     * @return static
     */
    public function startOfQuarter()
    {
        $quarter = $this->quarter();

        return $this->setMonth($quarter * 3)->startOfMonth(); // todo testen
    }

    /**
     * Resets the date to end of the year and time to 23:59:59
     *
     * @return static
     */
    public function endOfQuarter()
    {
        $quarter = $this->quarter();

        return $this->setMonth($quarter * 3 + 2)->endOfMonth(); // todo testen
    }

    /**
     * Resets the date to the first day of the month and the time to 00:00:00
     *
     * @return static
     */
    public function startOfMonth()
    {
        return $this->startOfDay()->setDay(1);
    }

    /**
     * Resets the date to end of the month and time to 23:59:59
     *
     * @return static
     */
    public function endOfMonth()
    {
        return $this->setDay($this->daysInMonth())->endOfDay();
    }

    /**
     * Resets the date to the first day of week (defined in $weekStartsAt) and the time to 00:00:00
     *
     * @return static
     */
    public function startOfWeek()
    {
        if ($this->dayOfWeek() !== static::$weekStartsAt) {
            $this->previous(static::$weekStartsAt);
        }

        return $this->startOfDay();
    }

    /**
     * Resets the date to end of week (defined in $weekEndsAt) and time to 23:59:59
     *
     * @return static
     */
    public function endOfWeek()
    {
        $weekEndsAt = static::$weekStartsAt > 0 ? static::$weekStartsAt - 1 : 6;
        if ($this->dayOfWeek() !== $weekEndsAt) {
            $this->next($weekEndsAt);
        }

        return $this->endOfDay();
    }

    /**
     * Resets the time to 00:00:00
     *
     * @return static
     */
    public function startOfDay()
    {
        return $this->setHour(0)->setMinute(0)->setSecond(0);
    }

    /**
     * Resets the time to 23:59:59
     *
     * @return static
     */
    public function endOfDay()
    {
        return $this->setHour(23)->setMinute(59)->setSecond(59);
    }

    /**
     * Resets the time to now, but with 0 minutes, 0 seconds.
     *
     * @return static
     */
    public function startOfHour()
    {
        return $this->setMinute(0)->setSecond(0);
    }

    /**
     * Resets the time to now, but with 59 minutes, 59 seconds.
     *
     * @return static
     */
    public function endOfHour()
    {
        return $this->setMinute(59)->setSecond(59);
    }

    /**
     * Resets the time to now, but with 0 seconds.
     *
     * @return static
     */
    public function startOfMinute()
    {
        return $this->setSecond(0);
    }

    /**
     * Resets the time to now, but with 59 seconds.
     *
     * @return static
     */
    public function endOfMinute()
    {
        return $this->setSecond(59);
    }

    /**
     * Modify to the next occurrence of a given day of the week.
     * If no dayOfWeek is provided, modify to the next occurrence
     * of the current day of the week.  Use the supplied consts
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * TODO Sinnvoll? Wenn ja, dokumentieren und public machen
     *
     * @param int|null $dayOfWeek
     *
     * @return static
     */
    private function next($dayOfWeek = null)
    {
        if ($dayOfWeek === null) {
            $dayOfWeek = $this->dayOfWeek();
        }

        $days = [
            self::SUNDAY => 'Sunday',
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
        ];

        $dow = $days[$dayOfWeek];

        return $this->startOfDay()->modify("next $dow"); // todo geht nicht auch assDays(7) ?
    }

    /**
     * Modify to the previous occurrence of a given day of the week.
     * If no dayOfWeek is provided, modify to the previous occurrence
     * of the current day of the week.  Use the supplied consts
     * to indicate the desired dayOfWeek, ex. static::MONDAY.
     *
     * TODO Sinnvoll? Wenn ja, dokumentieren und public machen
     *
     * @param int|null $dayOfWeek
     *
     * @return static
     */
    private function previous($dayOfWeek = null)
    {
        if ($dayOfWeek === null) {
            $dayOfWeek = $this->dayOfWeek();
        }

        $days = [
            self::SUNDAY => 'Sunday',
            self::MONDAY => 'Monday',
            self::TUESDAY => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY => 'Thursday',
            self::FRIDAY => 'Friday',
            self::SATURDAY => 'Saturday',
        ];

        $dow = $days[$dayOfWeek];

        return $this->startOfDay()->modify("last $dow"); // todo geht nicht auch subDays(7) ?
    }

    ///////////////////////////////////////////////////////////////////
    // Timezone

    /**
     * Set the default timezone.
     *
     * @param DateTimeZone|string $timezone
     */
    public static function setDefaultTimezone($timezone)
    {
        self::$timezone = $timezone;
    }

    /**
     * Get the default timezone.
     *
     * @return DateTimeZone
     */
    public static function getDefaultTimezone()
    {
        return self::$timezone;
    }

    /**
     * Set the actual timezone.
     *
     * @param DateTimeZone|string|null $timezone
     * @return $this
     */
    public function setTimezone($timezone)
    {
        parent::setTimezone(static::createTimeZone($timezone));

        return $this;
    }

    /**
     * Creates a DateTimeZone from a string or a DateTimeZone
     *
     * @param DateTimeZone|string|null $timezone
     * @throws InvalidArgumentException
     * @return DateTimeZone
     */
    private static function createTimeZone($timezone)
    {
        if ($timezone === null) {
            $def = config('app.timezone', date_default_timezone_get());
            return new DateTimeZone($def);
        }

        if ($timezone instanceof DateTimeZone) {
            return $timezone;
        }

        if (($tz = @timezone_open((string)$timezone)) === false) {
            throw new InvalidArgumentException('Unknown or bad timezone (' . $timezone . ')');
        }

        return $tz;
    }

    ///////////////////////////////////////////////////////////////////
    // Locale

    /**
     * Set the default locale.
     *
     * @param string $locale
     */
    public static function setDefaultLocale($locale)
    {
        self::$locale = $locale;
    }

    /**
     * Get the default locale.
     *
     * @return string
     */
    public static function getDefaultLocale()
    {
        return self::$locale;
    }

    /**
     * Set locale.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->actualLocale = $locale;

        return $this;
    }

    /**
     * Get the actual locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->actualLocale;
    }

    /**
     * Returns the locale date time format.
     *
     * @param string $part
     * @return string
     */
    private static function localeFormat($part = 'datetime')
    {
        $locale = config('app.locale');

        // todo

        return config('app.date_formats.' . $locale . '.' . $part); //t('datetime.time_fromat');
    }

    /**
     * Get the first day of week
     *
     * @return int
     */
    public static function getWeekStartsAt()
    {
        return static::$weekStartsAt;
    }

    /**
     * Set the first day of week
     *
     * @param int
     */
    public static function setWeekStartsAt($dow)
    {
        static::$weekStartsAt = $dow;
    }
}