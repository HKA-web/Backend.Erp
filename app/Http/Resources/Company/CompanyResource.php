<?php

namespace App\Http\Resources\Company;

use App\Http\Resources\ExpandableResource;
use App\Http\Resources\Province\ProvinceResource;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    use ExpandableResource;

    public function toArray($request)
    {
        return [
            'company_id'   => $this->company_id,
            'company_name' => $this->company_name,
            'province' => $this->expandable(
                'province',
                ProvinceResource::class,
                'province_id'
            ),
        ];
    }
}
