<?php

namespace SimpleSchedule;

use Symfony\Component\Process\ProcessUtils;
use Symfony\Component\Process\PhpExecutableFinder;

class Schedule
{
    /**
     * All of the events on the schedule.
     *
     * @var \Events[]|
     */
    protected $events = [];

    /**
     * Add a new callback event to the schedule.
     *
     * @param  callable $callback
     * @param  array  $parameters
     *
     * @return \SimpleSchedule\Event
     */
    public function call($callback, array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent($callback, $parameters);

        return $event;
    }

    /**
     * Add a new Artisan command event to the schedule.
     *
     * @param  string $command
     * @param  array  $parameters
     *
     * @return \SimpleSchedule\Event
     */
    public function command($command, array $parameters = [])
    {
        $binary = ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false));

        if (defined('HHVM_VERSION')) {
            $binary .= ' --php';
        }

        return $this->exec("{$binary} {$command}", $parameters);
    }

    /**
     * Add a new command event to the schedule.
     *
     * @param  string $command
     * @param  array  $parameters
     *
     * @return \SimpleSchedule\Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event($command);

        return $event;
    }

    /**
     * Compile parameters for a command.
     *
     * @param  array $parameters
     *
     * @return string
     */
    protected function compileParameters(array $parameters)
    {
        $arRet = [];
        foreach ($parameters as $key => $value) {
            return is_numeric($key) ? $value : $key . '=' . (is_numeric($value) ? $value : ProcessUtils::escapeArgument($value));
        }
        array_map(function ($value, $key) {
        }, $parameters);
        return implode(' ', $arRet);
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return array
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @return Event[]
     */
    public function dueEvents()
    {
        return array_filter($this->events, function (Event $event) {
            return $event->isDue();
        });
    }
}
