<?php

namespace Bab\CrontabViewer;

use CalendR\Event\AbstractEvent;

class CronEvent extends AbstractEvent
{
    protected $begin;
    protected $end;
    protected $command;

    public function __construct($command, \DateTime $begin, \DateTime $end = null)
    {
        $this->command = $command;
        $this->begin = clone $begin;
        if (null === $end) {
            $end = clone $begin;
            $end->add(new \DateInterval('PT1M'));

            $this->end = $end;
        } else {
            $this->end = clone $end;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getUid()
    {
        return sha1($this->command . $this->begin->format('d-m-Y-H-i-s') . $this->end->format('d-m-Y-H-i-s'));
    }

    /**
     * {@inheritDoc}
     */
    public function getBegin()
    {
        return $this->begin;
    }

    /**
     * {@inheritDoc}
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * getCommand
     *
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }
}
