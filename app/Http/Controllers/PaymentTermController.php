<?php

namespace App\Http\Controllers;
use App\Models\PaymentTerm;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentTermController extends Controller
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
        $helpers = new helpers();
        $data = array();
        $payment_terms = PaymentTerm::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('due_day', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $payment_terms->count();
        $payment_terms = $payment_terms->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        
        foreach ($payment_terms as $payment_terms) {
            $item['id'] = $payment_terms->id;
            $item['name'] = $payment_terms->name;
            $item['due_day'] = $payment_terms->due_day;
            $item['reason'] = $payment_terms->reason;

            $data[] = $item;

        }

        return response()->json([
            'payment_terms' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    public function store(Request $request)
    {
         request()->validate([
            'name' => 'required',
            'due_day' => 'required|integer',
            'reason' => 'nullable',
        ]);

        PaymentTerm::create([
            'name' => $request['name'],
            'due_day' => $request['due_day'],
            'reason' =>  $request['reason'],
        ]);
        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        request()->validate([
            'name' => 'required',
            'due_day' => 'required|integer',
            'reason' => 'nullable',
        ]);

        PaymentTerm::whereId($id)->update([
            'name' => $request['name'],
            'due_day' => $request['due_day'],
            'reason' =>  $request['reason'],
        ]);
        return response()->json(['success' => true]);
    }

      public function destroy(Request $request, $id)
    {
       

        PaymentTerm::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

    public function delete_by_selection(Request $request)
    {
      
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $payment_term_id) {
            PaymentTerm::whereId($payment_term_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
