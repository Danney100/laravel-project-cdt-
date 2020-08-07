<?php

namespace App\Http\Resources;

use App\Models\Capability;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyAddressMapResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $primary_service = null;
        if(isset($this->company['smff_service_id']) && $this->company['smff_service_id'] > 0 ){
            $capability = Capability::where(['id' => $this->company['smff_service_id'], 'status' => 1])->first();
            $primary_service = $capability['primary_service'];
        }
        return [
            'id' => $this->id,
            'text' => $this->company['name'],
            'company_id' => $this->company_id,
            'latlng' => [$this->latitude, $this->longitude],
            'primary_service' => $primary_service,
        ];
    }
}
