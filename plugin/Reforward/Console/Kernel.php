<?php

namespace Plugin\Reforward\Console;


use Discuz\Base\DzqKernel;
use Illuminate\Console\Scheduling\Schedule;

class Kernel extends DzqKernel
{
    public function schedule(Schedule $schedule)
    {
        // $schedule->command('register:notice')->everyMinute()->appendOutputTo('/data/logs/schedule.log');;
    }
}
