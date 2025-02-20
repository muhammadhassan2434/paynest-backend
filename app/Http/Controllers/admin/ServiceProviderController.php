<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceProviders = ServiceProvider::with('service')->get(); 
        return view('admin.serviceProvider.index',compact('serviceProviders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $services = Service::all();
        return view('admin.serviceProvider.create',compact('services'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'logo' => 'required',
            'status' => 'required',
            'service_id' => 'required'
        ],[
            'service_id' => 'Please select a service'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = time() . $file->getClientOriginalName();
            $filePath =  'uploads/serviceProviders/' . $filename;
            $file->move('uploads/serviceProviders/', $filename);



            $service = new ServiceProvider();
            $service->service_id = $request->service_id;
            $service->name = $request->name;
            $service->logo = $filePath;
            $service->status = $request->status;
            $service->save();
            return redirect()->route('provider.index')->with('success', 'Service created successfully');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $provider = ServiceProvider::find($id);
        $services = Service::all();
        return view('admin.serviceProvider.edit',compact('provider','services'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', 
            'status' => 'required',
            'service_id' => 'required'
        ], [
            'service_id.required' => 'Please select a service'
        ]);
    
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
    
        $service = ServiceProvider::findOrFail($id);
        
        if ($request->hasFile('logo')) {
            if ($service->logo && file_exists(public_path($service->logo))) {
                unlink(public_path($service->logo));
            }
    
            $file = $request->file('logo');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filePath = 'uploads/serviceProviders/' . $filename;
            $file->move(public_path('uploads/serviceProviders/'), $filename);
    
            $service->logo = $filePath; 
        }
    
        $service->service_id = $request->service_id;
        $service->name = $request->name;
        $service->status = $request->status;
        $service->save();
    
        return redirect()->route('provider.index')->with('success', 'Service provider updated successfully');
    }
    

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $provider = ServiceProvider::findOrFail($id);
        if($provider->logo && file_exists(public_path($provider->logo))){
            unlink(public_path($provider->logo));
        }

        $provider->delete();

    return redirect()->route('provider.index')->with('success', 'Service provider deleted successfully');
    }
}
