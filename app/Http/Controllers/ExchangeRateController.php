<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        //$this->authorizeForUser($request->user('api'), 'view', ExchangeRate::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();

        $exchange_rates = ExchangeRate::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('exchange_date', 'LIKE', "%{$request->search}%");
                        // ->orWhere('status', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $exchange_rates->count();
        $exchange_rates = $exchange_rates->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        return response()->json([
            'exchange_rates' => $exchange_rates,
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
        //
        request()->validate([
            'exchange_date' => 'required',
            'exchange_rate' => 'required',
        ]);

        ExchangeRate::create([
            'exchange_date' => $request['exchange_date'],
            'exchange_rate' => $request['exchange_rate'],
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
   
    public function update(Request $request, $id)
    {

        // $this->authorizeForUser($request->user('api'), 'update', ExchangeRate::class);

        request()->validate([
            'exchange_date' => 'required',
            'exchange_rate' => 'required',
        ]);

        ExchangeRate::whereId($id)->update([
            'exchange_date' => $request['exchange_date'],
            'exchange_rate' => $request['exchange_rate'],
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

        ExchangeRate::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }


    public function delete_by_selection(Request $request)
    {
        // $this->authorizeForUser($request->user('api'), 'delete', Category::class);
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $exchange_rate_id) {
            ExchangeRate::whereId($exchange_rate_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
