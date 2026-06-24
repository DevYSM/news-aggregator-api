<?php

use App\Events\ArticlesFetched;
use App\Jobs\FetchNewsArticlesJob;
use App\Services\News\AggregatorService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

it('dispatches job to queue when --queue flag given', function () {
    Queue::fake();

    $this->artisan('news:fetch --queue')
        ->assertSuccessful();

    Queue::assertPushed(FetchNewsArticlesJob::class);
});

it('runs aggregator synchronously without --queue flag', function () {
    $mock = Mockery::mock(AggregatorService::class);
    $mock->shouldReceive('handle')->once();

    app()->bind(AggregatorService::class, fn () => $mock);

    $this->artisan('news:fetch')
        ->assertSuccessful();
});

it('dispatches ArticlesFetched event when source returns articles', function () {
    Event::fake();

    $mock = Mockery::mock(AggregatorService::class);
    $mock->shouldReceive('handle')->once()->andReturnUsing(function () {
        ArticlesFetched::dispatch([]);
    });

    app()->bind(AggregatorService::class, fn () => $mock);

    $this->artisan('news:fetch')->assertSuccessful();

    Event::assertDispatched(ArticlesFetched::class);
});
