<?php

namespace App\Services\News\Sources;

use App\Contracts\NewsSourceInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

abstract class AbstractNewsSource implements NewsSourceInterface
{
    protected function client(): PendingRequest
    {
        return Http::timeout(15)
            ->connectTimeout(5)
            ->retry(3, 500);
    }
}
