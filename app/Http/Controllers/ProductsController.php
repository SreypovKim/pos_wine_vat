<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Unit;
use App\Models\Combine;
use App\Models\Warehouse;
use App\Models\PaymentTerm;
use App\utils\helpers;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use \Gumlet\ImageResize;

use Intervention\Image\Facades\Image as ImageResizeOne;
use Illuminate\Support\Facades\File;

class ProductsController extends BaseController
{

    //------------ Get ALL Products --------------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Product::class);
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = 0;
        $offSet = ((int)$pageStart * (int)$perPage) - (int)$perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();
        // Filter fields With Params to retrieve
        $columns = array(0 => 'name', 1 => 'category_id', 2 => 'brand_id', 3 => 'code');
        $param = array(0 => 'like', 1 => '=', 2 => '=', 3 => 'like');
        $data = array();

        $products = Product::with('unit', 'category', 'brand','product_warehouse')
            ->where('products.deleted_at', '=', null);
        //Multiple Filter
        $Filtred = $helpers->filter($products, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('products.name', 'LIKE', "%{$request->search}%")
                        ->orWhere('products.code', 'LIKE', "%{$request->search}%")
                        ->orWhere('products.kh_name', 'LIKE', "%{$request->search}%")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('category', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('brand', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('subcategory', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });
        $totalRows = $Filtred->count();

        if ($order == "quantity") {
            $products = $products->join('product_warehouse', 'products.id', '=', 'product_warehouse.product_id')
                ->select('products.*')
                ->groupBy('products.id')
                ->orderBy('product_warehouse.qte', $dir)
                ->offset($offSet)
                ->limit($perPage)
                ->get();
        } else {
            $products = $products->offset($offSet)
                ->limit($perPage)
                ->orderBy($order, $dir)
                ->get();
        }

        foreach ($products as $product) {
            $item['id'] = $product->id;
            $item['code'] = $product->code;
            $item['name'] = $product->name;
            // $item['kh_name'] = $product->kh_name;
            $item['category'] = $product['category']->name;
            $item['subcategory'] = $product['subcategory'] ? $product['subcategory']->name : 'N/D' ;
            $item['brand'] = $product['brand'] ? $product['brand']->name : 'N/D';
            $item['unit'] = $product['unit']->ShortName;
            $item['cost'] = round($product->cost,2);
            $item['price'] = $product->price;
            $val= $item['price'] - $item['cost'];
            $item['profit'] = round($val, 2);
            $item['olo'] = $product['product_warehouse'];

            $product_warehouse_data = product_warehouse::where('product_id', $product->id)
            ->where('deleted_at', '=', null)->where('qte','>',0)
            ->first();
            $total_qty = 0;
            $item['quantity'] =  $product_warehouse_data ? $product_warehouse_data->qte : 0 ;
            // foreach ($product_warehouse_data as $product_warehouse) {
            //     if($product_warehouse->qte > 0){
            //         $total_qty = $product_warehouse->qte;
            //         $item['quantity'] = $total_qty;
            //     }
                
            // }

            $firstimage = explode(',', $product->image);
            $item['image'] = $firstimage[0];

            $data[] = $item;

        }

        $warehouses = Warehouse::where('deleted_at', null)->get(['id', 'name']);
        $categories = Category::where('deleted_at', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', null)->get(['id', 'name']);
     

        return response()->json([
            'warehouses' => $warehouses,
            'categories' => $categories,
          
            'brands' => $brands,
            'products' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    //-------------- Store new  Product  ---------------\\

    public function store(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'create', Product::class);

      

        try {
            $this->validate($request, [
                // 'code' => 'required|unique:products',
                // 'code' => Rule::unique('products')->where(function ($query) {
                //     return $query->where('deleted_at', '=', null);
                // }),
                'name' => 'required',
                'Type_barcode' => 'required',
                'price' => 'required',
                'category_id' => 'required',
                'subcategory_id' => 'nullable',
                'cost' => 'required',
                'unit_id' => 'required',
            ], [
                'code.unique' => 'This code already used. Generate Now',
                'code.required' => 'This field is required',
            ]);

            \DB::transaction(function () use ($request) {

                // -- Create New Product
                $Product = new Product;

                // -- Field Required
                $Product->name = $request['name'];
                $Product->kh_name = $request['kh_name'];
                $Product->code = $request['code'];
                $Product->Type_barcode = $request['Type_barcode'];
                $Product->price = $request['price'];
                $Product->category_id = $request['category_id'];
                $Product->subcategory_id = $request['subcategory_id'];
                $Product->brand_id = $request['brand_id'];
                $Product->TaxNet = $request['TaxNet'] ? $request['TaxNet'] : 0;
                $Product->tax_method = $request['tax_method'];
                $Product->note = $request['note'];
                $Product->cost = $request['cost'];
                $Product->unit_id = $request['unit_id'];
                $Product->unit_sale_id = $request['unit_sale_id'];
                $Product->unit_purchase_id = $request['unit_purchase_id'];
                $Product->stock_alert = $request['stock_alert'] ? $request['stock_alert'] : 0;
                $Product->is_variant = $request['is_variant'] == 'true' ? 1 : 0;
                $Product->is_combine = $request['is_combine'] == 'true' ? 1 : 0;
                $Product->is_stock = $request['is_stock'];
                $Product->volume = $request['volume'];

                if ($request['images']) {
                    $files = $request['images'];
                    foreach ($files as $file) {
                        $fileData = ImageResize::createFromString(base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $file['path'])));
                        $fileData->resize(200, 200);
                        $name = rand(11111111, 99999999) . $file['name'];
                        $path = public_path() . '/images/products/';
                        $success = file_put_contents($path . $name, $fileData);
                        $images[] = $name;
                    }
                    $filename = implode(",", $images);
                } else {
                    $filename = 'no-image.png';
                }

                $Product->image = $filename;
                $Product->save();

                // Store Variants Product
                if ($request['is_variant'] == 'true') {
                    // foreach ($request['variants'] as $variant) {
                    foreach ($request['variants'] as $variant) {
                        $parts = explode(',', $variant);
                        $Product_variants_data[] = [
                            'product_id' => $Product->id,
                            'name' =>  $parts[0],
                            'qty' => $parts[1],
                            'price' => $parts[2],
                           
                        ];
                    }
                      
                    // return $Product_variants_data;
                    ProductVariant::insert($Product_variants_data);
                   
                }
                if ($request['is_combine'] == 'true') {
                    // foreach ($request['variants'] as $variant) {
                    foreach ($request['combines'] as $combine) {
                        $parts = explode(',', $combine);
                        $Product_combine_data[] = [
                            'product_id' => $Product->id,
                            'product_combine_id' => $parts[0],
                            'qty' => $parts[1],
                        ];
                    }
                      
                    // return $Product_variants_data;
                    Combine::insert($Product_combine_data);
                   
                }
                //--Store Product Warehouse
                $warehouses = Warehouse::where('deleted_at', null)->pluck('id')->toArray();
                if ($warehouses) {
                    $Product_variants = ProductVariant::where('product_id', $Product->id)
                        ->where('deleted_at', null)
                        ->get();
                    foreach ($warehouses as $warehouse) {
                        if ($request['is_variant'] == 'true') {
                            foreach ($Product_variants as $product_variant) {

                                $product_warehouse[] = [
                                    'product_id' => $Product->id,
                                    'warehouse_id' => $warehouse,
                                    'product_variant_id' => $product_variant->id,
                                ];
                            }
                        } else {
                            $product_warehouse[] = [
                                'product_id' => $Product->id,
                                'warehouse_id' => $warehouse,
                            ];
                        }
                    }
                    product_warehouse::insert($product_warehouse);
                }

            }, 10);

            return response()->json(['success' => true]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'msg' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    //-------------- Update Product  ---------------\\
    //-----------------------------------------------\\

    public function update(Request $request, $id)
    {
          
        $this->authorizeForUser($request->user('api'), 'update', Product::class);
        try {
            $this->validate($request, [
                // 'code' => 'required|unique:products',
                // 'code' => Rule::unique('products')->ignore($id)->where(function ($query) {
                //     return $query->where('deleted_at', '=', null);
               // }),
                'name' => 'required',
                'Type_barcode' => 'required',
                'price' => 'required',
                'category_id' => 'required',
                'cost' => 'required',
                'unit_id' => 'required',
            ], [
                'code.unique' => 'This code already used. Generate Now',
                'code.required' => 'This field is required',
            ]);

            \DB::transaction(function () use ($request, $id) {

                $Product = Product::where('id', $id)
                    ->where('deleted_at', '=', null)
                    ->first();

                //-- Update Product
                $Product->name = $request['name'];
                // $Product->kh_name = $request['kh_name'];
                $Product->code = $request['code'];
                $Product->Type_barcode = $request['Type_barcode'];
                $Product->price = $request['price'];
                $Product->category_id = $request['category_id'];
                $Product->subcategory_id = $request['subcategory_id'];
                $Product->brand_id = $request['brand_id'] == 'null' ?Null: $request['brand_id'];
                $Product->TaxNet = $request['TaxNet'];
                $Product->tax_method = $request['tax_method'];
                $Product->note = $request['note'];
                $Product->cost = $request['cost'];
                $Product->unit_id = $request['unit_id'];
                $Product->unit_sale_id = $request['unit_sale_id'] ? $request['unit_sale_id'] : $request['unit_id'];
                $Product->unit_purchase_id = $request['unit_purchase_id'] ? $request['unit_purchase_id'] : $request['unit_id'];
                $Product->stock_alert = $request['stock_alert'];
                $Product->is_variant = $request['is_variant'] == 'true' ? 1 : 0;
                $Product->is_combine = $request['is_combine'] == 'true' ? 1 : 0;
                $Product->is_stock = $request['is_stock'];
                $Product->volume = $request['volume'];
                // Store Variants Product
                $oldVariants = ProductVariant::where('product_id', $id)
                    ->where('deleted_at', null)
                    ->get();

                $warehouses = Warehouse::where('deleted_at', null)
                    ->pluck('id')
                    ->toArray();


                if ($request['is_variant'] == 'true') {

                    if ($oldVariants->isNotEmpty()) {
                        $new_variants_id = [];
                        $var = 'id';

                        foreach ($request['variants'] as $new_id) {
                            if (array_key_exists($var, $new_id)) {
                                $new_variants_id[] = $new_id['id'];
                            } else {
                                $new_variants_id[] = 0;
                            }
                        }

                        foreach ($oldVariants as $key => $value) {
                            $old_variants_id[] = $value->id;

                            // Delete Variant
                            if (!in_array($old_variants_id[$key], $new_variants_id)) {
                                $ProductVariant = ProductVariant::findOrFail($value->id);
                                $ProductVariant->deleted_at = Carbon::now();
                                $ProductVariant->save();

                                $ProductWarehouse = product_warehouse::where('product_variant_id', $value->id)
                                    ->update(['deleted_at' => Carbon::now()]);
                            }
                        }

                        foreach ($request['variants'] as $key => $variant) {
                            if (array_key_exists($var, $variant)) {

                                $ProductVariantDT = new ProductVariant;

                                //-- Field Required
                                $ProductVariantDT->product_id = $variant['product_id'];
                                $ProductVariantDT->name = $variant['name'];
                                $ProductVariantDT->qty = $variant['qty'];
                                $ProductVariantDT->price = $variant['price'];
                                $ProductVariantUP['product_id'] = $variant['product_id'];
                                $ProductVariantUP['name'] = $variant['name'];
                                $ProductVariantUP['qty'] = $variant['qty'];
                                $ProductVariantUP['price'] = $variant['price'];

                            } else {
                                $ProductVariantDT = new ProductVariant;

                                //-- Field Required
                                $ProductVariantDT->product_id = $id;
                                $ProductVariantDT->name = $variant['name'];
                                $ProductVariantDT->qty = $variant['qty'];
                                $ProductVariantDT['price'] = $variant['price'];
                                $ProductVariantUP['product_id'] = $id;
                                $ProductVariantUP['name'] = $variant['name'];
                                $ProductVariantUP['qty'] =$variant['qty'];
                                $ProductVariantUP['price'] = $variant['price'];
                            }

                            if (!in_array($new_variants_id[$key], $old_variants_id)) {
                                $ProductVariantDT->save();

                                //--Store Product warehouse
                                if ($warehouses) {
                                    $product_warehouse= [];
                                    foreach ($warehouses as $warehouse) {
                                         $product_warehouse_data = product_warehouse::where('product_id', $id)
                                            ->where('deleted_at', '=', null)
                                            ->where('warehouse_id','=', $warehouse)
                                            ->first();
                                        $product_warehouse[] = [
                                            'product_id' => $id,
                                            'warehouse_id' => $warehouse,
                                            'product_variant_id' => $ProductVariantDT->id,
                                            'qte' => $product_warehouse_data->qte,
                                        ];

                                    }
                                    product_warehouse::insert($product_warehouse);
                                }
                            } else {
                                ProductVariant::where('id', $variant['id'])->update($ProductVariantUP);
                            }
                        }

                    } else {
                        $ProducttWarehouse = product_warehouse::where('product_id', $id)
                            ->update([
                                'deleted_at' => Carbon::now(),
                            ]);

                        $ProducttWarehouse_qte = product_warehouse::where('product_id', $id)->where('product_variant_id','=',null)->first();
                        if($ProducttWarehouse_qte){
                            $qty = $ProducttWarehouse_qte->qte;
                        }

                        foreach ($request['variants'] as $variant) {
                            $product_warehouse_DT = [];
                            $ProductVarDT = new ProductVariant;

                            //-- Field Required
                            $ProductVarDT->product_id = $id;
                            $ProductVarDT->name = $variant['name'];
                            $ProductVarDT->price = $variant['price'];
                            $ProductVarDT->qty = $variant['qty'];
                            $ProductVarDT->save();

                            //-- Store Product warehouse
                            if ($warehouses) {
                                foreach ($warehouses as $warehouse) {

                                    $product_warehouse_DT[] = [
                                        'product_id' => $id,
                                        'warehouse_id' => $warehouse,
                                        'product_variant_id' => $ProductVarDT->id,
                                        'qte' =>  $qty > 0 ? $qty : 0,
                                    ];
                                }

                                product_warehouse::insert($product_warehouse_DT);
                            }
                        }

                    }
                } else {
                    if ($oldVariants->isNotEmpty()) {
                        foreach ($oldVariants as $old_var) {
                            $var_old = ProductVariant::where('product_id', $old_var['product_id'])
                                ->where('deleted_at', null)
                                ->first();
                            $var_old->deleted_at = Carbon::now();
                            $var_old->save();

                            $ProducttWarehouse = product_warehouse::where('product_variant_id', $old_var['id'])
                                ->update([
                                    'deleted_at' => Carbon::now(),
                                ]);
                        }

                        if ($warehouses) {
                            foreach ($warehouses as $warehouse) {

                                $product_warehouse[] = [
                                    'product_id' => $id,
                                    'warehouse_id' => $warehouse,
                                    'product_variant_id' => null,
                                ];

                            }
                            product_warehouse::insert($product_warehouse);
                        }
                    }
                }

                $productCombines = Combine::where('product_id',$id)->get();
                foreach($productCombines as $productCombine){
                    $productCombine->delete();
                }
                if ($request['is_combine'] == 'true') {
                    foreach ($request['combines'] as $combine) {     
                        $Product_combine_data[] = [
                            'product_id' => $id,
                            'product_combine_id' => $combine['product_combine_id'],
                            'qty' => $combine['qty'],
                        ];
                    }
                    Combine::insert($Product_combine_data);
                }
                
                    // return $Product_variants_data;
               

                if ($request['images'] === null) {

                    if ($Product->image !== null) {
                        foreach (explode(',', $Product->image) as $img) {
                            $pathIMG = public_path() . '/images/products/' . $img;
                            if (file_exists($pathIMG)) {
                                if ($img != 'no-image.png') {
                                    @unlink($pathIMG);
                                }
                            }
                        }
                    }
                    $filename = 'no-image.png';
                } else {
                    if ($Product->image !== null) {
                        foreach (explode(',', $Product->image) as $img) {
                            $pathIMG = public_path() . '/images/products/' . $img;
                            if (file_exists($pathIMG)) {
                                if ($img != 'no-image.png') {
                                    @unlink($pathIMG);
                                }
                            }
                        }
                    }
                    $files = $request['images'];
                    foreach ($files as $file) {
                      // Extract the base64-encoded image data
                        $base64Data = preg_replace('#^data:image/\w+;base64,#i', '', $file['path']);
                        
                        // Create Intervention Image instance from base64 data
                        $image = ImageResizeOne::make(base64_decode($base64Data));

                        // Convert PNG to JPG
                        if ($image->mime() === 'image/png') {
                            $image->encode('jpg', 75);
                        }

                        // Resize the image
                        $image->resize(200, 200);

                        // Generate a unique filename
                        $name = rand(11111111, 99999999) . pathinfo($file['name'], PATHINFO_FILENAME) . '.jpg';

                        // Specify the path to save the image
                        $path = public_path('images/products/' . $name);
                        // Save the image
                        $image->save($path);

                        // Add the filename to the array
                        $images[] = $name;
                    }
                    $filename = implode(",", $images);
                }

                $Product->image = $filename;
                $Product->save();

            }, 10);

            return response()->json(['success' => true]);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'msg' => 'error',
                'errors' => $e->errors(),
            ], 422);
        }

    }

    //-------------- Remove Product  ---------------\\
    //-----------------------------------------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Product::class);

        \DB::transaction(function () use ($id) {

            $Product = Product::findOrFail($id);
            $Product->deleted_at = Carbon::now();
            $Product->save();

            foreach (explode(',', $Product->image) as $img) {
                $pathIMG = public_path() . '/images/products/' . $img;
                if (file_exists($pathIMG)) {
                    if ($img != 'no-image.png') {
                        @unlink($pathIMG);
                    }
                }
            }

            product_warehouse::where('product_id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);

            ProductVariant::where('product_id', $id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        return response()->json(['success' => true]);

    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'delete', Product::class);

        \DB::transaction(function () use ($request) {
            $selectedIds = $request->selectedIds;
            foreach ($selectedIds as $product_id) {

                $Product = Product::findOrFail($product_id);
                $Product->deleted_at = Carbon::now();
                $Product->save();

                foreach (explode(',', $Product->image) as $img) {
                    $pathIMG = public_path() . '/images/products/' . $img;
                    if (file_exists($pathIMG)) {
                        if ($img != 'no-image.png') {
                            @unlink($pathIMG);
                        }
                    }
                }

                product_warehouse::where('product_id', $product_id)->update([
                    'deleted_at' => Carbon::now(),
                ]);

                ProductVariant::where('product_id', $product_id)->update([
                    'deleted_at' => Carbon::now(),
                ]);
            }

        }, 10);

        return response()->json(['success' => true]);

    }

    //-------------- Export All Product to EXCEL  ---------------\\

    public function export_Excel(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Product::class);

        return Excel::download(new ProductsExport, 'List_Products.xlsx');
    }

    //--------------  Show Product Details ---------------\\

    public function Get_Products_Details(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', Product::class);

        $Product = Product::where('deleted_at', '=', null)->findOrFail($id);
        $warehouses = Warehouse::where('deleted_at', '=', null)->get();

        $item['id'] = $Product->id;
        $item['code'] = $Product->code;
        $item['Type_barcode'] = $Product->Type_barcode;
        $item['name'] = $Product->name;
        $item['kh_name'] = $Product->kh_name;
        $item['category'] = $Product['category']->name;
        $item['brand'] = $Product['brand'] ? $Product['brand']->name : 'N/D';
        $item['unit'] = $Product['unit']->ShortName;
        $item['price'] = $Product->price;
        $item['cost'] = $Product->cost;
        $item['stock_alert'] = $Product->stock_alert;
        $item['taxe'] = $Product->TaxNet;
        $item['is_stock'] = $Product->is_stock;
        $item['is_combine'] = $Product->is_combine;
        $item['tax_method'] = $Product->tax_method == '1' ? 'Exclusive' : 'Inclusive';
        $item['volume'] =  $Product->volume;
        $item['product_variant_id'] = 111111111;
        if ($Product->is_variant) {
            $item['is_variant'] = 'yes';
            $productsVariants = ProductVariant::where('product_id', $id)
                ->where('deleted_at', null)
                ->get();
            foreach ($productsVariants as $variant) {
                $item['ProductVariant'][] = $variant->name;

                foreach ($warehouses as $warehouse) {
                    $product_warehouse = DB::table('product_warehouse')
                        ->where('product_id', $id)
                        ->where('deleted_at', '=', null)
                        ->where('warehouse_id', $warehouse->id)
                        ->where('product_variant_id', $variant->id)
                        ->select(DB::raw('SUM(product_warehouse.qte) AS sum'))
                        ->first();

                    $war_var['mag'] = $warehouse->name;
                    $war_var['variant'] = $variant->name;
                    $war_var['product_variant_id'] = $variant->id;
                    $war_var['qte'] = $product_warehouse->sum;
                    $item['CountQTY_variants'][] = $war_var;
                }

            }
        } else {
            $item['is_variant'] = 'no';
            $item['CountQTY_variants'] = [];
        }

        foreach ($warehouses as $warehouse) {
            $product_warehouse_data = DB::table('product_warehouse')
                ->where('deleted_at', '=', null)
                ->where('product_id', $id)
                ->where('warehouse_id', $warehouse->id)
                ->select('qte')
                ->first();

            $war['mag'] = $warehouse->name;
            $war['qte'] = $product_warehouse_data->qte;
            $item['CountQTY'][] = $war;
        }

        if ($Product->image != '') {
            foreach (explode(',', $Product->image) as $img) {
                $item['images'][] = $img;
            }
        }

        $data[] = $item;

        return response()->json($data[0]);

    }

    //------------ Get products By Warehouse -----------------\\

    public function Products_by_Warehouse(request $request, $id)
    {
        $data = [];
        $product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')
            ->where('warehouse_id', $id)
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                if ($request->stock == '1' || $request->stock == '0') {
                    // return $query->where('qte', '>', 0);
                    return $query->where('qte', '>', 0)->orwhereHas('product', function ($q) use ($request) {
                        $q->where('is_stock', '=', 0)->orwhere('is_stock', '=', 1);
                    });
                }

            })->get();

        foreach ($product_warehouse_data as $product_warehouse) {

            if ($product_warehouse->product_variant_id) {
                $item['product_variant_id'] = $product_warehouse->product_variant_id;
                $item['code'] = $product_warehouse['productVariant']->name . '-' . $product_warehouse['product']->code;
                $item['product_code'] =$product_warehouse['product']->code;
                $item['Variant'] = $product_warehouse['productVariant']->name;
                $item['VariantQty'] = $product_warehouse['productVariant']->qty;
            } else {
                $item['product_variant_id'] = null;
                $item['Variant'] = $item['Variant'] =  $product_warehouse['product']['unitSale']->ShortName;
                $item['code'] = $product_warehouse['product']->code;
                $item['VariantQty'] = 1;
            }
            if($product_warehouse['product']->is_combine == 1){

            }
            $item['is_stock'] =$product_warehouse['product']->is_stock;
            $item['id'] = $product_warehouse->product_id;
            $item['name'] = $product_warehouse['product']->name;
            $item['kh_name'] = $product_warehouse['product']->kh_name;
            $item['barcode'] = $product_warehouse['product']->code;
            $item['Type_barcode'] = $product_warehouse['product']->Type_barcode;
            $firstimage = explode(',', $product_warehouse['product']->image);
            $item['image'] = $firstimage[0];

            if ($product_warehouse['product']['unitSale']->operator == '/') {
                $item['qte_sale'] = $product_warehouse->qte * $product_warehouse['product']['unitSale']->operator_value;
                $price = $product_warehouse['product']->price / $product_warehouse['product']['unitSale']->operator_value;
            } else {
                $item['qte_sale'] = $product_warehouse->qte / $product_warehouse['product']['unitSale']->operator_value;
                $price = $product_warehouse['product']->price * $product_warehouse['product']['unitSale']->operator_value;
            }

            if ($product_warehouse['product']['unitPurchase']->operator == '/') {
                $item['qte_purchase'] = round($product_warehouse->qte * $product_warehouse['product']['unitPurchase']->operator_value, 5);
            } else {
                $item['qte_purchase'] = round($product_warehouse->qte / $product_warehouse['product']['unitPurchase']->operator_value, 5);
            }

            $item['qte'] = $product_warehouse->qte;
            $item['unitSale'] = $product_warehouse['product']['unitSale']->ShortName;
            $item['unitPurchase'] = $product_warehouse['product']['unitPurchase']->ShortName;

            if ($product_warehouse['product']->TaxNet !== 0.0) {
                //Exclusive
                if ($product_warehouse['product']->tax_method == '1') {
                    $tax_price = $price * $product_warehouse['product']->TaxNet / 100;
                    $item['Net_price'] = $price + $tax_price;
                    // Inxclusive
                } else {
                    $item['Net_price'] = 1;
                }
            } else {
                $item['Net_price'] = 1;
            }

            $data[] = $item;
        }

        return response()->json($data);
    }

    //------------ Get product By ID -----------------\\

    public function show($id)
    {
        $varian = ProductVariant::where('product_id',$id)
            ->where('deleted_at', '=', null)->get();
        $Product_data = Product::with(['unit'])
            ->where('id', $id)
            ->where('deleted_at', '=', null)
            ->first();
        $product_variant = Product::with(['unit','ProductVariant'])
            ->where('id', $id)
            ->where('deleted_at', '=', null)
            ->first();
        $product = product_warehouse::where('product_id',$id)->first();
        $data = [];
        $item['all_qty'] = $product['qte'];
        $item['ProductVariantQty'] = $product_variant['qty'];
        $item['id'] = $Product_data['id'];
        $item['name'] = $Product_data['name'];
        $item['kh_name'] = $Product_data['kh_name'];
        $item['Type_barcode'] = $Product_data['Type_barcode'];
        $item['unit_id'] = $Product_data['unit']->id;
        $item['unit'] = $Product_data['unit']->ShortName;
        $item['purchase_unit_id'] = $Product_data['unitPurchase']->id;
        $item['unitPurchase'] = $Product_data['unitPurchase']->ShortName;
        $item['sale_unit_id'] = $Product_data['unitSale']->id;
        $item['unitSale'] = $Product_data['unitSale']->ShortName;
        $item['tax_method'] = $Product_data['tax_method'];
        $item['tax_percent'] = $Product_data['TaxNet'];
        $item['is_stock'] = $Product_data['is_stock'];
        $item['is_combine'] = $Product_data['is_combine'];
        $item['varians'] =  $varian;
        $item['volume'] =  $Product_data['volume'];
        
     
        // $product_variant = ProductVariant::find($Product_data['productVariant']->id);

        if ($Product_data['unitSale']->operator == '/') {
            if($Product_data['productVariant']){
                 $price = $Product_data['productVariant'] / $Product_data['unitSale']->operator_value;
            }else{
                 $price = $Product_data['price'] / $Product_data['unitSale']->operator_value;
            }
           

        } else {
            if($Product_data['productVariant']){
                 $price = $Product_data['productVariant'] * $Product_data['unitSale']->operator_value;
                //  $item['productVariant'] =  $Product_data['productVariant'];
            }else{
                 $price = $Product_data['price'] * $Product_data['unitSale']->operator_value;
            }
        }

        if ($Product_data['unitPurchase']->operator == '/') {
            $cost = $Product_data['cost'] / $Product_data['unitPurchase']->operator_value;
        } else {
            $cost = $Product_data['cost'] * $Product_data['unitPurchase']->operator_value;
        }

        $item['Unit_cost'] = $cost;
        $item['fix_cost'] = $Product_data['cost'];
        $item['Unit_price'] = $price;
        $item['fix_price'] = $Product_data['price'];

        if ($Product_data->TaxNet !== 0.0) {
            //Exclusive
            if ($Product_data['tax_method'] == '1') {
                $tax_price = $price * $Product_data['TaxNet'] / 100;
                $tax_cost = $cost * $Product_data['TaxNet'] / 100;

                $item['Total_cost'] = $cost + $tax_cost;
                $item['Total_price'] = $price + $tax_price;
                $item['Net_cost'] = $cost;
                $item['Net_price'] = $price;
                $item['tax_price'] = $tax_price;
                $item['tax_cost'] = $tax_cost;

                // Inxclusive
            } else {
                $item['Total_cost'] = $cost;
                $item['Total_price'] = $price;
                $item['Net_cost'] = $cost / (($Product_data['TaxNet'] / 100) + 1);
                $item['Net_price'] = $price / (($Product_data['TaxNet'] / 100) + 1);
                $item['tax_cost'] = $item['Total_cost'] - $item['Net_cost'];
                $item['tax_price'] = $item['Total_price'] - $item['Net_price'];
            }
        } else {
            $item['Total_cost'] = $cost;
            $item['Total_price'] = $price;
            $item['Net_cost'] = $cost;
            $item['Net_price'] = $price;
            $item['tax_price'] = 0;
            $item['tax_cost'] = 0;
        }

        $data[] = $item;
        
        // return $product;
       
        return response()->json($data[0]);
    }

    public function product_varian($id){
         $varian = ProductVariant::where('id',$id)
            ->where('deleted_at', '=', null)->first();
        return $varian;
    }

   
    //--------------  Product Quantity Alerts ---------------\\

    public function Products_Alert(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Stock_Alerts', Product::class);

        $product_warehouse_data = product_warehouse::with('warehouse', 'product', 'productVariant')
            ->join('products', 'product_warehouse.product_id', '=', 'products.id')
            ->whereRaw('qte <= stock_alert')
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('warehouse'), function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse);
                });
            })->where('product_warehouse.deleted_at', null)->get();

        $data = [];

        if ($product_warehouse_data->isNotEmpty()) {

            foreach ($product_warehouse_data as $product_warehouse) {
                if ($product_warehouse->qte <= $product_warehouse['product']->stock_alert) {
                    if ($product_warehouse->product_variant_id !== null) {
                        $item['code'] = $product_warehouse['productVariant']->name . '-' . $product_warehouse['product']->code;
                    } else {
                        $item['code'] = $product_warehouse['product']->code;
                    }
                    $item['quantity'] = $product_warehouse->qte;
                    $item['name'] = $product_warehouse['product']->name;
                    $item['kh_name'] = $product_warehouse['product']->kh_name;
                    $item['warehouse'] = $product_warehouse['warehouse']->name;
                    $item['stock_alert'] = $product_warehouse['product']->stock_alert;
                    $data[] = $item;
                }
            }
        }

        $perPage = $request->limit; // How many items do you want to display.
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $collection = collect($data);
        // Get only the items you need using array_slice
        $data_collection = $collection->slice($offSet, $perPage)->values();

        $products = new LengthAwarePaginator($data_collection, count($data), $perPage, Paginator::resolveCurrentPage(), array('path' => Paginator::resolveCurrentPath()));
        $warehouses = Warehouse::where('deleted_at', null)->get(['id', 'name']);

        return response()->json([
            'products' => $products,
            'warehouses' => $warehouses,
        ]);
    }

    //---------------- Show Form Create Product ---------------\\

    public function create(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'create', Product::class);

        $categories = Category::where('deleted_at', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', null)->get(['id', 'name']);
        $units = Unit::where('deleted_at', null)->where('base_unit', null)->get();
        return response()->json([
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
        ]);

    }

    //---------------- Show Elements Barcode ---------------\\

    public function Get_element_barcode(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'barcode', Product::class);

        $warehouses = Warehouse::where('deleted_at', null)->get(['id', 'name']);
        return response()->json(['warehouses' => $warehouses]);

    }

    //---------------- Show Form Edit Product ---------------\\

    public function edit(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'update', Product::class);

        $Product = Product::where('deleted_at', '=', null)->findOrFail($id);

        $item['id'] = $Product->id;
        $item['code'] = $Product->code;
        $item['Type_barcode'] = $Product->Type_barcode;
        $item['name'] = $Product->name;
        $item['kh_name'] = $Product->kh_name;
        $item['is_stock'] = $Product->is_stock;
        if ($Product->category_id) {
            if (Category::where('id', $Product->category_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $item['category_id'] = $Product->category_id;
            } else {
                $item['category_id'] = '';
            }
        } else {
            $item['category_id'] = '';
        }

        if ($Product->brand_id) {
            if (Brand::where('id', $Product->brand_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $item['brand_id'] = $Product->brand_id;
            } else {
                $item['brand_id'] = '';
            }
        } else {
            $item['brand_id'] = '';
        }

        if ($Product->unit_id) {
            if (Unit::where('id', $Product->unit_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $item['unit_id'] = $Product->unit_id;
            } else {
                $item['unit_id'] = '';
            }

            if (Unit::where('id', $Product->unit_sale_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $item['unit_sale_id'] = $Product->unit_sale_id;
            } else {
                $item['unit_sale_id'] = '';
            }

            if (Unit::where('id', $Product->unit_purchase_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $item['unit_purchase_id'] = $Product->unit_purchase_id;
            } else {
                $item['unit_purchase_id'] = '';
            }

        } else {
            $item['unit_id'] = '';
        }

        $item['tax_method'] = $Product->tax_method;
        $item['subcategory_id'] = $Product->subcategory_id;
        $item['price'] = $Product->price;
        $item['cost'] = $Product->cost;
        $item['stock_alert'] = $Product->stock_alert;
        $item['TaxNet'] = $Product->TaxNet;
        $item['volume'] = $Product->volume;
        $item['note'] = $Product->note ? $Product->note : '';
        $item['images'] = [];
        if ($Product->image != '' && $Product->image != 'no-image.png') {
            foreach (explode(',', $Product->image) as $img) {
                $path = public_path() . '/images/products/' . $img;
                if (file_exists($path)) {
                    $itemImg['name'] = $img;
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $itemImg['path'] = 'data:image/' . $type . ';base64,' . base64_encode($data);

                    $item['images'][] = $itemImg;
                }
            }
        } else {
            $item['images'] = [];
        }
        if ($Product->is_variant) {
            $item['is_variant'] = true;
            $productsVariants = ProductVariant::where('product_id', $id)
                ->where('deleted_at', null)
                ->get();
            foreach ($productsVariants as $variant) {
                $variant_item['id'] = $variant->id;
                $variant_item['name'] = $variant->name;
                $variant_item['qty'] = $variant->qty;
                $variant_item['price'] = $variant->price;
                $variant_item['product_id'] = $variant->product_id;
                $item['ProductVariant'][] = $variant_item;
            }
        } else {
            $item['is_variant'] = false;
            $item['ProductVariant'] = [];
        }

        if ($Product->is_combine) {
            $item['is_combine'] = true;
            $productsCombines = Combine::where('product_id', $id)
                ->get();
            foreach ($productsCombines as $combine) {
                $combine_item['id'] = $combine->id;
                $combine_item['qty'] = $combine->qty;
                $combine_item['product_id'] = $combine->product_id;
                $combine_item['product_combine_id'] = $combine->product_combine_id;
                $item['ProductCombine'][] = $combine_item;
            }
        } else {
            $item['is_combine'] = false;
            $item['ProductCombine'] = [];
        }

        $data = $item;
        $categories = Category::where('deleted_at', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', null)->get(['id', 'name']);

        $product_units = Unit::where('id', $Product->unit_id)
                              ->orWhere('base_unit', $Product->unit_id)
                              ->where('deleted_at', null)
                              ->get();

        
        // $product_units[] = Unit::where('id', $Product->unit_id)->where('deleted_at', null)->first();
        // $product_units_child = Unit::where('base_unit', $Product->unit_id)->where('deleted_at', null)->get();

        // if ($product_units_child->isNotEmpty()) {
        //     foreach ($product_units_child as $unit_child) {
        //         $product_units[] = $unit_child;
        //     }
        // }
        
        $units = Unit::where('deleted_at', null)
            ->where('base_unit', null)
            ->get();

        return response()->json([
            'product' => $data,
            'categories' => $categories,
            'brands' => $brands,
            'units' => $units,
            'units_sub' => $product_units,
        ]);

    }

    // import Products
    public function import_products(Request $request)
    {
        try {
            \DB::transaction(function () use ($request) {
                $file_upload = $request->file('products');
                $ext = pathinfo($file_upload->getClientOriginalName(), PATHINFO_EXTENSION);
                if ($ext != 'csv') {
                    return response()->json([
                        'msg' => 'must be in csv format',
                        'status' => false,
                    ]);
                } else {
                    $data = array();
                    $rowcount = 0;
                    if (($handle = fopen($file_upload, "r")) !== false) {

                        $max_line_length = defined('MAX_LINE_LENGTH') ? MAX_LINE_LENGTH : 10000;
                        $header = fgetcsv($handle, $max_line_length);
                        $header_colcount = count($header);
                        while (($row = fgetcsv($handle, $max_line_length)) !== false) {
                            $row_colcount = count($row);
                            if ($row_colcount == $header_colcount) {
                                $entry = array_combine($header, $row);
                                $data[] = $entry;
                            } else {
                                return null;
                            }
                            $rowcount++;
                        }
                        fclose($handle);
                    } else {
                        return null;
                    }


                    $warehouses = Warehouse::where('deleted_at', null)->pluck('id')->toArray();

                    //-- Create New Product
                    foreach ($data as $key => $value) {
                        $category = Category::firstOrCreate(['name' => $value['category']]);
                        $category_id = $category->id;

                        $unit = Unit::where(['ShortName' => $value['unit']])
                            ->orWhere(['name' => $value['unit']])->first();
                        $unit_id = $unit->id;

                        if ($value['brand'] != 'N/A' && $value['brand'] != '') {
                            $brand = Brand::firstOrCreate(['name' => $value['brand']]);
                            $brand_id = $brand->id;
                        } else {
                            $brand_id = null;
                        }
                        $Product = new Product;
                        $Product->name = $value['name'] == '' ? null : $value['name'];
                        $Product->code = $value['code'] == '' ? '11111111' : $value['code'];
                        $Product->Type_barcode = 'CODE128';
                        $Product->price = $value['price'];
                        $Product->cost = $value['cost'];
                        $Product->category_id = $category_id;
                        $Product->brand_id = $brand_id;
                        $Product->TaxNet = 0;
                        $Product->tax_method = 1;
                        $Product->note = $value['note'] ? $value['note'] : '';
                        $Product->unit_id = $unit_id;
                        $Product->unit_sale_id = $unit_id;
                        $Product->unit_purchase_id = $unit_id;
                        $Product->stock_alert = $value['stock_alert'] ? $value['stock_alert'] : 0;
                        $Product->is_variant = 0;
                        $Product->image = 'no-image.png';
                        $Product->save();

                        if ($warehouses) {
                            foreach ($warehouses as $warehouse) {
                                $product_warehouse[] = [
                                    'product_id' => $Product->id,
                                    'warehouse_id' => $warehouse,
                                ];
                            }
                        }
                    }
                    if ($warehouses) {
                        product_warehouse::insert($product_warehouse);
                    }
                }
            }, 10);
            return response()->json([
                'status' => true,
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'msg' => 'error',
                'errors' => $e->errors(),
            ]);
        }

    }

    // Generate_random_code
    public function generate_random_code($value_code)
    {
        if($value_code == ''){
            $gen_code = substr(number_format(time() * mt_rand(), 0, '', ''), 0, 8);
            $this->check_code_exist($gen_code);
        }else{
            $this->check_code_exist($value_code);
        }
    }


    // check_code_exist
    public function check_code_exist($code)
    {
        $check_code = Product::where('code', $code)->first();
        if ($check_code) {
            $this->generate_random_code($code);
        } else {
            return $code;
        }



    }

    public function getProduct(){
        $products = Product::with('unit', 'category', 'brand')
            ->where('deleted_at', '=', null)->get();
        return $products; 
    }

    public function getProductByID($id){
        $products = Product::with('unit', 'category', 'brand')
             ->where('deleted_at', '=', null)->where('id', '=', $id)->get();
        return $products; 
    }

    public function Products_by_combine($id){
        $combines = Combine::where('product_id',$id)->get();
        $data =[];
        foreach($combines as $combine){
           $product_warehouse = product_warehouse::where('product_id',$combine->product_combine_id)->where('warehouse_id',1)->get();
           $data []= $product_warehouse;
           
        }
        
        $allGreaterThanZero = collect($data)->every(function ($nestedArray) {
            return collect($nestedArray)->every(function ($item) {
                return $item['qte'] > 0;
            });
        });
       if ($allGreaterThanZero) {
           return 1;
        } else {
           return 2;
        }
    }


    public function Products_combine_qty($id,$quantity){
        $combines = Combine::where('product_id',$id)->get();
        $data =[];
        foreach($combines as $combine){
           $product_warehouse = product_warehouse::where('product_id',$combine->product_combine_id)->where('warehouse_id',1)->get();
           $data []= $product_warehouse;
           
        }
      
         $minQty = PHP_INT_MAX; // Set an initial value to compare against

    // Loop through the nested arrays and find the smallest qty
        foreach ($data as $nestedArray) {
            foreach ($nestedArray as $item) {
                // $minQty = min($minQty, $item['qte']-($quantity * $combine->qty)-( $combine->qty+$combine->qty));
                $minQty = min($minQty, $item['qte'] / $combine->qty);
            }
        }
        return  $minQty;
       
    }

    public function FindProductVarians($id,$name){
        $data = ProductVariant::where('product_id',$id)->where('name',$name)->first();
        return $data;
    }




    
}
