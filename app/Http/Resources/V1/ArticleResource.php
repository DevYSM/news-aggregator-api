<?php

namespace App\Http\Resources\V1;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Article */
class ArticleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'author' => $this->author,
            'category' => $this->category,
            'url' => $this->url,
            'image_url' => $this->image_url,
            'published_at' => $this->published_at,
        ];
    }
}
