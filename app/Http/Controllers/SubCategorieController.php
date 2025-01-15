<?php

namespace App\Http\Controllers;
use App\Models\SubCategorie;
use App\Models\Category;
use App\utils\helpers;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubCategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
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
        $sub_categories = SubCategorie::where('deleted_at', '=', null)

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%")
                        ->orWhere('code', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('category', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });
        $totalRows = $sub_categories->count();
        $sub_categories = $sub_categories->offset($offSet)
            ->limit($perPage)
            ->orderBy($order, $dir)
            ->get();

        
        foreach ($sub_categories as $sub_categorie) {
            $item['id'] = $sub_categorie->id;
            $item['code'] = $sub_categorie->code;
            $item['name'] = $sub_categorie->name;
            $item['category'] = $sub_categorie['category']->name;
            $item['category_id'] = $sub_categorie->category_id;

            $data[] = $item;

        }
          $categories = Category::where('deleted_at', '=', null)->get();

        return response()->json([
            'sub_categories' => $data,
            'totalRows' => $totalRows,
            'categories'=>$categories,
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
            'name' => 'required',
            'code' => 'required',
            'category_id' => 'required',
        ]);

        SubCategorie::create([
            'code' => $request['code'],
            'name' => $request['name'],
            'category_id' =>  $request['category_id'],
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
        $sub_categories = SubCategorie::where('category_id',$id)->where('deleted_at', '=', null)->orderBy('id', 'asc')->get();
        return response()->json([
            'sub_categories' => $sub_categories,
        ]);
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
            'name' => 'required',
            'code' => 'required',
            'category_id' => 'required',
        ]);

        SubCategorie::whereId($id)->update([
            'code' => $request['code'],
            'name' => $request['name'],
            'category_id' =>  $request['category_id'],
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

        SubCategorie::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        return response()->json(['success' => true]);
    }

     public function delete_by_selection(Request $request)
    {
      
        $selectedIds = $request->selectedIds;

        foreach ($selectedIds as $subcategory_id) {
            SubCategorie::whereId($subcategory_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }
}
