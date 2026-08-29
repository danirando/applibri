<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'cover_url' => $this->cover_url,
            'isbn_13' => $this->isbn_13,
            'isbn_10' => $this->isbn_10,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'page_count' => $this->page_count,
            'language' => $this->language,
            'external_id' => $this->external_id,
            'source' => $this->source,
            'authors' => AuthorResource::collection($this->whenLoaded('authors')),
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
