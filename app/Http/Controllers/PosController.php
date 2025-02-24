<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Client;
use App\Models\PaymentSale;
use App\Models\Product;
use App\Models\Setting;
use App\Models\PosSetting;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\PaymentWithCreditCard;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Unit;
use App\Models\Combine;
use App\Models\SaleDetail;
use App\Models\Warehouse;
use App\Models\ExchangeRate;
use App\Models\PLTRate;
use App\utils\helpers;
use Carbon\Carbon;
use DB;
use App\Models\PaymentTerm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe;

class PosController extends BaseController
{

    //------------ Create New  POS --------------\\

    public function CreatePOS(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Sales_pos', Sale::class);

        request()->validate([
            'client_id' => 'required',
            'warehouse_id' => 'required',
            'payment.amount' => 'required',
        ]);

        $item = \DB::transaction(function () use ($request) {
            $helpers = new helpers();
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $payment_term = PaymentTerm::find(1);
            $PLTRate = PLTRate::latest()->firstOrFail();
            $order = new Sale;

            $order->is_pos = 1;
            $order->date = Carbon::now();
            $order->Ref = app('App\Http\Controllers\SalesController')->getNumberOrder();
            $order->client_id = $request->client_id;
            $order->warehouse_id = $request->warehouse_id;
            $order->payment_term_id = 1;
            $order->exp_date = Carbon::now()->addDay($payment_term->due_day);
            $order->tax_rate = $request->tax_rate;
            $order->TaxNet = $request->TaxNet;
            $order->discount = $request->discount;
            $order->shipping = $request->shipping;
            $order->GrandTotal = $request->GrandTotal;
            $order->pon = $request->pon;
            $order->pod = $request->pod;
            $order->statut = 'completed';
            $order->user_id = Auth::user()->id;
            $order->plt = $PLTRate->plt_rate;

        
            $order->save();
         
            $data = $request['details'];
            foreach ($data as $key => $value) {

                $unit = Unit::where('id', $value['sale_unit_id'])
                    ->first();
                $orderDetails[] = [
                    'date' => Carbon::now(),
                    'sale_id' => $order->id,
                    'sale_unit_id' =>  $value['sale_unit_id'],
                    'quantity' => $value['quantity'],
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $value['product_variant_id'],
                    'total' => $value['subtotal'],
                    'price' => $value['Unit_price'],
                    'TaxNet' => $value['tax_percent'],
                    'tax_method' => $value['tax_method'],
                    'discount' => $value['discount'],
                    'discount_method' => $value['discount_Method'],
                ];
              

                if ($value['product_variant_id'] !== null) {
                    $product = Product::where('id', $value['product_id'])->first();
                    $product_variant = ProductVariant::find($value['product_variant_id']);
                    $product_warehouses = product_warehouse::where('warehouse_id', $order->warehouse_id)
                    ->where('product_id', $value['product_id'])->get();
                    $qty = $product_variant->qty * $value['quantity'];
                    // return $qty;
                    foreach( $product_warehouses as  $product_warehouse){
                        if ($unit && $product_warehouse) {
                            if ($unit->operator == '/') {
                                if($product['is_stock'] == 1){
                                    $product_warehouse->qte -=  $qty / $unit->operator_value;
                                }else{
                                      $product_warehouse->qte = 0;
                                }
                               
                            } else {
                                if($product['is_stock'] == 1){
                                    $product_warehouse->qte -=  $qty * $unit->operator_value;
                                }else{
                                    $product_warehouse->qte = 0;
                                }

                              
                            }
                          
                             if($product['is_combine']){
                                 $combine_products = Combine::where('product_id',$value['product_id'])->get();
                                    foreach($combine_products as  $combine_product){
                                        $product_combine_warehouses = product_warehouse::where('warehouse_id', $order->warehouse_id)
                                        ->where('product_id',$combine_product->product_combine_id)
                                        ->get();
                                        $new_qty = $combine_product->qty * $value['quantity'];
                                        foreach($product_combine_warehouses as  $product_combine_warehouse){
                                            $product_whs = product_warehouse::where('warehouse_id', $order->warehouse_id)
                                            ->where('product_id',$product_combine_warehouse->product_id)
                                            ->first();

                                                 if ($unit->operator == '/') {
                                                    $product_combine_warehouse->qte -=  $new_qty / $unit->operator_value;
                                                } else {
                                                    $product_combine_warehouse->qte -=  $new_qty * $unit->operator_value;
                                                }
                                             
                                                $product_combine_warehouse->save();        
                                    }
                                         
                                }
                            }
                        }  
    
                        $product_warehouse->save();
                    }

                } else {
                         // $product_warehouse = product_warehouse::where('warehouse_id', $order->warehouse_id)
                     $product_warehouse = product_warehouse::where('warehouse_id', $order->warehouse_id)
                        ->where('product_id', $value['product_id'])
                        ->first();
                      $product = Product::where('id', $value['product_id'])
                        ->first();
                    if ($unit && $product_warehouse) {
                        if ($unit->operator == '/') {
                            if($product['is_stock'] == 1){
                                $product_warehouse->qte -= $value['quantity'] / $unit->operator_value;
                            }else{
                                $product_warehouse->qte = 0;
                            }
                           
                        } else {
                            if($product['is_stock'] == 1){
                                $product_warehouse->qte -= $value['quantity'] * $unit->operator_value;
                            }else{
                                $product_warehouse->qte = 0;
                                
                            }
                       
                        }
                         if($product['is_combine']){
                                 $combine_products = Combine::where('product_id',$value['product_id'])->get();
                                    foreach($combine_products as  $combine_product){
                                        $product_combine_warehouses = product_warehouse::where('warehouse_id', $order->warehouse_id)
                                        ->where('product_id',$combine_product->product_combine_id)
                                        ->get();
                                        $new_qty = $combine_product->qty * $value['quantity'];
                                        foreach($product_combine_warehouses as  $product_combine_warehouse){
                                            $product_whs = product_warehouse::where('warehouse_id', $order->warehouse_id)
                                            ->where('product_id',$product_combine_warehouse->product_id)
                                            ->first();

                                                 if ($unit->operator == '/') {
                                                    $product_combine_warehouse->qte -=  $new_qty / $unit->operator_value;
                                                } else {
                                                    $product_combine_warehouse->qte -=  $new_qty * $unit->operator_value;
                                                }
                                             
                                                $product_combine_warehouse->save();        
                                    }
                                         
                                }
                            }
                          
                         $product_warehouse->save();  
                         
                                    
                    }
                }
            }
            

            SaleDetail::insert($orderDetails);

            $sale = Sale::findOrFail($order->id);
            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === sale->id
                $this->authorizeForUser($request->user('api'), 'check_record', $sale);
            }

            try {

                $total_paid = $sale->paid_amount + $request['amount'];
                $due = $sale->GrandTotal - $total_paid;

                if ($due === 0.0 || $due < 0.0) {
                    $payment_statut = 'paid';
                } else if ($due != $sale->GrandTotal) {
                    $payment_statut = 'partial';
                } else if ($due == $sale->GrandTotal) {
                    $payment_statut = 'unpaid';
                }

                if($request['amount'] > 0){
                    if ($request->payment['Reglement'] == 'credit card') {
                        $Client = Client::whereId($request->client_id)->first();
                        Stripe\Stripe::setApiKey(config('app.STRIPE_SECRET'));

                        $PaymentWithCreditCard = PaymentWithCreditCard::where('customer_id', $request->client_id)->first();
                        if (!$PaymentWithCreditCard) {
                            // Create a Customer
                            $customer = \Stripe\Customer::create([
                                'source' => $request->token,
                                'email' => $Client->email,
                            ]);

                            // Charge the Customer instead of the card:
                            $charge = \Stripe\Charge::create([
                                'amount' => $request['amount'] * 100,
                                'currency' => 'usd',
                                'customer' => $customer->id,
                            ]);
                            $PaymentCard['customer_stripe_id'] = $customer->id;

                        } else {
                            $customer_id = $PaymentWithCreditCard->customer_stripe_id;
                            $charge = \Stripe\Charge::create([
                                'amount' => $request['amount'] * 100,
                                'currency' => 'usd',
                                'customer' => $customer_id,
                            ]);
                            $PaymentCard['customer_stripe_id'] = $customer_id;
                        }

                        $PaymentSale = new PaymentSale();
                        $PaymentSale->sale_id = $order->id;
                        $PaymentSale->Ref = app('App\Http\Controllers\PaymentSalesController')->getNumberOrder();
                        $PaymentSale->date = Carbon::now();
                        $PaymentSale->Reglement = $request->payment['Reglement'];
                        $PaymentSale->montant = $request['amount'];
                        $PaymentSale->change = $request['change'];
                        $PaymentSale->notes = $request->payment['notes'];
                        $PaymentSale->user_id = Auth::user()->id;
                        $PaymentSale->save();

                        $sale->update([
                            'paid_amount' => $total_paid,
                            'payment_statut' => $payment_statut,
                        ]);

                        $PaymentCard['customer_id'] = $request->client_id;
                        $PaymentCard['payment_id'] = $PaymentSale->id;
                        $PaymentCard['charge_id'] = $charge->id;
                        PaymentWithCreditCard::create($PaymentCard);

                        // Paying Method Cash
                    } else {

                        PaymentSale::create([
                            'sale_id' => $order->id,
                            'Ref' => app('App\Http\Controllers\PaymentSalesController')->getNumberOrder(),
                            'date' => Carbon::now(),
                            'Reglement' => $request->payment['Reglement'],
                            'montant' => $request['amount'],
                            'received_amount' => $request['received_amount'],
                            'change' => $request['change'],
                            'notes' => $request->payment['notes'],
                            'user_id' => Auth::user()->id,
                        ]);

                        $sale->update([
                            'paid_amount' => $total_paid,
                            'payment_statut' => $payment_statut,
                        ]);
                    }
                }

            } catch (Exception $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            return $order->id;

        }, 10);

        return response()->json(['success' => true, 'id' => $item]);

    }

