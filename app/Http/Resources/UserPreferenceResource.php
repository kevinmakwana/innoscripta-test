<?php
declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    /**
     * @return array<string,mixed>
     */
    public function toArray($request): array
    {
        if (! $this->resource) {
            return [];
        }

        return [
            'id' => $this->id,
            'sources' => $this->sources,
            'categories' => $this->categories,
            'authors' => $this->authors,
        ];
    }
}
