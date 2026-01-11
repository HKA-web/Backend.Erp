<?php

namespace App\Http\Resources\Option;

use App\Http\Resources\Company\CompanyResource;
use App\Http\Resources\ExpandableResource;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionResource extends JsonResource
{
    use ExpandableResource;

    public function toArray($request)
    {
        return [
            'option_id'   => $this->option_id,
            'option_name' => $this->option_name,
            'company' => $this->expandable(
                'company',
                CompanyResource::class,
                'company_id'
            ),
        ];
    }
}
