<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:mark-absent')->dailyAt('23:30');
Schedule::command('documents:alerts')->dailyAt('07:00');
Schedule::command('employees:regularization-alerts')->dailyAt('07:00');
Schedule::command('hris:backup')->dailyAt('02:00')->withoutOverlapping();