    //------------ Get Products--------------\\

    public function GetProductsByParametre(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Sales_pos', Sale::class);
        // How many items do you want to display.
        $perPage = 8;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $data = array();

        $product_warehouse_data = product_warehouse::where('warehouse_id', $request->warehouse_id)
            ->with('product', 'product.unitSale')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($request) {
                if ($request->stock == '1' || $request->stock == '0') {
                    // return $query->where('qte', '>', 0);
                    return $query->where('qte', '>', 0)->orwhereHas('product', function ($q) use ($request) {
                        $q->where('is_stock', '=', 0);
                    });
                }

            })
        // Filter
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('category_id'), function ($query) use ($request) {
                    return $query->whereHas('product', function ($q) use ($request) {
                        $q->where('category_id', '=', $request->category_id);
                    });
                });
            })
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('brand_id'), function ($query) use ($request) {
                    return $query->whereHas('product', function ($q) use ($request) {
                        $q->where('brand_id', '=', $request->brand_id);
                    });
                });
            });

        $totalRows = $product_warehouse_data->count();

        $product_warehouse_data = $product_warehouse_data
            ->offset($offSet)
            ->limit(8)
            ->get();

        foreach ($product_warehouse_data as $product_warehouse) {

             $ProductVariantQty = ProductVariant::where('product_id', $product_warehouse->product_id)
                    ->where('id', $product_warehouse->product_variant_id)
                    ->where('deleted_at', null)
                    ->first();
            if ($product_warehouse->product_variant_id ) {
             
                $productsVariants = ProductVariant::where('product_id', $product_warehouse->product_id)
                    ->where('id', $product_warehouse->product_variant_id)
                    ->where('deleted_at', null)
                    ->first();
               
                $item['product_variant_id'] = $product_warehouse->product_variant_id;
                $item['Variant'] = $productsVariants->name;
                $item['VariantQty'] = $productsVariants->qty;
                $item['product_qty'] =  $product_warehouse->qte / $productsVariants->qty;
                $item['code'] = $productsVariants->name . '-' . $product_warehouse['product']->code;
             

            } else if ($product_warehouse->product_variant_id === null) {
                $item['product_variant_id'] = null;
                $item['Variant'] = $product_warehouse['product']['unitSale']->ShortName;
                $item['code'] = $product_warehouse['product']->code;
                $item['VariantQty'] = $product_warehouse->qte;
                $item['product_qty'] =  $product_warehouse->qte;
            }
            $item['id'] = $product_warehouse->product_id;
            $item['barcode'] = $product_warehouse['product']->code;
            $item['name'] = $product_warehouse['product']->name;
            $item['kh_name'] = $product_warehouse['product']->kh_name;
            $firstimage = explode(',', $product_warehouse['product']->image);
            $item['image'] = $firstimage[0];

            if ($product_warehouse['product']['unitSale']->operator == '/') {
                $item['qte_sale'] = $product_warehouse->qte * $product_warehouse['product']['unitSale']->operator_value;
                if($product_warehouse->product_variant_id){
                    $price = $productsVariants->price;
                }else{
                    $price = $product_warehouse['product']->price;
                }         
                $price =  $price / $product_warehouse['product']['unitSale']->operator_value;

            } else {
                $item['qte_sale'] = $product_warehouse->qte / $product_warehouse['product']['unitSale']->operator_value;
                if($product_warehouse->product_variant_id){
                    $price = $productsVariants->price;
                }else{
                    $price = $product_warehouse['product']->price;
                }         
                $price = $price * $product_warehouse['product']['unitSale']->operator_value;

            }
            $item['unitSale'] = $product_warehouse['product']['unitSale']->ShortName;
            $item['qte'] = $product_warehouse->qte;

            if ($product_warehouse['product']->TaxNet !== 0.0) {

                //Exclusive
                if ($product_warehouse['product']->tax_method == '1') {
                    $tax_price = $price * $product_warehouse['product']->TaxNet / 100;

                    // $item['Net_price'] = $price + $tax_price;
                    $item['Net_price'] = $price + $tax_price;

                    // Inxclusive
                } else {
                    if($product_warehouse->product_variant_id){
                        $item['Net_price'] = $productsVariants->price;
                    }else{
                        $item['Net_price'] = $price;
                    }         
                    // $item['Net_price'] = $price;
                }
            } else {
                if($product_warehouse->product_variant_id){
                    $item['Net_price'] = $productsVariants->price;
                }else{
                    $item['Net_price'] = $price;
                }                
                
                // $item['Net_price'] = $price;
            }

        

            $data[] = $item;
        }

        return response()->json([
            'products' => $data,
            'totalRows' => $totalRows,
        ]);
    }

    //--------------------- Get Element POS ------------------------\\

    public function GetELementPos(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'Sales_pos', Sale::class);

        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
        $settings = Setting::where('deleted_at', '=', null)->first();
        $plt_rates = PLTRate::latest()->firstOrFail();

        // $pos_settings = PosSetting::where('deleted_at', '=', null)->first();
        if ($settings->warehouse_id) {
            if (Warehouse::where('id', $settings->warehouse_id)->where('deleted_at', '=', null)->first()) {
                $defaultWarehouse = $settings->warehouse_id;
            } else {
                $defaultWarehouse = '';
            }
        } else {
            $defaultWarehouse = '';
        }

        if ($settings->client_id) {
            if (Client::where('id', $settings->client_id)->where('deleted_at', '=', null)->first()) {
                $defaultClient = $settings->client_id;
            } else {
                $defaultClient = '';
            }
        } else {
            $defaultClient = '';
        }
        $categories = Category::where('deleted_at', '=', null)->get(['id', 'name']);
        $brands = Brand::where('deleted_at', '=', null)->get();
        $stripe_key = config('app.STRIPE_KEY');

        return response()->json([
            'stripe_key' => $stripe_key,
            'brands' => $brands,
            // 'pos_settings' => $pos_settings,
            'defaultWarehouse' => $defaultWarehouse,
            'defaultClient' => $defaultClient,
            'clients' => $clients,
            'warehouses' => $warehouses,
            'categories' => $categories,
            'plt_rates' => $plt_rates,
        ]);
    }
    
    public function GetExchangeRate(Request $request){

        $totalriel = ExchangeRate::orderBy('id', 'desc')->first();

       return $totalriel;
    }


    public function GetCombine(){
          $combines = Combine::all();
          return $combines;
    }

}
