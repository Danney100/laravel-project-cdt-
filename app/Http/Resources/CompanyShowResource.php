<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyShowResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $company =  Company::whereId($this->id)->first();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'plan_number' => $this->plan_number,
            'email' => $this->email,
            'fax' => $this->fax,
            'phone' => $this->phone,
            'website' => $this->website,
            'description' => $this->description,
            'active' => $this->active,
            'qi_id' => $this->qi_id,
            'operating_company_id' => $this->operating_company_id,
            'vrp_import' => $this->vrp_import,
            'djs_active' => $this->djs_active,
            'networks_active' => $this->networks_active,
            'vendor_active' => $this->vendor_active,
            'capabilies_active' => $this->capabilies_active,
            'vendor_type' => $company->type ? $company->type->name : null,
            'vendor_type_id' => $company->type ? $company->type->id : null,
            'vrp_primary_smff' => $this->vrp_primary_smff,
            'vendor_category' => $this->vendor_category,
            'company_poc_id' => $this->company_poc_id,
            'has_photo' => (bool) $this->has_photo,
            'zone_name' => $this->primaryAddress->first() ? $this->primaryAddress->first()->zone->name : null
        ];
    }
}
