<?php

namespace App\Http\Resources;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Category $category */
        $category = $this->resource;

        return [
            'id'          => $category->id,
            'slug'        => $category->slug,
            'name'        => $category->name,
            'video_count' => $category->videos_count ?? null,
        ];
    }
}
