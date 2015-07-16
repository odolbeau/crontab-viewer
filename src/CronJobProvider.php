<?php

namespace Bab\CrontabViewer;

use CalendR\Event\Provider\ProviderInterface;
use Bab\CrontabViewer\CronEvent;

class CronJobProvider implements ProviderInterface
{
    protected $minutes;
    protected $hours;
    protected $days;
    protected $months;
    protected $weekdays;
    protected $command;

    private function __construct($minutes, $hours, $days, $months, $weekdays, $command)
    {
        $this->minutes  = $this->getRelevantNumbers($minutes, 59);
        $this->hours    = $this->getRelevantNumbers($hours, 23);
        $this->days     = $this->getRelevantNumbers($days, 30);
        $this->months   = $this->getRelevantNumbers($months, 11);
        $this->weekdays = $this->getRelevantNumbers($weekdays, 6);
        $this->command  = $command;
    }

    /**
     * createFromCrontab
     *
     * @param string $config
     *
     * @return EventInterface[]
     */
    public static function createFromCrontab($config)
    {
        $parts = explode(' ', $config);

        $minutes  = array_shift($parts);
        $hours    = array_shift($parts);
        $days     = array_shift($parts);
        $months   = array_shift($parts);
        $weekdays = array_shift($parts);

        $command = implode($parts, ' ');

        return new self($minutes, $hours, $days, $months, $weekdays, $command);
    }

    /**
     * {@inheritDoc}
     */
    public function getEvents(\DateTime $begin, \DateTime $end, array $options = array())
    {
        // Edge cases
        if ($this->isEveryMonths() && $this->isEveryDays() && $this->isEveryHours()) {
            return [
                new CronEvent($this->command, $begin, $end)
            ];
        }

        $events = [];

        foreach ($this->months as $month) {
            foreach ($this->days as $day) {
                foreach ($this->hours as $hour) {
                    foreach ($this->minutes as $minute) {
                        $date = new \DateTime();
                        $date->setDate($date->format('Y'), $month, $day);
                        $date->setTime($hour, $minute, 0);

                        $events[] = new CronEvent($this->command, $date);
                    }
                }
            }
        }

        return $events;
    }

    /**
     * isEveryMonths
     *
     * @return boolean
     */
    public function isEveryMonths()
    {
        return 12 === count($this->months);
    }

    /**
     * isEveryDays
     *
     * @return boolean
     */
    public function isEveryDays()
    {
        return 31 === count($this->days);
    }

    /**
     * isEveryHours
     *
     * @return boolean
     */
    public function isEveryHours()
    {
        return 24 === count($this->hours);
    }

    /**
     * isEveryMinutes
     *
     * @return boolean
     */
    public function isEveryMinutes()
    {
        return 60 === count($this->minutes);
    }

    /**
     * getRelevantNumbers
     *
     * @param string $string
     * @param int    $max
     *
     * @return array
     */
    private function getRelevantNumbers($string, $max)
    {
        if ('*' === $string) {
            return range(0, $max);
        }

        if (false !== strpos($string, '/')) {
            $parts = explode('/', $string);

            return range(0, $max, $parts[1]);
        }

        $numbers = explode(',', $string);

        return array_filter($numbers, function (&$value) {
            $value = (int) $value;

            return true;
        });
    }
}
