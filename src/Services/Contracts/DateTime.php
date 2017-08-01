<?php

namespace Core\Services\Contracts;

use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;

interface DateTime extends DateTimeInterface, JsonSerializable
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
     * Get a copy of the instance
     *
     * @return static
     */
    public function copy();

    /**
     * Convert the DateTime instance into JSON serializable.
     *
     * @return string
     */
    public function jsonSerialize();

    ///////////////////////////////////////////////////////////////////
    // Create a DateTime Instance

    /**
     * Create a DateTime instance from a DateTime one
     *
     * @param DateTimeInterface $dateTime
     * @return static
     */
    public static function instance(DateTimeInterface $dateTime);

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
    public static function createFromParts(array $parts, $timezone = null);

    /**
     * Create a DateTime instance from a specific format
     *
     * @param string $format
     * @param string $dateTimeString
     * @param DateTimeZone|string|null $timezone
     * @throws InvalidArgumentException
     * @return static
     */
    public static function createFromFormat($format, $dateTimeString, $timezone = null);

    /**
     * Create a DateTime instance from a locale format
     *
     * @param string $dateTimeString
     * @param DateTimeZone|string|null $timezone
     * @return static
     */
    public static function createFromLocaleFormat($dateTimeString, $timezone = null);

    /**
     * Create a DateTime instance from a locale date format
     *
     * @param string $dateString
     * @param DateTimeZone|string|null $timezone
     * @return static
     */
    public static function createFromLocaleDateFormat($dateString, $timezone = null);

    /**
     * Create a DateTime instance from a locale time format
     *
     * @param string $timeString
     * @param DateTimeZone|string|null $timezone
     * @return static
     */
    public static function createFromLocaleTimeFormat($timeString, $timezone = null);

    /**
     * Create a DateTime instance from a timestamp.
     *
     * @param int $timestamp
     * @param DateTimeZone|string|null $timezone
     * @return static
     */
    public static function createFromTimestamp($timestamp, $timezone = null);

    /**
     * Create a DateTime instance from an UTC timestamp.
     *
     * @param int $timestamp
     * @return static
     */
    public static function createFromTimestampUTC($timestamp);

    ///////////////////////////////////////////////////////////////////
    // String Formatting

    /**
     * Format the instance as date and time.
     *
     * @return string
     */
    public function toDateTimeString();

    /**
     * Format the instance as date
     *
     * @return string
     */
    public function toDateString();

    /**
     * Format the instance as time
     *
     * @return string
     */
    public function toTimeString();

    /**
     * Format the instance as date and time.
     *
     * @return string
     */
    public function toLocaleDateTimeString();

    /**
     * Format the instance as date
     *
     * @return string
     */
    public function toLocaleDateString();

    /**
     * Format the instance as time
     *
     * @return string
     */
    public function toLocaleTimeString();

    /**
     * Format the instance as ATOM
     *
     * @return string
     */
    public function toAtomString();

    /**
     * Format the instance as COOKIE
     *
     * @return string
     */
    public function toCookieString();

    /**
     * Format the instance as ISO8601
     *
     * Note: This format is not compatible with ISO-8601! Use DateTime::ATOM or DATE_ATOM instead.
     * see http://de2.php.net/manual/en/class.datetime.php#datetime.constants.iso8601
     *
     * @return string
     */
    public function toIso8601String();

    /**
     * Format the instance as RFC822
     *
     * @return string
     */
    public function toRfc822String();

    /**
     * Format the instance as RFC850
     *
     * @return string
     */
    public function toRfc850String();

    /**
     * Format the instance as RFC1036
     *
     * @return string
     */
    public function toRfc1036String();

    /**
     * Format the instance as RFC1123
     *
     * @return string
     */
    public function toRfc1123String();

    /**
     * Format the instance as RFC2822
     *
     * @return string
     */
    public function toRfc2822String();

    /**
     * Format the instance as RFC3339
     *
     * @return string
     */
    public function toRfc3339String();

    /**
     * Format the instance as RSS
     *
     * @return string
     */
    public function toRssString();

    /**
     * Format the instance as W3C
     *
     * @return string
     */
    public function toW3cString();

    ///////////////////////////////////////////////////////////////////
    // Parts of Date Time

    /**
     * Get the year.
     *
     * @return int
     */
    public function getYear();

    /**
     * Set the year.
     *
     * @param $year
     * @return \Core\Services\DateTime
     */
    public function setYear($year);

    /**
     * Get the month.
     *
     * @return int
     */
    public function getMonth();

    /**
     * Set the month.
     *
     * @param $month
     * @return \Core\Services\DateTime
     */
    public function setMonth($month);

    /**
     * Get the day of month.
     *
     * @return int
     */
    public function getDay();

    /**
     * Set the day of month.
     *
     * @param $day
     * @return \Core\Services\DateTime
     */
    public function setDay($day);

    /**
     * Get the hour.
     *
     * @return int
     */
    public function getHour();

    /**
     * Set the hour.
     *
     * @param $hour
     * @return \Core\Services\DateTime
     */
    public function setHour($hour);

    /**
     * Get the minute.
     *
     * @return int
     */
    public function getMinute();

    /**
     * Set the minute.
     *
     * @param $minute
     * @return \Core\Services\DateTime
     */
    public function setMinute($minute);

    /**
     * Get the second.
     *
     * @return int
     */
    public function getSecond();

    /**
     * Set the second.
     *
     * @param $second
     * @return \Core\Services\DateTime
     */
    public function setSecond($second);

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
    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0);

    /**
     * Returns the microseconds.
     *
     * @return int
     */
    public function micro();

    /**
     * Returns the day of the week.
     *
     * @return int 0 (for Sunday) through 6 (for Saturday)
     */
    public function dayOfWeek();

    /**
     * Returns the day of the year (starting from 0).
     *
     * @return int 0 through 365
     */
    public function dayOfYear();

    /**
     * Week number of month.
     *
     * @return int
     */
    public function weekOfMonth();

    /**
     * ISO-8601 week number of year.
     *
     * Weeks starting on Monday.
     *
     * @return int
     */
    public function weekOfYear();

    /**
     * Number of days in the month.
     *
     * @return int
     */
    public function daysInMonth();

    /**
     * Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT).
     *
     * @return int
     */
    public function timestamp();

    /**
     * Alias for diffInYears().
     *
     * @return int
     */
    public function age();

    /**
     * Returns the quarter of the year.
     *
     * @return int
     */
    public function quarter();

    /**
     * Determines if the instance is a leap year
     *
     * @return bool
     */
    public function isLeapYear();

    /**
     * Checks if this day is a Sunday.
     *
     * @return bool
     */
    public function isSunday();

    /**
     * Checks if this day is a Monday.
     *
     * @return bool
     */
    public function isMonday();

    /**
     * Checks if this day is a Tuesday.
     *
     * @return bool
     */
    public function isTuesday();

    /**
     * Checks if this day is a Wednesday.
     *
     * @return bool
     */
    public function isWednesday();

    /**
     * Checks if this day is a Thursday.
     *
     * @return bool
     */
    public function isThursday();

    /**
     * Checks if this day is a Friday.
     *
     * @return bool
     */
    public function isFriday();

    /**
     * Checks if this day is a Saturday.
     *
     * @return bool
     */
    public function isSaturday();

    ///////////////////////////////////////////////////////////////////
    // Addition and Subtraction

    /**
     * Add years to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addYears($value);

    /**
     * Subtract years from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subYears($value);

    /**
     * Add quarters to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addQuarters($value);

    /**
     * Subtract quarters from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subQuarters($value);

    /**
     * Add months to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addMonths($value);

    /**
     * Subtract months from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subMonths($value);

    /**
     * Add weeks to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addWeeks($value);

    /**
     * Subtract weeks from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subWeeks($value);

    /**
     * Add days to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addDays($value);

    /**
     * Subtract days from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subDays($value);

    /**
     * Add hours to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addHours($value);

    /**
     * Subtract hours from the instance
     *
     * @param int $value
     *
     * @return static
     */
    public function subHours($value);

    /**
     * Add minutes to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addMinutes($value);

    /**
     * Subtract minutes from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subMinutes($value);

    /**
     * Add seconds to the instance.
     *
     * @param int $value
     * @return static
     */
    public function addSeconds($value);

    /**
     * Subtract seconds from the instance.
     *
     * @param int $value
     * @return static
     */
    public function subSeconds($value);

    ///////////////////////////////////////////////////////////////////
    // Differences

    /**
     * Get the difference in years.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInYears(DateTimeInterface $dt = null, $abs = true);

    /**
     * Get the difference in quarters.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInQuarters(DateTimeInterface $dt = null, $abs = true);

    /**
     * Get the difference in months.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInMonths(DateTimeInterface $dt = null, $abs = true);

    /**
     * Get the difference in weeks.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInWeeks(DateTimeInterface $dt = null, $abs = true);

    /**
     * Get the difference in days.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInDays(DateTimeInterface $dt = null, $abs = true);

    /**
     * Get the difference in hours.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInHours(DateTimeInterface $dt = null, $abs = true);

    /**
     * Get the difference in minutes.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInMinutes(DateTimeInterface $dt = null, $abs = true);

    /**
     * Get the difference in seconds.
     *
     * @param DateTimeInterface|null $dt
     * @param bool $abs Get the absolute of the difference
     * @return int
     */
    public function diffInSeconds(DateTimeInterface $dt = null, $abs = true);

    ///////////////////////////////////////////////////////////////////
    // Start Of and End Of

    /**
     * Resets the date to the first day of the year and the time to 00:00:00
     *
     * @return static
     */
    public function startOfYear();

    /**
     * Resets the date to end of the year and time to 23:59:59
     *
     * @return static
     */
    public function endOfYear();

    /**
     * Resets the date to the beginning of the current quarter, 1st day of months, 00:00
     *
     * @return static
     */
    public function startOfQuarter();

    /**
     * Resets the date to end of the year and time to 23:59:59
     *
     * @return static
     */
    public function endOfQuarter();

    /**
     * Resets the date to the first day of the month and the time to 00:00:00
     *
     * @return static
     */
    public function startOfMonth();

    /**
     * Resets the date to end of the month and time to 23:59:59
     *
     * @return static
     */
    public function endOfMonth();

    /**
     * Resets the date to the first day of week (defined in $weekStartsAt) and the time to 00:00:00
     *
     * @return static
     */
    public function startOfWeek();

    /**
     * Resets the date to end of week (defined in $weekEndsAt) and time to 23:59:59
     *
     * @return static
     */
    public function endOfWeek();

    /**
     * Resets the time to 00:00:00
     *
     * @return static
     */
    public function startOfDay();

    /**
     * Resets the time to 23:59:59
     *
     * @return static
     */
    public function endOfDay();

    /**
     * Resets the time to now, but with 0 minutes, 0 seconds.
     *
     * @return static
     */
    public function startOfHour();

    /**
     * Resets the time to now, but with 59 minutes, 59 seconds.
     *
     * @return static
     */
    public function endOfHour();

    /**
     * Resets the time to now, but with 0 seconds.
     *
     * @return static
     */
    public function startOfMinute();

    /**
     * Resets the time to now, but with 59 seconds.
     *
     * @return static
     */
    public function endOfMinute();

    ///////////////////////////////////////////////////////////////////
    // Locale

    /**
     * Get the default timezone.
     *
     * @return DateTimeZone
     */
    public static function getDefaultTimezone();

    /**
     * Set the default timezone.
     *
     * If null is passed, the default will be reset.
     *
     * @param DateTimeZone|string|null $timezone
     */
    public static function setDefaultTimezone($timezone = null);

    /**
     * Set the actual timezone.
     *
     * If null is passed, the default will be used.
     *
     * @param DateTimeZone|string $timezone
     * @return \Core\Services\DateTime
     */
    public function setTimezone($timezone);

    /**
     * Get the first day of week
     *
     * @return int
     */
    public static function getFirstDayOfWeek();

    /**
     * Set the first day of week
     *
     * @param int
     */
    public static function setFirstDayOfWeek($dow);

    /**
     * Get the locale.
     *
     * @return string
     */
    public static function getLocale();

    /**
     * Set the locale.
     *
     * @param string $locale
     */
    public static function setLocale($locale);

    /**
     * Set the locale format.
     *
     * @param array $format
     */
    public static function setLocaleFormat(array $format);
}
