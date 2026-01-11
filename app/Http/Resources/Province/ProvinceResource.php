<?php

namespace App\Http\Resources\Province;

use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\ExpandableResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProvinceResource extends JsonResource
{
    use ExpandableResource;

    public function toArray($request)
    {
        return [
            'province_id'   => $this->province_id,
            'province_name' => $this->province_name,
            'companys' => $this->expandable(
                'companys',
                CompanyResource::class,
                null
            ),
        ];
    }
}
