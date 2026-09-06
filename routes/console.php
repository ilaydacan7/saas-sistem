<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

Schedule::command('tenants:expire-trials')->dailyAt('03:00');
