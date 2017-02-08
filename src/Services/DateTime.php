<?php

namespace Core\Services;

use Core\Services\Contracts\DateTime as DateTimeContract;

/**
 * A simple PHP API extension for DateTime
 *
 * @see https://github.com/briannesbitt/Carbon Carbon on GitHub
 * @see https://github.com/fightbulc/moment.php moment.php on GitHub
 */
class DateTime extends \DateTime implements DateTimeContract
{
    /**
     * @var string
     */
    private $dateTime;

    /**
     * Create a new DateTime instance.
     *
     * @param string $dateTime
     */
    public function __construct($dateTime = 'now')
    {
        $this->dateTime = $dateTime;
    }
}