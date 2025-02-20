<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = Service::all();
        return view('admin.services.index', compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'logo' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = time() . $file->getClientOriginalName();
            $filePath =  'uploads/services/' . $filename;
            $file->move('uploads/services/', $filename);



            $service = new Service();
            $service->name = $request->name;
            $service->logo = $filePath;
            $service->status = $request->status;
            $service->save();
            return redirect()->route('services.index')->with('success', 'Service created successfully');
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
    }
    
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $service = Service::find($id);
        return view('admin.services.edit',compact('service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    $service = Service::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'status' => 'required',
        'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Handle logo update
    if ($request->hasFile('logo')) {
        // Remove old image if exists
        if ($service->logo && file_exists(public_path($service->logo))) {
            unlink(public_path($service->logo));
        }

        // Upload new image
        $file = $request->file('logo');
        $filename = time() . '_' . $file->getClientOriginalName();
        $filePath = 'uploads/services/' . $filename;
        $file->move(public_path('uploads/services/'), $filename);

        // Update service logo
        $service->logo = $filePath;
    }

    // Update other fields
    $service->name = $request->name;
    $service->status = $request->status;
    $service->save();

    return redirect()->route('services.index')->with('success', 'Service updated successfully');
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
{
    $service = Service::findOrFail($id);

    if ($service->logo && file_exists(public_path($service->logo))) {
        unlink(public_path($service->logo));
    }

    $service->delete();

    return redirect()->route('services.index')->with('success', 'Service deleted successfully');
}

}
