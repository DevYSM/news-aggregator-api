<?php

namespace App\Jobs;

use App\Services\News\AggregatorService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class FetchNewsArticlesJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 80;

    /** @var int[] */
    public array $backoff = [10, 30, 60];

    public function handle(AggregatorService $aggregator): void
    {
        $aggregator->handle();
    }

    public function failed(Throwable $exception): void
    {
        report($exception);
    }
}
