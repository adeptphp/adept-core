<?php

namespace Adept\Console;

use Cron\CronExpression;

abstract class Cron
{
    protected $cron;

    /**
     * $schedule holds the string representation of the cron expression
     * @var string
     *
     * Options include:
     * '* * * * *' custom setting
     * '@everyMinute', '@everyFiveMinutes', '@everyTenMinutes','@everyFifteenMinutes'
     * '@everyThirtyMinutes', '@hourly', '@everyThreeHours', '@everyFourHours', '@everyEightHours'
     * '@daily', '@weekly', '@monthly', '@quarterly', '@yearly', '@sunday', '@monday' , '@teusday'
     * '@wednesday', '@thursday', '@friday', '@saturday', '@noon', '@midnight'
     */
    protected $schedule;

    protected $periods = [
        '@everyMinute'          => '* * * * *',       // Run the task every minute
        '@everyFiveMinutes'     => '*/5 * * * *',     // Run the task every five minutes
        '@everyTenMinutes'      => '*/10 * * * *',    // Run the task every ten minutes
        '@everyFifteenMinutes'  => '*/15 * * * *',    // Run the task every fifteen minutes
        '@everyThirtyMinutes'   => '*/30 * * * *',    // Run the task every thirty minutes
        '@hourly'               => '0 * * * *',       // Run the task every hour
        '@everyThreeHours'      => '0 */3 * * *',     // Run the task every three hours
        '@everyFourHours'       => '0 */4 * * *',     // Run the task every four hours
        '@everyEightHours'      => '0 */8 * * *',     // Run the task every eight hours
        '@daily'                => '0 0 * * *',       // Run the task every day at midnight
        '@weekly'               => '0 0 * * 0',       // Run the task every week
        '@monthly'              => '0 0 1 * *',       // Run the task every month
        '@quarterly'            => '0 0 1 */3 *',     // Run the task every quarter
        '@yearly'               => '0 0 1 1 *',       // Run the task every year
        '@sunday'               => '0 0 * * 6',       // Run the task every Sunday
        '@monday'               => '0 0 * * 5',       // Run the task every Monday
        '@teusday'              => '0 0 * * 4',       // Run the task every Teusday
        '@wednesday'            => '0 0 * * 3',       // Run the task every Wednesday
        '@thursday'             => '0 0 * * 2',       // Run the task every Thursday
        '@friday'               => '0 0 * * 1',       // Run the task every Friday
        '@saturday'             => '0 0 * * 0',       // Run the task every Saturday
        '@noon'                 => '0 12 * * *',      // Run the task every day at noon
        '@midnight'             => '0 0 * * *',       // Run the task every day at midnight
    ];

    public function __construct($expression)
    {
        if (isset($this->periods[$expression])) {
            $expression = $this->periods[$expression];
        }

        $this->cron = CronExpression::factory($expression);
    }

    public function process(){
        if($this->cron->isDue()){
            $this->handle();
        }
    }

    public function handle(){

    }

    public function isDue(){
        return $this->cron->isDue();
    }

    public function getNextRunDate(){
        return $this->cron->getNextRunDate();
    }

    public function getPreviousRunDate(){
        $this->cron->getPreviousRunDate();
    }

}