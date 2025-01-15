<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\utils\helpers;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class ActionController extends Controller
{
    public function index(Request $request)
    {
        
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ((int)$pageStart * (int)$perPage) - (int)$perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();

        $actions = Action::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('reason', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $actions->count();
        $actions = $actions->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

         $data = Action::where('deleted_at', '=', null)->get();        
         return response()->json([
            'actions' => $actions,
            'totalRows' => $totalRows,
            'getActions' => $data,
        ]);
    }

     public function store(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'create', Brand::class);

        request()->validate([
            'name' => 'required',
            'reason' => 'nullable',
        ]);

        \DB::transaction(function () use ($request) {

            $action = new Action;
            $action->name = $request['name'];
            $action->reason = $request['reason'];
            $action->save();

        }, 10);

        return response()->json(['success' => true]);

    }

    public function update(Request $request, $id)
     {
 
         request()->validate([
            'name' => 'required',
            'reason' => 'nullable',
         ]);
         \DB::transaction(function () use ($request, $id) {

             Action::whereId($id)->update([
                 'name' => $request['name'],
                 'reason' => $request['reason'],
             ]);
 
         }, 10);
 
         return response()->json(['success' => true]);
     }


     public function destroy(Request $request, $id)
    {
       

        Action::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

     public function delete_by_selection(Request $request)
    {

      

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $brand_id) {
            Action::whereId($brand_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }
        return response()->json(['success' => true]);

    }

   



    public function GetAction(){
        $actions = Action::all();
        return $actions;
    }



}
