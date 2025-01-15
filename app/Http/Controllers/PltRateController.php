<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PLTRate;
use App\utils\helpers;
use Carbon\Carbon;

class PltRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();

        $plt_rates = PLTRate::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('plt_rate', 'LIKE', "%{$request->search}%");
                        // ->orWhere('status', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $plt_rates->count();
        $plt_rates = $plt_rates->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'plt_rates' => $plt_rates,
            'totalRows' => $totalRows,
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        request()->validate([
            'date' => 'required|date',
            'plt_rate' => 'required|numeric',
            'description' => 'nullable|string|max:255',
        ]);

        PLTRate::create([
            'date' => $request['date'],
            'plt_rate' => $request['plt_rate'],
            'description' => $request['description'],
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        request()->validate([
            'date' => 'required|date',
            'plt_rate' => 'required|numeric',
            'description' => 'nullable|string|max:255',
        ]);

        PLTRate::whereId($id)->update([
            'date' => $request['date'],
            'plt_rate' => $request['plt_rate'],
            'description' => $request['description'],
        ]);
        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        PLTRate::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function delete_by_selection(Request $request)
    {
        // $this->authorizeForUser($request->user('api'), 'delete', Category::class);
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $plt_rate_id) {
            PLTRate::whereId($plt_rate_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
