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
 * Function createTimeZone() based on Carbon (https://github.com/briannesbitt/Carbon/blob/1.22.1/LICENSE MIT license).
 * Function createFromFormat(), instance(), and functions for addition/subtraction based on Chronos
 *
 * @see https://github.com/briannesbitt/Carbon/blob/1.22.1/src/Carbon/Carbon.php
 * @see https://github.com/cakephp/chronos/blob/1.1.0/src/Traits/FactoryTrait.php
 * @see https://github.com/cakephp/chronos/blob/1.1.0/src/Traits/ModifierTrait.php
 */
class DateTime extends BaseDateTime implements DateTimeContract
{
    /**
     * The default timezone.
     *
     * @var DateTimeZone
     */
    private static $timezone;

    /**
     * First day of week (will be loaded from the config files).
     *
     * @var int
     */
    private static $firstDayOfWeek;
    
    /**
     * The locale (will be loaded from the config files).
     *
     * @var string
     */
    private static $locale;
    
    /**
     * The local date time format (will be loaded from the language files).
     *
     * @var array
     */
    private static $localeFormat;

    /**
     * Create a new DateTime instance.
     *
     * @param int|DateTimeInterface|array|string|null $dateTimeString
     * @param DateTimeZone|string|null $timezone
     */
    public function __construct($dateTimeString = null, $timezone = null)
    {
        if (is_string($timezone)) {
            $timezone = self::createTimezone($timezone);
        }

        parent::__construct($dateTimeString, $timezone);
    }

    /**
     * Returns new DateTimeZone object.
     *
     * @param string $name
     * @return DateTimeZone
     */
    private static function createTimezone($name)
    {
        if (($timezone = @timezone_open($name)) === false) {
            throw new InvalidArgumentException('Unknown or bad timezone: ' . $name . '!');
        }

        return $timezone;
    }

    /**
     * @inheritdoc
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * Format the instance as a string using the default setting.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return (string)$this->toIso8601String();
    }

    ///////////////////////////////////////////////////////////////////
    // Create a DateTime Instance

    /**
     * @inheritdoc
     */
    public static function instance(DateTimeInterface $dateTime)
    {
        if ($dateTime instanceof static) {
            return clone $dateTime;
        }

        return new static($dateTime->format('Y-m-d H:i:s.u'), $dateTime->getTimezone());
    }

    /**
     * Create a new DateTime instance from a specific parts of date and time.
     *
     * The default of any part of date (year, month or day) is today.
     * The default of any part of time (hour, minute, second or microsecond) is 0.
     *
     * @param array $parts [$year, $month, $day, $hour, $minute, $second, $micro] All parts are optional.
     * @param DateTimeZone|string|null $timezone
     * @return static
     */
    public static function createFromParts(array $parts, $timezone = null)
    {
        $year   = isset($parts[0]) ? $parts[0] : date('Y');
        $month  = isset($parts[1]) ? $parts[1] : date('m');
        $day    = isset($parts[2]) ? $parts[2] : date('d');
        $hour   = isset($parts[3]) ? $parts[3] : 0; //date('H');
        $minute = isset($parts[4]) ? $parts[4] : 0; //date('i');
        $second = isset($parts[5]) ? $parts[5] : 0; //date('s');
        $micro  = isset($parts[6]) ? $parts[6] : 0; //date('u');
        $dateTimeString = sprintf('%04s-%02s-%02s %02s:%02s:%02s.%06s', $year, $month, $day, $hour, $minute, $second, $micro);

        return new static($dateTimeString, $timezone);
    }

    /**
     * @inheritdoc
     */
    public static function createFromFormat($format, $dateTimeString, $timezone = null)
    {
        $dateTime = parent::createFromFormat($format, $dateTimeString);

        if ($dateTime === false) {
            $errors = parent::getLastErrors();
            throw new InvalidArgumentException(implode(PHP_EOL, $errors['errors']));
        }

        return new static($dateTime->format('Y-m-d H:i:s.u'), $timezone);
    }

    /**
     * @inheritdoc
     */
    public static function createFromLocaleFormat($dateTimeString, $timezone = null)
    {
        return static::createFromFormat(self::localeFormat(), $dateTimeString, $timezone);
    }

    /**
     * @inheritdoc
     */
    public static function createFromLocaleDateFormat($dateString, $timezone = null)
    {
        return static::createFromFormat(self::localeFormat('date'), $dateString, $timezone);
    }

    /**
     * @inheritdoc
     */
    public static function createFromLocaleTimeFormat($timeString, $timezone = null)
    {
        return static::createFromFormat(self::localeFormat('time'), $timeString, $timezone);
    }

    /**
     * @inheritdoc
     */
    public static function createFromTimestamp($timestamp, $timezone = null)
    {
        return (new static(null, $timezone))->setTimestamp($timestamp);
    }

    /**
     * @inheritdoc
     */
    public static function createFromTimestampUTC($timestamp)
    {
        return new static('@'.$timestamp);
    }

    ///////////////////////////////////////////////////////////////////
    // String Formatting

    /**
     * @inheritdoc
     */
    public function toDateTimeString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * @inheritdoc
     */
    public function toDateString()
    {
        return $this->format('Y-m-d');
    }

    /**
     * @inheritdoc
     */
    public function toTimeString()
    {
        return $this->format('H:i:s');
    }

    /**
     * @inheritdoc
     */
    public function toLocaleDateTimeString()
    {
        return $this->format(self::localeFormat());
    }

    /**
     * @inheritdoc
     */
    public function toLocaleDateString()
    {
        return $this->format(self::localeFormat('date'));
    }

    /**
     * @inheritdoc
     */
    public function toLocaleTimeString()
    {
        return $this->format(self::localeFormat('time'));
    }

    /**
     * @inheritdoc
     */
    public function toAtomString()
    {
        return $this->format(static::ATOM);
    }

    /**
     * @inheritdoc
     */
    public function toCookieString()
    {
        return $this->format(static::COOKIE);
    }

    /**
     * @inheritdoc
     */
    public function toIso8601String()
    {
        // TODO
        // Note: This format is not compatible with ISO-8601! Use DateTime::ATOM or DATE_ATOM instead.
        // see http://de2.php.net/manual/en/class.datetime.php#datetime.constants.iso8601

        return $this->format(static::ISO8601);
    }

    /**
     * @inheritdoc
     */
    public function toRfc822String()
    {
        return $this->format(static::RFC822);
    }

    /**
     * @inheritdoc
     */
    public function toRfc850String()
    {
        return $this->format(static::RFC850);
    }

    /**
     * @inheritdoc
     */
    public function toRfc1036String()
    {
        return $this->format(static::RFC1036);
    }

    /**
     * @inheritdoc
     */
    public function toRfc1123String()
    {
        return $this->format(static::RFC1123);
    }

    /**
     * @inheritdoc
     */
    public function toRfc2822String()
    {
        return $this->format(static::RFC2822);
    }

    /**
     * @inheritdoc
     */
    public function toRfc3339String()
    {
        return $this->format(static::RFC3339);
    }

    /**
     * @inheritdoc
     */
    public function toRssString()
    {
        return $this->format(static::RSS);
    }

    /**
     * @inheritdoc
     */
    public function toW3cString()
    {
        return $this->format(static::W3C);
    }

    ///////////////////////////////////////////////////////////////////
    // Parts of Date Time

    /**
     * @inheritdoc
     */
    public function getYear()
    {
        return (int)$this->format('Y');
    }

    /**
     * @inheritdoc
     */
    public function setYear($year)
    {
        $this->setDate($year, $this->getMonth(), $this->getDay());

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMonth()
    {
        return (int)$this->format('n');
    }

    /**
     * @inheritdoc
     */
    public function setMonth($month)
    {
        $this->setDate($this->getYear(), $month, $this->getDay());

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDay()
    {
        return (int)$this->format('j');
    }

    /**
     * @inheritdoc
     */
    public function setDay($day)
    {
        $this->setDate($this->getYear(), $this->getMonth(), $day);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getHour()
    {
        return (int)$this->format('G');
    }

    /**
     * @inheritdoc
     */
    public function setHour($hour)
    {
        $this->setTime($hour, $this->getMinute(), $this->getSecond());

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMinute()
    {
        return (int)$this->format('i');
    }

    /**
     * @inheritdoc
     */
    public function setMinute($minute)
    {
        $this->setTime($this->getHour(), $minute, $this->getSecond());

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSecond()
    {
        return (int)$this->format('s');
    }

    /**
     * @inheritdoc
     */
    public function setSecond($second)
    {
        $this->setTime($this->getHour(), $this->getMinute(), $second);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0)
    {
        return $this->setDate($year, $month, $day)->setTime($hour, $minute, $second);
    }

    /**
     * @inheritdoc
     */
    public function micro()
    {
        return (int)$this->format('u');
    }

    /**
     * @inheritdoc
     */
    public function dayOfWeek()
    {
        return (int)$this->format('w');
    }

    /**
     * @inheritdoc
     */
    public function dayOfYear()
    {
        return (int)$this->format('z');
    }

    /**
     * @inheritdoc
     */
    public function weekOfMonth()
    {
        return (int)ceil($this->getDay() / 7);
    }

    /**
     * @inheritdoc
     */
    public function weekOfYear()
    {
        return (int)$this->format('W');
    }

    /**
     * @inheritdoc
     */
    public function daysInMonth()
    {
        return (int)$this->format('t');
    }

    /**
     * @inheritdoc
     */
    public function timestamp()
    {
        return (int)$this->format('U');
    }

    /**
     * @inheritdoc
     */
    public function age()
    {
        return (int)$this->diffInYears();
    }

    /**
     * @inheritdoc
     */
    public function quarter()
    {
        return (int)ceil($this->getMonth() / 3);
    }

    /**
     * @inheritdoc
     */
    public function isLeapYear()
    {
        return $this->format('L') === '1';
    }

    /**
     * @inheritdoc
     */
    public function isSunday()
    {
        return $this->dayOfWeek() === static::SUNDAY;
    }

    /**
     * @inheritdoc
     */
    public function isMonday()
    {
        return $this->dayOfWeek() === static::MONDAY;
    }

    /**
     * @inheritdoc
     */
    public function isTuesday()
    {
        return $this->dayOfWeek() === static::TUESDAY;
    }

    /**
     * @inheritdoc
     */
    public function isWednesday()
    {
        return $this->dayOfWeek() === static::WEDNESDAY;
    }

    /**
     * @inheritdoc
     */
    public function isThursday()
    {
        return $this->dayOfWeek() === static::THURSDAY;
    }

    /**
     * @inheritdoc
     */
    public function isFriday()
    {
        return $this->dayOfWeek() === static::FRIDAY;
    }

    /**
     * @inheritdoc
     */
    public function isSaturday()
    {
        return $this->dayOfWeek() === static::SATURDAY;
    }

    ///////////////////////////////////////////////////////////////////
    // Addition and Subtraction

    /**
     * @inheritdoc
     */
    public function addYears($value)
    {
        $value = (int)$value;

        return $this->modify("$value year");
    }

    /**
     * @inheritdoc
     */
    public function subYears($value)
    {
        $value = (int)$value;

        return $this->modify("-$value year");
    }

    /**
     * @inheritdoc
     */
    public function addQuarters($value)
    {
        $value = (int)$value * 3;

        return $this->modify("$value month");
    }

    /**
     * @inheritdoc
     */
    public function subQuarters($value)
    {
        $value = (int)$value * 3;

        return $this->modify("-$value month");
    }

    /**
     * @inheritdoc
     */
    public function addMonths($value)
    {
        $value = (int)$value;

        return $this->modify("$value month");
    }

    /**
     * @inheritdoc
     */
    public function subMonths($value)
    {
        $value = (int)$value;

        return $this->modify("-$value month");
    }

    /**
     * @inheritdoc
     */
    public function addWeeks($value)
    {
        $value = (int)$value;

        return $this->modify("$value week");
    }

    /**
     * @inheritdoc
     */
    public function subWeeks($value)
    {
        $value = (int)$value;

        return $this->modify("-$value week");
    }

    /**
     * @inheritdoc
     */
    public function addDays($value)
    {
        $value = (int)$value;

        return $this->modify("$value day");
    }

    /**
     * @inheritdoc
     */
    public function subDays($value)
    {
        $value = (int)$value;

        return $this->modify("-$value day");
    }

    /**
     * @inheritdoc
     */
    public function addHours($value)
    {
        $value = (int)$value;

        return $this->modify("$value hour");
    }

    /**
     * @inheritdoc
     */
    public function subHours($value)
    {
        $value = (int)$value;

        return $this->modify("-$value hour");
    }

    /**
     * @inheritdoc
     */
    public function addMinutes($value)
    {
        $value = (int)$value;

        return $this->modify("$value minute");
    }

    /**
     * @inheritdoc
     */
    public function subMinutes($value)
    {
        $value = (int)$value;

        return $this->modify("-$value minute");
    }

    /**
     * @inheritdoc
     */
    public function addSeconds($value)
    {
        $value = (int)$value;

        return $this->modify("$value second");
    }

    /**
     * @inheritdoc
     */
    public function subSeconds($value)
    {
        $value = (int)$value;

        return $this->modify("-$value second");
    }

    ///////////////////////////////////////////////////////////////////
    // Differences

    /**
     * @inheritdoc
     */
    public function diffInYears(DateTimeInterface $dt = null, $abs = true)
    {
        if ($dt === null) {
            $dt = new static(null, $this->getTimezone());
        }

        return (int)$this->diff($dt, $abs)->format('%r%y');
    }

    /**
     * @inheritdoc
     */
    public function diffInQuarters(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInMonths($dt, $abs) / 3);
    }

    /**
     * @inheritdoc
     */
    public function diffInMonths(DateTimeInterface $dt = null, $abs = true)
    {
        if ($dt === null) {
            $dt = new static(null, $this->getTimezone());
        }

        return $this->diffInYears($dt, $abs) * 12 + (int)$this->diff($dt, $abs)->format('%r%m');
    }

    /**
     * @inheritdoc
     */
    public function diffInWeeks(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInDays($dt, $abs) / 7);
    }

    /**
     * @inheritdoc
     */
    public function diffInDays(DateTimeInterface $dt = null, $abs = true)
    {
        if ($dt === null) {
            $dt = new static(null, $this->getTimezone());
        }

        return (int)$this->diff($dt, $abs)->format('%r%a');
    }

    /**
     * @inheritdoc
     */
    public function diffInHours(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInSeconds($dt, $abs) / 3600);
    }

    /**
     * @inheritdoc
     */
    public function diffInMinutes(DateTimeInterface $dt = null, $abs = true)
    {
        return (int)($this->diffInSeconds($dt, $abs) / 60);
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function startOfYear()
    {
        return $this->setMonth(1)->startOfMonth();
    }

    /**
     * @inheritdoc
     */
    public function endOfYear()
    {
        return $this->setMonth(12)->endOfMonth();
    }

    /**
     * @inheritdoc
     */
    public function startOfQuarter()
    {
        $quarter = $this->quarter();

        return $this->setMonth($quarter * 3)->startOfMonth(); // todo testen
    }

    /**
     * @inheritdoc
     */
    public function endOfQuarter()
    {
        $quarter = $this->quarter();

        return $this->setMonth($quarter * 3 + 2)->endOfMonth(); // todo testen
    }

    /**
     * @inheritdoc
     */
    public function startOfMonth()
    {
        return $this->startOfDay()->setDay(1);
    }

    /**
     * @inheritdoc
     */
    public function endOfMonth()
    {
        return $this->setDay($this->daysInMonth())->endOfDay();
    }

    /**
     * @inheritdoc
     */
    public function startOfWeek()
    {
        $dow = self::getFirstDayOfWeek();
        if ($this->dayOfWeek() !== $dow) {
            $this->previous($dow);
        }

        return $this->startOfDay();
    }

    /**
     * @inheritdoc
     */
    public function endOfWeek()
    {
        $dow = self::getFirstDayOfWeek();
        $weekEndsAt = $dow > 0 ? $dow - 1 : 6;
        if ($this->dayOfWeek() !== $weekEndsAt) {
            $this->next($weekEndsAt);
        }

        return $this->endOfDay();
    }

    /**
     * @inheritdoc
     */
    public function startOfDay()
    {
        return $this->setHour(0)->setMinute(0)->setSecond(0);
    }

    /**
     * @inheritdoc
     */
    public function endOfDay()
    {
        return $this->setHour(23)->setMinute(59)->setSecond(59);
    }

    /**
     * @inheritdoc
     */
    public function startOfHour()
    {
        return $this->setMinute(0)->setSecond(0);
    }

    /**
     * @inheritdoc
     */
    public function endOfHour()
    {
        return $this->setMinute(59)->setSecond(59);
    }

    /**
     * @inheritdoc
     */
    public function startOfMinute()
    {
        return $this->setSecond(0);
    }

    /**
     * @inheritdoc
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
            self::SUNDAY    => 'Sunday',
            self::MONDAY    => 'Monday',
            self::TUESDAY   => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY  => 'Thursday',
            self::FRIDAY    => 'Friday',
            self::SATURDAY  => 'Saturday',
        ];

        $dow = $days[$dayOfWeek];

        return $this->startOfDay()->modify("next $dow"); // todo geht nicht auch addDays(7) ?
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
            self::SUNDAY    => 'Sunday',
            self::MONDAY    => 'Monday',
            self::TUESDAY   => 'Tuesday',
            self::WEDNESDAY => 'Wednesday',
            self::THURSDAY  => 'Thursday',
            self::FRIDAY    => 'Friday',
            self::SATURDAY  => 'Saturday',
        ];

        $dow = $days[$dayOfWeek];

        return $this->startOfDay()->modify("last $dow"); // todo geht nicht auch subDays(7) ?
    }

    ///////////////////////////////////////////////////////////////////
    // Settings

    /**
     * @inheritdoc
     */
    public static function getDefaultTimezone()
    {
        if (self::$timezone === null) {
            self::$timezone = new DateTimeZone(date_default_timezone_get() ?: 'UTC');
        }

        return self::$timezone;
    }

    /**
     * @inheritdoc
     */
    public static function setDefaultTimezone($timezone)
    {
        if ($timezone instanceof DateTimeZone) {
            self::$timezone = $timezone;
            date_default_timezone_set($timezone->getName());
        }
        else {
            if ($timezone === null) {
                $timezone = config('app.timezone', 'UTC');
            }
            self::$timezone = self::createTimezone($timezone);
            date_default_timezone_set($timezone);
        }
    }

    /**
     * @inheritdoc
     */
    public function setTimezone($timezone)
    {
        if ($timezone === null) {
            $timezone = self::getDefaultTimezone();
        }
        else if (is_string($timezone)) {
            $timezone = self::createTimezone($timezone);
        }

        parent::setTimezone($timezone);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public static function getFirstDayOfWeek()
    {
        if (self::$firstDayOfWeek === null) {
            self::$firstDayOfWeek = config('app.first_dow');
        }

        return static::$firstDayOfWeek;
    }

    /**
     * @inheritdoc
     */
    public static function setFirstDayOfWeek($dow)
    {
        static::$firstDayOfWeek = $dow;
    }

    /**
     * @inheritdoc
     */
    public static function getLocale()
    {
        if (self::$locale === null) {
            self::$locale = config('app.locale');
        }

        return self::$locale;
    }

    /**
     * @inheritdoc
     */
    public static function setLocale($locale)
    {
        if (self::$locale !== $locale) {
            self::$localeFormat = null;
            self::$locale = $locale;    
        }
    }
    
    /**
     * Returns the locale date time format.
     *
     * @param string $part
     * @return string
     */
    private static function localeFormat($part = 'datetime')
    {
        if (self::$localeFormat === null) {
            $locale = self::getLocale();
            $file = resource_path('lang/' . $locale . '/datetime.php');
            if (@file_exists($file)) {
                /** @noinspection PhpIncludeInspection */
                self::$localeFormat = include $file;
            }
            else {
                $file = resource_path('lang/' . config('app.fallback_locale') . '/datetime.php');
                /** @noinspection PhpIncludeInspection */
                self::$localeFormat = @file_exists($file) ? include $file : ['datetime' => 'Y-m-d H:i', 'date' => 'Y-m-d', 'time' => 'H:i'];
            }
        }

        return self::$localeFormat[$part];
    }
}