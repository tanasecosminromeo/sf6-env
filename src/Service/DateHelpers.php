<?php

namespace App\Service;

use DateTimeImmutable;
use DateTimeZone;

class DateHelpers
{
    private ?string $defaultTimezone;

    public function __construct(?string $defaultTimezone = null)
    {
        $this->defaultTimezone = $defaultTimezone;
    }

    /**
     * Convert a millisecond timestamp to a DateTimeImmutable object.
     *
     * @param string|int $milliseconds The Unix timestamp in milliseconds
     * @param string|null $timezone Optional timezone (uses default if not provided)
     * @return DateTimeImmutable
     */
    public function millisecondsToDateTime(string|int $milliseconds, ?string $timezone = null): DateTimeImmutable
    {
        // Convert milliseconds to seconds (PHP uses second-based timestamps)
        $seconds = (int)($milliseconds / 1000);
        
        // Create DateTimeImmutable from the timestamp (UTC)
        $dateTime = new DateTimeImmutable('@' . $seconds);
        
        // Apply timezone if provided
        if ($timezone) {
            return $dateTime->setTimezone(new DateTimeZone($timezone));
        } elseif ($this->defaultTimezone) {
            return $dateTime->setTimezone(new DateTimeZone($this->defaultTimezone));
        }
        
        return $dateTime;
    }
}