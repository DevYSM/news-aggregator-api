<?php

use App\Jobs\FetchNewsArticlesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(FetchNewsArticlesJob::class)
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('fetch-news-articles');
