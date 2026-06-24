<?php

namespace App\Console\Commands;

use App\Jobs\FetchNewsArticlesJob;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('news:fetch {--queue : Dispatch to queue instead of running synchronously}')]
#[Description('Fetch and store articles from all configured news sources')]
class FetchNewsCommand extends Command
{
    /**
     * @return int
     */
    public function handle(): int
    {
        if ($this->option('queue')) {
            FetchNewsArticlesJob::dispatch();
            $this->info('FetchNewsArticlesJob dispatched to queue.');
        } else {
            FetchNewsArticlesJob::dispatchSync();
            $this->info('News fetch completed synchronously.');
        }

        return self::SUCCESS;
    }
}
