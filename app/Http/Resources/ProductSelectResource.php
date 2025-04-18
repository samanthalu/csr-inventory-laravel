<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSelectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'prod_id'           => $this->prod_id,
            'prod_name'         => $this->prod_name,
            'prod_tag'          => $this->prod_tag_number,
            'prod_serial_num'   => $this->prod_serial_num,
        ];
    }
}
