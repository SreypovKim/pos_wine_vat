<?php

namespace App\Http\Controllers;
use App\Models\Service;

use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
       
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();

        $services = Service::where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('price', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $services->count();
        $services = $services->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        foreach ($services as $service) {
            $service_data['id'] = $service->id;
            $service_data['name'] = $service->name;
            $service_data['price'] = $service->price;
            $service_data['status'] = $service->status;
            // $service_data['start_age'] = $service->start_age;
            // $service_data['end_age'] = $service->end_age;

            $data[] = $service_data;
        }


        return response()->json([
            'services' => $data,
            'totalRows' => $totalRows,
        ]);

    }

     public function store(Request $request)
    {
        request()->validate([
            'name' => 'required',
            'price' => 'required',
            // 'start_age' => 'nullable',
            // 'end_age' => 'nullable',
            
        ]);
        Service::create([
            'name' => $request['name'],
            'price' => $request['price'],
            'status' => $request['base_status'],
            // 'start_age' => $request['start_age'],
            // 'end_age' => $request['end_age'],
        ]);

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $service = Service::find($id);

        return $service;
    }

    public function update(Request $request, $id)
    {
        request()->validate([
            'name' => 'required',
            'price' => 'required',
            'base_status' => 'required',
            // 'start_age' => 'nullable',
            // 'end_age' => 'nullable',  
        ]);
        Service::whereId($id)->update([
            'name' => $request['name'],
            'price' => $request['price'],
            'status' => $request['base_status'],
            // 'start_age' => $request['start_age'],
            // 'end_age' => $request['end_age'],
        ]);

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, $id)
    {
        
        $service = Service::find($id);
        if($service) {
           $service->delete();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false]);
        }

    }
}
