<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdvertisementController extends Controller
{
    public function index()
    {
        $advertisements = Advertisement::all();
        return view('admin.advertisement.index', compact('advertisements'));
    }

    public function create()
    {
        return view('admin.advertisement.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'advertiser_name' => 'required',
            'logo' => 'required',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            $filename = time() . $file->getClientOriginalName();
            $filePath =  'uploads/advertisement/' . $filename;
            $file->move('uploads/advertisement/', $filename);
        }

        $advertisement = new Advertisement();
        $advertisement->advertiser_name = $request->advertiser_name;
        $advertisement->logo = $filePath;
        $advertisement->status = $request->status;
        $advertisement->save();

        return redirect()->route('advertisements.index');
    }

    public function edit($id)
    {
        $advertisement = Advertisement::find($id);
        return view('admin.advertisement.edit', compact('advertisement'));
    }
    public function update(Request $request, $id)
    {
        $advertisement = Advertisement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'advertiser_name' => 'required',
            'logo' => 'nullable|image', // not required on update
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors())->withInput();
        }

        $advertisement->advertiser_name = $request->advertiser_name;
        $advertisement->status = $request->status;

        if ($request->hasFile('logo')) {
            // Delete old file if exists
            if ($advertisement->logo && file_exists(public_path($advertisement->logo))) {
                unlink(public_path($advertisement->logo));
            }

            $file = $request->file('logo');
            $filename = time() . $file->getClientOriginalName();
            $filePath = 'uploads/advertisement/' . $filename;
            $file->move('uploads/advertisement/', $filename);

            $advertisement->logo = $filePath;
        }

        $advertisement->save();

        return redirect()->route('advertisements.index')->with('success', 'Advertisement updated successfully!');
    }
    public function destroy(string $id)
    {
        $advertisement = Advertisement::findOrFail($id);
        if ($advertisement->logo && file_exists(public_path($advertisement->logo))) {
            unlink(public_path($advertisement->logo));
        }
        $advertisement->delete();

        return redirect()->route('advertisements.index')->with('success', 'Advertisement provider  successfully');
    }

    // fetch data for frontend api
    public function getAdvertisement(){
        $advertisements = Advertisement::where('status','active')->get();
        $logos = $advertisements->pluck('logo');
        return response()->json([
            'status' => true,
            'logos' => $logos,
            'message' => 'logos fetch successfully'
        ]);
    }
}
