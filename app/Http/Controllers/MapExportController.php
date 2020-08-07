<?php

namespace App\Http\Controllers;

use App\Http\Resources\VesselMapInfoResource;
use App\Models\Company;
use App\Models\CompanyAddress;
use App\Models\Capability;
use App\Models\NavStatus;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Vessel;
use App\Models\VesselAISPositions;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class MapExportController extends Controller
{
    const iconDirectory = 'https://storage.googleapis.com/donjon-smit/map-icons/';

    public function KML($filters)
    {
        // Creates an array of strings to hold the lines of the KML file.
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
        $kml[] = ' <Document>';

        $kml = $this->kmlVessel($kml, $filters);

        $kml[] = ' </Document>';
        $kml[] = '</kml>';
        $kmlOutput = implode("\n", $kml);
        return response($kmlOutput)->header('Content-Type', 'application/vnd.google-earth.kml+xml');



        /*
        $map_filters = json_decode($filters);
        $fleets = [];
        $networks = [];
        $smff_selected = [];
        $smff_operator = 'and';
        if (is_object($map_filters)) {
            $fleets = is_array($map_filters->fleets) ? $map_filters->fleets : [];
            $networks = is_array($map_filters->networks) ? $map_filters->networks : [];
            $smff_selected = is_array($map_filters->smff_selected) ? $map_filters->smff_selected : [];
            $smff_operator = $map_filters->smff_operator ? $map_filters->smff_operator : 'and';
        }

        $vessels_model = Vessel::select('id', 'imo', 'name', 'latitude', 'longitude', 'speed', 'heading', 'course', 'destination', 'ais_timestamp', 'eta', 'ais_status_id', 'vessel_type_id')->with('navStatus:status_id,value', 'type:id,name,ais_category_id', 'zone:id,name')
            ->whereNotNull('latitude')->whereNotNull('longitude')->whereNotNull('ais_status_id')
            ->whereHas('provider', function ($q) {
                $q->where('ais_providers.active', 1);
            });
        $vessels = $vessels_model->get();
        $companies_model = CompanyAddress::select('id', 'company_id', 'latitude', 'longitude')->whereNotNull('latitude')->whereNotNull('longitude')->where([['latitude', '<>', 0], ['longitude', '<>', 0], ['latitude', '<>', ''], ['longitude', '<>', '']]);
        $user_model = UserAddress::select('id', 'user_id', 'latitude', 'longitude')->whereNotNull('latitude')->whereNotNull('longitude')->where([['latitude', '<>', 0], ['longitude', '<>', 0], ['latitude', '<>', ''], ['longitude', '<>', '']]);

        $companies = $companies_model->with('company:id,name,smff_service_id')->get();
        $users = $user_model->with('user:id,first_name,last_name,smff_service_id')->get();
        $company_ids = $companies_model->pluck('company_id');
        $user_ids = $user_model->pluck('user_id');
        $smff_ids = Company::whereIn('id', $company_ids)->groupBy('smff_service_id')->pluck('smff_service_id');
        $smff_ids_user = User::whereIn('id', $user_ids)->groupBy('smff_service_id')->pluck('smff_service_id');
        $smff_ids = array_unique(array_merge($smff_ids->toArray(), $smff_ids_user->toArray()), SORT_REGULAR);
        $company_icons = Capability::whereIn('id', $smff_ids)->groupBy('primary_service')->pluck('primary_service');

        $distinct_vessels = [];
        foreach ($vessels as $vessel) {
            $distinct_vessels[$vessel->type->ais_category_id . $vessel->ais_status_id] = [
                'ais_category_id' => $vessel->type->ais_category_id,
                'ais_status_id' => $vessel->ais_status_id
            ];
        }

        foreach ($distinct_vessels as $distinct_vessel) {
            $unique_name = $distinct_vessel['ais_category_id'] . $distinct_vessel['ais_status_id'];
            $kml[] = '<Style id="' . $unique_name . 'Style">';
            $kml[] = '<IconStyle id="' . $unique_name . 'Icon">';
            $kml[] = '<scale>0.7</scale>';
            $kml[] = '<Icon>';
            $kml[] = '<href>' . $this->getVesselIcon($distinct_vessel['ais_category_id'], $distinct_vessel['ais_status_id']) . '</href>';
            $kml[] = '</Icon>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
        }
        $company_icons[] = 'undefined';
        foreach ($company_icons as $company_icon) {
            $kml[] = '<Style id="' . $company_icon . 'Style">';
            $kml[] = '<IconStyle id="' . $company_icon . 'Icon">';
            $kml[] = '<scale>0.7</scale>';
            $kml[] = '<Icon>';
            $kml[] = '<href>' . $this->getPrimaryServiceIcon($company_icon) . '</href>';
            $kml[] = '</Icon>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
        }

        // Iterates through the MySQL results, creating one Placemark for each row.
        foreach ($vessels->toArray() as $vessel) {
            $kml[] = '<Placemark id="placemark' . $vessel['id'] . '">';
            $kml[] = '<name>' . htmlentities($vessel['name']) . '</name>';
            $kml[] = '<description>' . htmlentities('<b>' . $vessel['name'] . ' [' . ($vessel['imo'] ?? '--') . ']</b> / ' . $vessel['speed'] . ' knots / ' . $vessel['course'] . '&deg;<br>AIS Status: <strong>' . ($vessel['ais_status'] ? $vessel['ais_status']['value'] : '') . '</strong><br>Position received: ' . Carbon::parse($vessel['ais_timestamp'])->diffForHumans() . '<br>Destination: <b>' . ($vessel['destination'] ?? 'Unknown') . '<br>ETA: <b>' . ($vessel['eta'] ?? 'Unknown') . '</b>') . '</description>';
            $kml[] = '<Style>';
            $kml[] = '<IconStyle>';
            $kml[] = '<heading>' . $vessel['heading'] . '</heading>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
            $kml[] = '<styleUrl>#' . $vessel['type']['ais_category_id'] . $vessel['ais_status_id'] . 'Style</styleUrl>';
            $kml[] = '<Point>';
            $kml[] = '<coordinates>' . $vessel['longitude'] . ',' . $vessel['latitude'] . '</coordinates>';
            $kml[] = '</Point>';
            $kml[] = '</Placemark>';
        }

        foreach ($companies->toArray() as $company) {
            $primary_service = $company['company']['smff_service_id'] ? Capability::where('id', $company['company']['smff_service_id'])->first()->primary_service : 'undefined';
            $kml[] = '<Placemark id="placemarkCompany' . $company['id'] . '">';
            $kml[] = '<name><![CDATA[' . $company['company']['name'] . ']]></name>';
            $kml[] = '<description><![CDATA[' . $company['company']['name'] . ']]></description>';
            $kml[] = '<styleUrl>#' . $primary_service . 'Style</styleUrl>';
            $kml[] = '<Point>';
            $kml[] = '<coordinates>' . $company['longitude'] . ',' . $company['latitude'] . '</coordinates>';
            $kml[] = '</Point>';
            $kml[] = '</Placemark>';
        }

        foreach ($users->toArray() as $user) {
            $primary_service = $user['user']['smff_service_id'] ? Capability::where('id', $user['user']['smff_service_id'])->first()->primary_service : 'undefined';
            $kml[] = '<Placemark id="placemarkUser' . $user['id'] . '">';
            $kml[] = '<name><![CDATA[' . $user['user']['first_name'] . ' ' . $user['user']['last_name'] . ']]></name>';
            $kml[] = '<description><![CDATA[' . $user['user']['first_name'] . ' ' . $user['user']['last_name'] . ']]></description>';
            $kml[] = '<styleUrl>#' . $primary_service . 'Style</styleUrl>';
            $kml[] = '<Point>';
            $kml[] = '<coordinates>' . $user['longitude'] . ',' . $user['latitude'] . '</coordinates>';
            $kml[] = '</Point>';
            $kml[] = '</Placemark>';
        }

        // End XML file
        $kml[] = '</Document>';
        $kml[] = '</kml>';
        $kmlOutput = implode("\n", $kml);
        return response($kmlOutput)->header('Content-Type', 'application/vnd.google-earth.kml+xml');
        */
    }

    private function kmlVessel($kml, $filters)
    {
        $vessels = MapController::getFilteredVessels($filters);

        $distinct_vessels = [];
        foreach ($vessels as $vessel) {
            $distinct_vessels[$vessel->ais_nav_status_id . $vessel->type->ais_category_id] = [
                'ais_nav_status_id' => $vessel->ais_nav_status_id,
                'vessel_type' => $vessel->type->ais_category_id,
            ];
        }

        foreach ($distinct_vessels as $distinct_vessel) {
            $id = $distinct_vessel['ais_nav_status_id'] . '-' . $distinct_vessel['vessel_type'];
            $kml[] = '<Style id="' . $id . '">';
            $kml[] = '<IconStyle>';
            $kml[] = '<scale>0.5</scale>';
            $kml[] = '<Icon>';
            $kml[] = '<href>' . $this->getVesselIcon($distinct_vessel['vessel_type'], $distinct_vessel['ais_nav_status_id']) . '</href>';
            $kml[] = '</Icon>';
            $kml[] = '</IconStyle>';
            $kml[] = '</Style>';
        }

        foreach ($vessels as $vessel) {
            $tracks = VesselAISPositions::where('vessel_id', $vessel['id'])
                ->orderBy('timestamp', 'desc')
                ->get();
            $latest = count($tracks) > 0 ? $tracks[0] : [];

            $kml[] = '<Placemark>';
            $kml[] = '<name>' . htmlentities($vessel['name']) . '</name>';
            $kml[] = '<description>' .
                htmlentities(
                    $vessel['name'] . ' ' .
                    '[' . ($vessel['imo'] ?? '--') . '] / ' .
                    ($latest['speed'] ?? '--') . 'knots / ' .
                    ($latest['course'] ?? '--') . '&deg;<br>' .
                    'AIS Status: ' . $vessel['nav_status']['value'] . '<br>' .
                    'Position received: ' . ($latest['timestamp'] ?? '--') . '<br>' .
                    'Destination: ' . ($latest['destination'] ?? '--') . '<br>' .
                    'ETA: ' . ($latest['eta'] ?? '--')
                ) .
                '</description>';
            $kml[] = '<styleUrl>#' . $vessel['ais_nav_status_id'] . '-' . $vessel['type']['ais_category_id'] . '</styleUrl>';
            $kml[] = '<Point>';
            $kml[] = '<coordinates>' . $vessel['ais_long'] . ',' . $vessel['ais_lat'] . '</coordinates>';
            $kml[] = '</Point>';
            $kml[] = '</Placemark>';
        }

        return $kml;
    }

    private function getVesselIcon($vesselType, $aisStatusId)
    {
        $validTypes = ['0', '1', '2', '3', '3a', '3b', '4', '5', '5a', '6', '7', '8', '9'];
        if (!$vesselType || !in_array($vesselType, $validTypes)) {
            $vesselType = 'Unspecified';
        }
        if (!$this->validateAISStatusID($aisStatusId)) {
            $aisStatusId = 'No_AIS';
        }
        return self::iconDirectory . 'vessels/' . $vesselType . '/' . $aisStatusId . '.png'; 
    }

    private function validateAISStatusID($aisStatusId)
    {
        $validStatusIDS = [0, 1, 2, 3, 4, 5, 6, 7, 8, 11, 12];
        return in_array($aisStatusId, $validStatusIDS);
    }

    private function getPrimaryServiceIcon($primaryService)
    {
        if (!$primaryService) {
            $primaryService = 'undefined';
        }
        return url('/storage/mapfiles/smff_icons/' . $primaryService . '.png');
    }

    public function KMLEarth()
    {
        // Creates an array of strings to hold the lines of the KML file.
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = '<kml xmlns="http://earth.google.com/kml/2.1">';
        $kml[] = '<Folder>';
        $kml[] = '<NetworkLink>';
        $kml[] = '<Link>';
        $kml[] = '<href>' . url('/api/map/export/CDT.kml') . '</href>';
        $kml[] = '<refreshMode>onInterval</refreshMode>';
        $kml[] = '<refreshInterval>3600</refreshInterval>';
        $kml[] = '</Link>';
        $kml[] = '</NetworkLink>';
        $kml[] = '</Folder>';
        $kml[] = '</kml>';
        $kmlOutput = implode("\n", $kml);
        return response($kmlOutput)->header('Content-Type', 'application/vnd.google-earth.kml+xml');
    }
}
