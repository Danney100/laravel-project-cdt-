<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class WeatherController extends Controller
{
    public function test()
    {
        $path = base_path() . '/storage/grib2json/';
        $result = exec(
            'sudo -u www-data ' .
            $path . 'grib2json --data --names --output ' .
            $path . 'a.json ' .
            $path . 'a.f000'
        );

        echo 'sudo -u www-data ' .
            $path . 'grib2json --data --names --output ' .
            $path . 'a.json ' .
            $path . 'a.f000' . PHP_EOL;

        $process = new Process([
            'sudo', '-u', 'www-data',
            $path . 'grib2json', '--data', '--names', '--output',
            $path . 'a.json',
            $path . 'a.f000',
        ]);
        $process->run();

        if (!$process->isSuccessful()) {
            echo 'failed';
        }
        echo 'output ' . $process->getoutput() . PHP_EOL;

        return response()->json([
            'message' => 'converted',
            'base_path' => $path,
        ], 200);
    }
}
