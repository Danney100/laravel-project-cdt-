<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Vessel;
use Illuminate\Http\Request;
use Laracsv\Export;

class ReportsController extends Controller
{
    public function getNASAPotential()
    {
        $companies = Company::select('id', 'plan_number', 'name', 'email', 'fax', 'phone', 'website')->whereHas('networks', function ($q) {
            $q->where('networks.id', 4);
        })->withCount(['vessels' => function ($q1) {
            $q1->whereHas('networks', function ($q2) {
                $q2->where('networks.id', 4);
            });
        }])->orderBy('vessels_count', 'desc')->get();
        $csvExporter = new Export();
        $csvExporter->build($companies, ['vessels_count', 'name', 'id', 'plan_number', 'email', 'fax', 'phone', 'website'])->download();
    }

    public function getDJSVessels()
    {
        $djs_vessels = Vessel::where('active', 1)->with('vendors.hm', 'company.dpaContacts')->get();
        //whereHas('company', function ($q) {
        //            $q->where('name', 'like', '%donjon%');
        //        })
        $report = [];
        foreach ($djs_vessels as $vessel) {
            $make['imo'] = $vessel->imo;
            $make['mmsi'] = $vessel->mmsi;
            $make['name'] = $vessel->name;
            foreach ($vessel->vendors as $vendor) {
                if ($vendor->hm) {
                    $make['hm'] = $vendor->name;
                }
            }
            if ($vessel->company->dpaContacts->count()) {
                $make['dpa'] = $vessel->company->dpaContacts[0]->prefix . ' ' . $vessel->company->dpaContacts[0]->first_name . ' ' . $vessel->company->dpaContacts[0]->last_name;
                $make['dpa_email'] = $vessel->company->dpaContacts[0]->email;
                $make['dpa_work_phone'] = $vessel->company->dpaContacts[0]->work_phone;
                $make['dpa_mobile_phone'] = $vessel->company->dpaContacts[0]->mobile_phone;
                $make['dpa_aoh_phone'] = $vessel->company->dpaContacts[0]->aoh_phone;
                $make['dpa_fax'] = $vessel->company->dpaContacts[0]->fax;
            }
            $report[] = $make;
        }
        $csvExporter = new Export();
        $csvExporter->build(collect($report), ['imo' => 'IMO', 'mmsi' => 'MMSI', 'name' => 'Name', 'hm' => 'Hull and Machinery', 'dpa' => 'DPA', 'dpa_email' => 'DPA Email', 'dpa_work_phone' => 'DPA Work Phone', 'dpa_mobile_phone' => 'DPA Mobile Phone', 'dpa_aoh_phone' => 'DPA AOH Phone', 'dpa_fax' => 'DPA Fax'])->download();
    }
}
