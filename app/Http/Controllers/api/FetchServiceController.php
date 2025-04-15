<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class FetchServiceController extends Controller
{
    public function index(){
        $services = Cache::remember('services:index', now()->addMinutes(10), function(){
            return Service::all();
        });

        return response()->json($services);
    }
    public function serviceProvider(){
        $services = Cache::remember('serviceprovider:index', now()->addMinutes(10), function(){
            return ServiceProvider::all();
        });

        return response()->json($services);
    }
}
