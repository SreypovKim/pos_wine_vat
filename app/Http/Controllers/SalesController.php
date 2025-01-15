<?php

namespace App\Http\Controllers;
use Twilio\Rest\Client as Client_Twilio;
use App\Exports\SalesExport;
use App\Mail\SaleMail;
use App\Models\Client;
use App\Models\Unit;
use App\Models\PaymentSale;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\product_warehouse;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\Combine;
use App\Models\Sale;
use App\Models\Action;
use App\Models\SaleDetail;
use App\Models\Setting;
use App\Models\PaymentTerm;
use App\Models\PosSetting;
use App\Models\Warehouse;
use App\Models\ExchangeRate;
use App\Models\Service;
use App\utils\helpers;
use App\Models\SaleServiceDetail;
use App\Models\PLTRate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Stripe;
use App\Models\PaymentWithCreditCard;
use DB;
use PDF;
use DateTime;

class SalesController extends BaseController
{

    //------------- GET ALL SALES -----------\\

    public function index(request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Sale::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $helpers = new helpers();

        $start = $request->start_date;
        $end = $request->end_date;
        // Filter fields With Params to retrieve
        $param = array(
            0 => 'like',
            1 => 'like',
            2 => '=',
            3 => 'like',
            4 => '=',
            5 => '=',
        );
        $columns = array(
            0 => 'Ref',
            1 => 'statut',
            2 => 'client_id',
            3 => 'payment_statut',
            4 => 'warehouse_id',
            5 => 'date',
        );
        $data = array();

        // Check If User Has Permission View  All Records
        $Sales = Sale::with('facture', 'client', 'warehouse')
            ->where('deleted_at', '=', null)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            });
        //Multiple Filter
        $Filtred = $helpers->filter($Sales, $columns, $param, $request)
        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('Ref', 'LIKE', "%{$request->search}%")
                        ->orWhere('statut', 'LIKE', "%{$request->search}%")
                        ->orWhere('GrandTotal', $request->search)
                        ->orWhere('payment_statut', 'like', "$request->search")
                        ->orWhere('exp_date', 'like', "$request->search")
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('client', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        })
                        ->orWhere(function ($query) use ($request) {
                            return $query->whereHas('warehouse', function ($q) use ($request) {
                                $q->where('name', 'LIKE', "%{$request->search}%");
                            });
                        });
                });
            });

        $totalRows = $Filtred->count();
        // $Sales = $Filtred->offset($offSet)
        //     ->limit($perPage)
        //     ->orderBy($order, $dir)
        //     ->get();
        if($request->start_date > 0 && $request->end_date > 0){
            $Sales = Sale::where('deleted_at','=',null)
                    ->whereBetween('date',[$request->start_date." 00:00:00",$request->end_date." 23:59:59"])
                    ->limit($perPage)
                    ->get();  
                    $val =1;
                
        }else{  
            $Sales = $Filtred->offset($offSet)
            ->limit($perPage)
            ->orderBy('sales.' . $order, $dir)
            ->get();
            $val =2;
        }

        foreach ($Sales as $Sale) {

            $item['id'] = $Sale['id'];
            $item['date'] = Carbon::parse($Sale['date'])->format('d-m-Y H:i:s');
            $item['Ref'] = $Sale['Ref'];
            $item['statut'] = $Sale['statut'];
            $item['discount'] = $Sale['discount'];
            $item['shipping'] = $Sale['shipping'];
            $item['warehouse_name'] = $Sale['warehouse']['name'];
            $item['client_id'] = $Sale['client']['id'];
            $item['due_day'] = $Sale['paymentTerm'] ?  $Sale['paymentTerm']['due_day'] : 'N/A';
            $item['client_name'] = $Sale['client']['name'];
            $item['client_email'] = $Sale['client']['email'];
            $item['client_tele'] = $Sale['client']['phone'];
            $item['client_code'] = $Sale['client']['code'];
            $item['client_adr'] = $Sale['client']['adresse'];
            $item['GrandTotal'] = number_format($Sale['GrandTotal'], 2, '.', '');
            $item['paid_amount'] = number_format($Sale['paid_amount'], 2, '.', '');
            $item['due'] = number_format($item['GrandTotal'] - $item['paid_amount'], 2, '.', '');
            $item['exp_date'] = Carbon::parse($Sale['exp_date'])->format('d-m-Y');
            $item['payment_status'] = $Sale['payment_statut'];
            $item['sale_type'] = $Sale['sale_type'];
            
            $data[] = $item;
        }
        
        $stripe_key = config('app.STRIPE_KEY');
        $customers = client::where('deleted_at', '=', null)->get(['id', 'name']);
        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'stripe_key' => $stripe_key,
            'totalRows' => $totalRows,
            'sales' => $data,
            'customers' => $customers,
            'warehouses' => $warehouses,
        ]);
    }

    //------------- STORE NEW SALE-----------\\

     public function store(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'create', Sale::class);

        request()->validate([
            'client_id' => 'required',
            'warehouse_id' => 'required',
        ]);

        \DB::transaction(function () use ($request) {
            $payment_term = PaymentTerm::find($request->payment_term_id);
            $PLTRate = PLTRate::latest()->firstOrFail();
            $helpers = new helpers();
            $time = date('H:i:s');
            $order = new Sale;
            $date = $request->date;
            $order->is_pos = 0;
            $order->date = $date." ".$time;
            $order->Ref = $this->getNumberOrder();
            $order->client_id = $request->client_id;
            $order->payment_term_id = $request->payment_term_id;
            $order->exp_date = Carbon::now()->addDay($payment_term->due_day);
            $order->GrandTotal = $request->GrandTotal;
            $order->warehouse_id = $request->warehouse_id;
            $order->tax_rate = $request->tax_rate;
            $order->TaxNet = $request->TaxNet;
            $order->sale_type = $request->sale_type;
            $order->discount = $request->discount;
            $order->shipping = $request->shipping;
            $order->statut = $request->statut;
            $order->pon = $request->pon;
            $order->pod = $request->pod;
            $order->plt = $request->plt;
            $order->payment_statut = 'unpaid';
            $order->notes = $request->notes;
            $order->user_id = Auth::user()->id;
            $order->plt = $PLTRate->plt_rate;

            $order->save();

            $data = $request['details'];
            $sale_service_data = $request['service_details'];
            foreach ($data as $key => $value) {
                $unit = Unit::where('id', $value['sale_unit_id'])
                    ->first();
                $orderDetails[] = [
                    'date' => $request->date,
                    'sale_id' => $order->id,
                    'sale_unit_id' =>  $value['sale_unit_id'],
                    'quantity' => $value['quantity'],
                    'price' => $value['Net_price'],
                    'TaxNet' => $value['tax_percent'],
                    'tax_method' => $value['tax_method'],
                    'discount' => $value['discount'],
                    'discount_method' => $value['discount_Method'],
                    'product_id' => $value['product_id'],
                    'product_variant_id' => $value['product_variant_id'],
                    'total' => $value['subtotal'],
                ];


                if ($order->statut == "completed") {
                    if ($value['product_variant_id'] !== null) {
                    $product = Product::where('id', $value['product_id'])
                            ->first();
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
                                $product_warehouse->save();

                                
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
                        }

                    } else {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $order->warehouse_id)
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
                            $product_warehouse->save();


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
                    }
                }
            }
            foreach ($sale_service_data as $key => $value) {
                $serviceDetails[] = [
                    'sale_id' => $order->id,
                    'service_id' =>  $value['id'],
                    'description' => $value['description'],
                    'price' => $value['price'],
                    'total' => $value['price'],
                ];
            }
            if(sizeof($data)){
                 SaleDetail::insert($orderDetails);
            }
            if(sizeof($sale_service_data)){
                SaleServiceDetail::insert($serviceDetails);
            }

            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');

            if ($request->payment['status'] != 'pending') {
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
                        if($request->payment['Reglement'] == 'credit card'){
                            $Client = Client::whereId($request->client_id)->first();
                            Stripe\Stripe::setApiKey(config('app.STRIPE_SECRET'));

                            $PaymentWithCreditCard = PaymentWithCreditCard::where('customer_id' ,$request->client_id)->first();
                            if(!$PaymentWithCreditCard){
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
                                $PaymentCard['customer_stripe_id'] =  $customer->id;

                            }else{
                                $customer_id = $PaymentWithCreditCard->customer_stripe_id;
                                $charge = \Stripe\Charge::create([
                                    'amount' => $request['amount'] * 100,
                                    'currency' => 'usd',
                                    'customer' => $customer_id,
                                ]);
                                $PaymentCard['customer_stripe_id'] =  $customer_id;
                            }

                            $PaymentSale = new PaymentSale();
                            $PaymentSale->sale_id = $order->id;
                            $PaymentSale->Ref = app('App\Http\Controllers\PaymentSalesController')->getNumberOrder();
                            $PaymentSale->date = Carbon::now();
                            $PaymentSale->received_amount = $request->payment['received_amount'];
                            $PaymentSale->Reglement = $request->payment['Reglement'];
                            $PaymentSale->montant = $request['amount'];
                            $PaymentSale->change = $request['change'];
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
                        }else{

                            PaymentSale::create([
                                'sale_id' => $order->id,
                                'Ref' => app('App\Http\Controllers\PaymentSalesController')->getNumberOrder(),
                                'date' => Carbon::now(),
                                'received_amount' => $request->payment['received_amount'],
                                'Reglement' => $request->payment['Reglement'],
                                'montant' => $request['amount'],
                                'change' => $request['change'],
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
                
            }

        }, 10);

        return response()->json(['success' => true]);
    }



    //------------- UPDATE SALE -----------

    public function update(Request $request, $id)
    {
        $this->authorizeForUser($request->user('api'), 'update', Sale::class);

        request()->validate([
            'warehouse_id' => 'required',
            'client_id' => 'required',
        ]);

        \DB::transaction(function () use ($request, $id) {

            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $current_Sale = Sale::findOrFail($id);

            // Check If User Has Permission view All Records
            if (!$view_records) {
                // Check If User->id === Sale->id
                $this->authorizeForUser($request->user('api'), 'check_record', $current_Sale);
            }
            $old_sale_details = SaleDetail::where('sale_id', $id)->get();
           

            $new_sale_details = $request['details'];
            $new_sale_service_details = $request['service_details'];
            $length = sizeof($new_sale_details);
           
            // Get Ids for new Details
            $new_products_id = [];
            foreach ($new_sale_details as $new_detail) {
                $new_products_id[] = $new_detail['id'];
            }

            // Init Data with old Parametre
            $old_products_id = [];
            foreach ($old_sale_details as $key => $value) {
                $old_products_id[] = $value->id;
                
                //check if detail has sale_unit_id Or Null
                if($value['sale_unit_id'] !== null){
                    $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $value['product_id'])
                    ->first();
                    $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                }

                if($value['sale_unit_id'] !== null){
                    if ($current_Sale->statut == "completed") {

                        if ($value['product_variant_id'] !== null) {
                           
                            $product_warehouses = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])->get();
                            $product_variant = ProductVariant::find($value['product_variant_id']);
                            $product = Product::where('id', $value['product_id'])
                            ->first();
                            $qty =   $product_variant['qty'] *  $value['quantity'];

                            foreach($product_warehouses as $product_warehouse){
                                $product_warehouse_id = product_warehouse::find($product_warehouse->id);
                                if  ($product_warehouse_id) {
                                    if ($old_unit->operator == '/') {
                                        if($product['is_stock'] == 1){
                                            $product_warehouse_id->qte +=  $qty / $old_unit->operator_value;
                                        }else{
                                            $product_warehouse_id->qte = 0;
                                        }
                                    } else {
                                        if($product['is_stock'] == 1){
                                            $product_warehouse_id->qte +=   $qty * $old_unit->operator_value;
                                        }else{
                                             $product_warehouse_id->qte = 0;
                                        }
                                    }
                                    $product_warehouse_id->save();

                                    if($product['is_combine']){
                                    $combine_products = Combine::where('product_id',$value['product_id'])->get();
                                        foreach($combine_products as  $combine_product){
                                            $product_combine_warehouses = product_warehouse::where('warehouse_id', $current_Sale->warehouse_id)
                                            ->where('product_id',$combine_product->product_combine_id)
                                            ->get();
                                            $new_qty = $combine_product->qty * $value['quantity'];
                                            foreach($product_combine_warehouses as  $product_combine_warehouse){
                                                $product_whs = product_warehouse::where('warehouse_id', $current_Sale->warehouse_id)
                                                ->where('product_id',$product_combine_warehouse->product_id)
                                                ->first();

                                                    if ($unit->operator == '/') {
                                                        if($product['is_stock'] == 1){
                                                            $product_combine_warehouse->qte +=  $new_qty / $old_unit->operator_value;
                                                        }else{
                                                            $product_combine_warehouse->qte = 0;
                                                        }
                                                    } else {
                                                        if($product['is_stock'] == 1){
                                                            $product_combine_warehouse->qte +=  $new_qty * $old_unit->operator_value;
                                                        }else{
                                                            $product_combine_warehouse->qte = 0;
                                                        }
                                                    }
                                                    $product_combine_warehouse->save();        
                                            }        
                                        }
                                    }
                                }
                            }
                    

                           

                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();
                                $product = Product::where('id', $value['product_id'])
                                ->first();
                            if ($product_warehouse) {
                                if ($old_unit->operator == '/') {
                                    if($product['is_stock'] == 1){
                                        $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                    }else{
                                        $product_warehouse->qte = 0;
                                    }
                                } else {
                                    if($product['is_stock'] == 1){
                                        $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                    }else{
                                        $product_warehouse->qte = 0;
                                    }
                                }
                                $product_warehouse->save();

                                if($product['is_combine']){
                                 $combine_products = Combine::where('product_id',$value['product_id'])->get();
                                    foreach($combine_products as  $combine_product){
                                        $product_combine_warehouses = product_warehouse::where('warehouse_id', $current_Sale->warehouse_id)
                                        ->where('product_id',$combine_product->product_combine_id)
                                        ->get();
                                        $new_qty = $combine_product->qty * $value['quantity'];
                                        foreach($product_combine_warehouses as  $product_combine_warehouse){
                                            $product_whs = product_warehouse::where('warehouse_id', $current_Sale->warehouse_id)
                                            ->where('product_id',$product_combine_warehouse->product_id)
                                            ->first();
                                                if ($old_unit->operator == '/') {
                                                    $product_combine_warehouse->qte +=  $new_qty / $old_unit->operator_value;
                                                } else {
                                                    $product_combine_warehouse->qte +=  $new_qty * $old_unit->operator_value;
                                                }
                                                $product_combine_warehouse->save();        
                                        }        
                                    }
                                }
                            }
                        }
                    }
                 
                    // Delete Detail
                    if (!in_array($old_products_id[$key], $new_products_id)) {
                        $SaleDetail = SaleDetail::findOrFail($value->id);
                        $SaleDetail->delete();
                    }
                }
            }

            // Update Data with New request
            foreach ($new_sale_details as $prd => $prod_detail) {
                
                if($prod_detail['no_unit'] !== 0){
                    $unit_prod = Unit::where('id', $prod_detail['sale_unit_id'])->first();

                    if ($request['statut'] == "completed") {

                        if ($prod_detail['product_variant_id'] !== null) {
                            $product_warehouses = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $prod_detail['product_id'])
                                // ->where('product_variant_id', $prod_detail['product_variant_id'])
                                // ->first();
                                ->get();
                                $product = Product::where('id', $value['product_id'])
                                ->first();
                                $product_variant = ProductVariant::find($prod_detail['product_variant_id']);
                                $qty =   $product_variant['qty'] *  $prod_detail['quantity'];
                                foreach( $product_warehouses as  $product_warehouse ){
                                    $product_warehouse_id = product_warehouse::find($product_warehouse->id);
                                    if ($product_warehouse_id) {
                                        if ($unit_prod->operator == '/') {
                                            if($product['is_stock'] == 1){
                                                $product_warehouse_id->qte -= $qty / $unit_prod->operator_value;
                                            }else{
                                                $product_warehouse_id->qte = 0; 
                                            }
                                        } else {
                                            // if($product['is_stock'] == 1){
                                                $product_warehouse_id->qte -= $qty * $unit_prod->operator_value;
                                            // }else{
                                            //      $product_warehouse_id->qte = $product['is_stock']; 
                                            // }
                                        }
                                         $product_warehouse_id->save();
                                        if($product['is_combine']){
                                            $combine_products = Combine::where('product_id',$prod_detail['product_id'])->get();
                                                foreach($combine_products as  $combine_product){
                                                    $product_combine_warehouses = product_warehouse::where('warehouse_id', $request->warehouse_id)
                                                    ->where('product_id',$combine_product->product_combine_id)
                                                    ->get();
                                                    $new_qty = $combine_product->qty * $prod_detail['quantity'];
                                                    foreach($product_combine_warehouses as  $product_combine_warehouse){
                                                        $product_whs = product_warehouse::where('warehouse_id', $request->warehouse_id)
                                                        ->where('product_id',$product_combine_warehouse->product_id)
                                                        ->first();

                                                            if ($unit_prod->operator == '/') {
                                                                $product_combine_warehouse->qte -=  $new_qty / $unit_prod->operator_value;
                                                            } else {
                                                                $product_combine_warehouse->qte -=  $new_qty * $unit_prod->operator_value;
                                                            }
                                                            $product_combine_warehouse->save();        
                                                    }        
                                                }
                                            }
                                    }
                                }
                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $request->warehouse_id)
                                ->where('product_id', $prod_detail['product_id'])
                                ->first();
                                $product = Product::where('id', $prod_detail['product_id'])
                                ->first();

                            if ($product_warehouse) {
                                if ($unit_prod->operator == '/') {
                                    if($product['is_stock'] == 1){
                                        $product_warehouse->qte -= $prod_detail['quantity'] / $unit_prod->operator_value;
                                    }else{
                                        $product_warehouse->qte = 0;
                                    }
                                } else {
                                    if($product['is_stock'] == 1){
                                        $product_warehouse->qte -= $prod_detail['quantity'] * $unit_prod->operator_value;
                                    }else{
                                        $product_warehouse->qte = 0;
                                    }
                                }
                                $product_warehouse->save();
                                if($product['is_combine']){
                                 $combine_products = Combine::where('product_id',$prod_detail['product_id'])->get();
                                    foreach($combine_products as  $combine_product){
                                        $product_combine_warehouses = product_warehouse::where('warehouse_id', $request->warehouse_id)
                                        ->where('product_id',$combine_product->product_combine_id)
                                        ->get();
                                        $new_qty = $combine_product->qty * $prod_detail['quantity'];
                                        foreach($product_combine_warehouses as  $product_combine_warehouse){
                                            $product_whs = product_warehouse::where('warehouse_id', $request->warehouse_id)
                                            ->where('product_id',$product_combine_warehouse->product_id)
                                            ->first();

                                                if ($unit_prod->operator == '/') {
                                                    $product_combine_warehouse->qte -=  $new_qty / $unit_prod->operator_value;
                                                } else {
                                                    $product_combine_warehouse->qte -=  $new_qty * $unit_prod->operator_value;
                                                }
                                                $product_combine_warehouse->save();        
                                        }        
                                    }
                                }
                            
                            }
                        }

                    }
                  
                    $orderDetails['sale_id'] = $id;
                    $orderDetails['price'] = $prod_detail['Unit_price'];
                    $orderDetails['sale_unit_id'] = $prod_detail['sale_unit_id'];
                    $orderDetails['TaxNet'] = $prod_detail['tax_percent'];
                    $orderDetails['tax_method'] = $prod_detail['tax_method'];
                    $orderDetails['discount'] = $prod_detail['discount'];
                    $orderDetails['discount_method'] = $prod_detail['discount_Method'];
                    $orderDetails['quantity'] = $prod_detail['quantity'];
                    $orderDetails['product_id'] = $prod_detail['product_id'];
                    $orderDetails['product_variant_id'] = $prod_detail['product_variant_id'];
                    $orderDetails['total'] = $prod_detail['subtotal'];           
                    
                    if (!in_array($prod_detail['id'], $old_products_id)) {
                        $orderDetails['date'] = Carbon::now();
                        $orderDetails['sale_unit_id'] = $unit_prod ? $unit_prod->id : Null;
                        SaleDetail::Create($orderDetails);
                    } else {
                        SaleDetail::where('id', $prod_detail['id'])->update($orderDetails);
                    }
                }
            }

            $due = $request['GrandTotal'] - $current_Sale->paid_amount;
            if ($due === 0.0 || $due < 0.0) {
                $payment_statut = 'paid';
            } else if ($due != $request['GrandTotal']) {
                $payment_statut = 'partial';
            } else if ($due == $request['GrandTotal']) {
                $payment_statut = 'unpaid';
            }
            $payment_term = PaymentTerm::find($request['payment_term_id']);
            $date = $current_Sale->created_at;
            $exp = Carbon::parse($date)->addDays($payment_term->due_day);
            $exp_date =  $exp->format('Y-m-d');
            // $sale_service_datas = $request['service_details'];
            $old_sale_service_details = SaleServiceDetail::where('sale_id', $id)->get();
            foreach($old_sale_service_details as $old_sale_service_detail){
                $delete = SaleServiceDetail::find($old_sale_service_detail->id);
                $delete->delete();
            }
           
            
            $sale_service_data = $request['service_details'];
            foreach ($sale_service_data as $key => $value) {
                // $deleteSaleServiceDetail = SaleServiceDetail::find($id);
                // $deleteSaleServiceDetail->delete();
                $serviceDetails[] = [
                    'sale_id' => $id,
                    'service_id' =>  $value['service_id'],
                    'description' => $value['description'],
                    'price' => $value['price'],
                    'total' => $value['price'],
                ];
            }

            if(sizeof($sale_service_data)){
                SaleServiceDetail::insert($serviceDetails);
            }
           
          
                   
            $current_Sale->update([
                'date' => $request['date'],
                'client_id' => $request['client_id'],
                'warehouse_id' => $request['warehouse_id'],
                'payment_term_id' => $request['payment_term_id'],
                'exp_date' =>  $exp_date,
                'notes' => $request['notes'],
                'statut' => $request['statut'],
                'tax_rate' => $request['tax_rate'],
                'TaxNet' => $request['TaxNet'],
                'discount' => $request['discount'],
                'shipping' => $request['shipping'],
                'GrandTotal' => $request['GrandTotal'],
                'payment_statut' => $payment_statut,
                'pon' => $request['pon'],
                'pod' => $request['pod'],
                'sale_type' => $request['sale_type'],
            ]);

        }, 10);


        return response()->json(['success' => true]);
    }

    //------------- Remove SALE BY ID -----------\\

     public function destroy(Request $request, $id)
     {
         $this->authorizeForUser($request->user('api'), 'delete', Sale::class);
 
         \DB::transaction(function () use ($id, $request) {
             $role = Auth::user()->roles()->first();
             $view_records = Role::findOrFail($role->id)->inRole('record_view');
             $current_Sale = Sale::findOrFail($id);
             $old_sale_details = SaleDetail::where('sale_id', $id)->get();
 
             // Check If User Has Permission view All Records
             if (!$view_records) {
                 // Check If User->id === Sale->id
                 $this->authorizeForUser($request->user('api'), 'check_record', $current_Sale);
             }
            foreach ($old_sale_details as $key => $value) {
                
                //check if detail has sale_unit_id Or Null
                if($value['sale_unit_id'] !== null){
                    $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $value['product_id'])
                    ->first();
                    $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                }

                if ($current_Sale->statut == "completed") {

                    if ($value['product_variant_id'] !== null) {
                        $product_warehouses = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Sale->warehouse_id)
                            ->where('product_id', $value['product_id'])->get();
                            // ->where('product_variant_id', $value['product_variant_id'])
                            // ->first();
                            $product_variant = ProductVariant::find($value['product_variant_id']);
                            $qty =   $product_variant['qty'] *  $value['quantity'];
                            
                            foreach($product_warehouses as $product_warehouse){
                                $product_warehouse_id = product_warehouse::find($product_warehouse->id);
                                if ($product_warehouse_id) {
                                    if ($old_unit->operator == '/') {
                                         $product_warehouse_id->qte += $qty / $old_unit->operator_value;
                                    } else {
                                         $product_warehouse_id->qte += $qty * $old_unit->operator_value;
                                    }
                                     $product_warehouse_id->save();
                                }

                            }
                        

                    } else {
                        $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                            ->where('warehouse_id', $current_Sale->warehouse_id)
                            ->where('product_id', $value['product_id'])
                            ->first();
                        if ($product_warehouse) {
                            if ($old_unit->operator == '/') {
                                $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                            } else {
                                $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                            }
                            $product_warehouse->save();
                        }
                    }
                }
                  
            }

             $current_Sale->details()->delete();
             $current_Sale->update([
                 'deleted_at' => Carbon::now(),
             ]);

             $Payment_Sale_data = PaymentSale::where('sale_id', $id)->get();
             foreach($Payment_Sale_data as $Payment_Sale){
                 if($Payment_Sale->Reglement == 'credit card') {
                     $PaymentWithCreditCard = PaymentWithCreditCard::where('payment_id', $Payment_Sale->id)->first();
                     if($PaymentWithCreditCard){
                         $PaymentWithCreditCard->delete();
                     }
                 }
                 $Payment_Sale->delete();
             }
 
         }, 10);
 
         return response()->json(['success' => true]);
     }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'delete', Sale::class);

        \DB::transaction(function () use ($request) {
            $role = Auth::user()->roles()->first();
            $view_records = Role::findOrFail($role->id)->inRole('record_view');
            $selectedIds = $request->selectedIds;
            foreach ($selectedIds as $sale_id) {

                $current_Sale = Sale::findOrFail($sale_id);
                $old_sale_details = SaleDetail::where('sale_id', $sale_id)->get();
                // Check If User Has Permission view All Records
                if (!$view_records) {
                    // Check If User->id === current_Sale->id
                    $this->authorizeForUser($request->user('api'), 'check_record', $current_Sale);
                }
                foreach ($old_sale_details as $key => $value) {
                
                    //check if detail has sale_unit_id Or Null
                    if($value['sale_unit_id'] !== null){
                        $old_unit = Unit::where('id', $value['sale_unit_id'])->first();
                    }else{
                        $product_unit_sale_id = Product::with('unitSale')
                        ->where('id', $value['product_id'])
                        ->first();
                        $old_unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    }
    
                    if ($current_Sale->statut == "completed") {
    
                        if ($value['product_variant_id'] !== null) {
                            $product_warehouses = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])->get();
                                // ->where('product_variant_id', $value['product_variant_id'])
                                // ->first();
                            $product_variant = ProductVariant::find($value['product_variant_id']);
                            $qty =   $product_variant['qty'] *  $value['quantity'];
                            
                            foreach($product_warehouses as $product_warehouse){
                                $product_warehouse_id = product_warehouse::find($product_warehouse->id);
                                if ($product_warehouse_id) {
                                    if ($old_unit->operator == '/') {
                                        $product_warehouse_id->qte +=  $qty  / $old_unit->operator_value;
                                    } else {
                                        $product_warehouse_id->qte +=  $qty  * $old_unit->operator_value;
                                    }
                                    $product_warehouse_id->save();
                                }
                            }
                           
    
                        } else {
                            $product_warehouse = product_warehouse::where('deleted_at', '=', null)
                                ->where('warehouse_id', $current_Sale->warehouse_id)
                                ->where('product_id', $value['product_id'])
                                ->first();
                            if ($product_warehouse) {
                                if ($old_unit->operator == '/') {
                                    $product_warehouse->qte += $value['quantity'] / $old_unit->operator_value;
                                } else {
                                    $product_warehouse->qte += $value['quantity'] * $old_unit->operator_value;
                                }
                                $product_warehouse->save();
                            }
                        }
                    }
                      
                }
                $current_Sale->details()->delete();
                $current_Sale->update([
                    'deleted_at' => Carbon::now(),
                ]);

                $Payment_Sale_data = PaymentSale::where('sale_id', $sale_id)->get();
                foreach($Payment_Sale_data as $Payment_Sale){
                    if($Payment_Sale->Reglement == 'credit card') {
                        $PaymentWithCreditCard = PaymentWithCreditCard::where('payment_id', $Payment_Sale->id)->first();
                        if($PaymentWithCreditCard){
                            $PaymentWithCreditCard->delete();
                        }
                    }
                    $Payment_Sale->delete();
                }
            }

        }, 10);

        return response()->json(['success' => true]);
    }

    //------------- EXPORT  SALE to EXCEL-----------\\

    public function exportExcel(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Sale::class);
        return Excel::download(new SalesExport, 'List_Sales.xlsx');
    }

    //---------------- Get Details Sale-----------------\\

    public function show(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', Sale::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $sale_data = Sale::with(['details.product.unitSale','serviceDetails'])
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $details = array();

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === sale->id
            $this->authorizeForUser($request->user('api'), 'check_record', $sale_data);
        }

        $sale_details['id'] = $sale_data->id;
        $sale_details['action_name'] = $sale_data['action'] ? $sale_data['action']->name : "N/A" ;
        $sale_details['description'] = $sale_data->description;
        $sale_details['Ref'] = $sale_data->Ref;
        $sale_details['date'] = $sale_data->date;
        $sale_details['statut'] = $sale_data->statut;
        $sale_details['warehouse'] = $sale_data['warehouse']->name;
        $sale_details['discount'] = $sale_data->discount;
        $sale_details['shipping'] = $sale_data->shipping;
        $sale_details['tax_rate'] = $sale_data->tax_rate;
        $sale_details['plt'] = $sale_data->plt;
        $sale_details['TaxNet'] = $sale_data->TaxNet;
        $sale_details['client_name'] = $sale_data['client']->name;
        $sale_details['client_phone'] = $sale_data['client']->phone;
        $sale_details['client_adr'] = $sale_data['client']->adresse;
        $sale_details['client_email'] = $sale_data['client']->email;
        $sale_details['GrandTotal'] = number_format($sale_data->GrandTotal, 2, '.', '');
        $sale_details['paid_amount'] = number_format($sale_data->paid_amount, 2, '.', '');
        $sale_details['due'] = number_format($sale_details['GrandTotal'] - $sale_details['paid_amount'], 2, '.', '');
        $sale_details['payment_status'] = $sale_data->payment_statut;
        $sale_details['sale_type'] = $sale_data->sale_type;

        $sumtotal = 0;
        $sub_total = 0;
        foreach ($sale_data['details'] as $detail) {

             //check if detail has sale_unit_id Or Null
             if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }

            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();
                $data['variant_name'] = $productsVariants->name;
                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];

            } else {
                $data['code'] = $detail['product']['code'];
                $data['variant_name'] = $unit->ShortName;
                // $data['variant_name'] ="Unit"; 
            }

            $data['quantity'] = $detail->quantity;
            $data['total'] = $detail->total;
            $data['name'] = $detail['product']['name'];
            $data['kh_name'] = $detail['product']['kh_name'];
            $data['price'] = $detail->price;
            $data['unit_sale'] = $unit->ShortName;
            $sumtotal += $data['total'];

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = $detail->discount;
            } else {
                $data['DiscountNet'] = $detail->price * $detail->discount / 100;
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = $detail->price;
            $data['discount'] = $detail->discount;

            if ($detail->tax_method == '1') {
                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = $tax_price;
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
            }

            $sub_total = $sumtotal;
            $details[] = $data;
        }


        $service_details= [];
        foreach ($sale_data['serviceDetails'] as $service_detail) {
          
            $val['name'] = $service_detail['service']['name'];
            $val['price'] = $service_detail->price;
            $val['description'] = $service_detail->description;
            $val['total'] = $service_detail->price;
            $service_details[] = $val;
        }
        

        $company = Setting::where('deleted_at', '=', null)->first();

        return response()->json([
            'details' => $details,
            'sale' => $sale_details,
            'company' => $company,
            'service_details'=> $service_details,
            'sub_total' => $sub_total,
        ]);

    }

    //-------------- Print Invoice ---------------\\

    public function Print_Invoice_POS(Request $request, $id)
    {
        // $this->authorizeForUser($request->user('api'), 'view', PaymentSale::class);
        // $role = Auth::user()->roles()->first();
        $helpers = new helpers();
        $details = array();

        $sale = Sale::with(['details.product.unitSale', 'facture'])
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $item['id'] = $sale->id;
        $item['Ref'] = $sale->Ref;
        $item['date'] = Carbon::parse($sale->date)->format('d-m-Y H:i:s');
        $item['sale_type'] = $sale->sale_type ?? 'NULL';
        $item['pod'] = $sale->pod;
        $item['pon'] = $sale->pon;
        $item['discount'] = number_format($sale->discount, 2, '.', '');
        $item['shipping'] = number_format($sale->shipping, 2, '.', '');
        $item['taxe'] =     number_format($sale->TaxNet, 2, '.', '');
        $item['tax_rate'] = $sale->tax_rate;
        $item['client_name'] = $sale['client']->name;
        $item['client_adr'] = $sale['client']->adresse;
        $item['client_email'] = $sale['client']->email;
        $item['client_tele'] = $sale['client']->phone;
        $item['client_vat'] = $sale['client']->vat;
        $item['client_city'] = $sale['client']->city;
        $item['received_amt'] = isset($sale['facture'][0]) ? $sale['facture'][0]->received_amount : "0" ;
        $item['change'] = isset($sale['facture'][0]) ? $sale['facture'][0]->change : "0";
        $item['GrandTotal'] = number_format($sale->GrandTotal, 2, '.', '');
        $item['paid_amount'] = number_format($sale->paid_amount, 2, '.', '');
        $sum = 0;
        $sum_qty = 0;
        $tax_ex = 0;
        foreach ($sale['details'] as $detail) {

             //check if detail has sale_unit_id Or Null
             if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }

            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                    $data['name'] = $productsVariants->name . '-' . $detail['product']['name'];
                    $data['kh_name'] = $productsVariants->kh_name . '-' . $detail['product']['kh_name'];
                    $data['unit_sale'] = $productsVariants->name;
                    
                } else {
                    $data['code'] = $detail['product']['code'];
                    $data['name'] = $detail['product']['name'];
                    $data['kh_name'] = $detail['product']['kh_name'];
                    $data['unit_sale'] = $unit->ShortName;
                }
                
            $data['volume'] = $detail['product']['volume'];
            $data['price'] = $detail['product']['price'];
            $data['quantity'] = number_format($detail->quantity, 2, '.', '');
            $data['total'] = number_format($detail->total, 2, '.', '');
            $data['Tax_dt'] = $detail->TaxNet;
            $sum +=  $data['total'];  
            $sum_qty += $data['quantity']; 
            $tax_ex += $data['Tax_dt'];     

            $details[] = $data;
        }

        $subtotal = $sum;
        $sumqty = $sum_qty;
        $total_tax = $tax_ex;

        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->orderBy('id', 'DESC')
            ->get();

        $settings = Setting::where('deleted_at', '=', null)->first();
        $pos_settings = PosSetting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();
        $user =  $request->user('api');
        $warehouse = Warehouse::find(1);
        $ExchangeRate = ExchangeRate::latest()->firstOrFail();
        $PLTRate = PLTRate::latest()->firstOrFail();

        
       // $dis = ( $item['discount']/100) * $subtotal;
       // $sub_after_dis = $subtotal - $dis;
        //$exclude_PLT = $sub_after_dis - ($sub_after_dis*5/100);
        //$PLT = $sub_after_dis*5/100;
       // $include_PLT = $exclude_PLT + $PLT;
       // $VAT =  $include_PLT *10/100;
       // $USATotal =  $VAT + $sub_after_dis;
       // $Riel = $USATotal *  $ExchangeRate->exchange_rate;
       // $KHTotal = round($Riel);

        //Tax Invoice

        $dis = $item['discount'];
        $sub_after_dis = $subtotal - $dis;       
        $exclude_PLT = $sub_after_dis;
        $PLT = ($sub_after_dis* $PLTRate->plt_rate)/100;
        $include_PLT = $exclude_PLT + $PLT;
        $VAT =  $sub_after_dis *10/100;
        $USATotal =  $VAT + $include_PLT; 
        $Riel = $USATotal *  $ExchangeRate->exchange_rate;
        $KHTotal = round($Riel);

        //Commercial Invoice

        $sub_after_dis_cm = ($subtotal/1.1) - $dis;
        $exclude_PLT_cm = $sub_after_dis_cm;
        $PLT_cm = ($sub_after_dis_cm* $PLTRate->plt_rate)/100;
        $include_PLT_cm = $exclude_PLT_cm + $PLT_cm;
        $vat_cm = ($sub_after_dis_cm * 0.1);

        if($dis !== 0){
            $Total_cm = $vat_cm + $include_PLT_cm;
        }else{
            $Total_cm = $subtotal + $PLT_cm;
        }
        
        $RielCM = $Total_cm *  $ExchangeRate->exchange_rate;
        $TotalRielCM = round($RielCM);

        //$sub_after_dis_cm = $subtotal - $dis - ($total_tax*$subtotal)/100;
        //$Total_cm = $subtotal - $dis;
        //$exclude_PLT_cm = $sub_after_dis_cm - ($sub_after_dis_cm*5/100);
        //$PLT_cm = $sub_after_dis_cm*5/100;
        //$include_PLT_cm = $exclude_PLT_cm + $PLT_cm;
        //$RielCM = $Total_cm *  $ExchangeRate->exchange_rate;
        //$TotalRielCM = round($RielCM);


        return response()->json([
            'sumqty'=> $sumqty,
            'vat_cm' =>$vat_cm,
            'ExchangeRate' => $ExchangeRate,
            'KHTotal'=> $KHTotal,
            'USATotal'=>$USATotal,
            'VAT'=>$VAT,
            'include_PLT'=>$include_PLT,
            'PLT'=>$PLT,
            'exclude_PLT' => $exclude_PLT,
            'subtotal'=> $subtotal,
            'symbol' => $symbol,
            'payments' => $payments,
            'setting' => $settings,
            'pos_settings' => $pos_settings,
            'sale' => $item,
            'details' => $details,
            'user'=> $user,
            'warehouse'=> $warehouse,
            'dis' => $dis,
            'sub_after_dis' => $sub_after_dis,
            'sub_after_dis_cm' => $sub_after_dis_cm,
            'exclude_PLT_cm' =>$exclude_PLT_cm,
            'PLT_cm' =>$PLT_cm,
            'include_PLT_cm' =>$include_PLT_cm,
            'Total_cm' =>$Total_cm,
            'TotalRielCM' =>$TotalRielCM,
        ]);

    }

    //------------- GET PAYMENTS SALE -----------\\

    public function Payments_Sale(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'view', PaymentSale::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $Sale = Sale::findOrFail($id);

        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === Sale->id
            $this->authorizeForUser($request->user('api'), 'check_record', $Sale);
        }

        $payments = PaymentSale::with('sale')
            ->where('sale_id', $id)
            ->where(function ($query) use ($view_records) {
                if (!$view_records) {
                    return $query->where('user_id', '=', Auth::user()->id);
                }
            })->orderBy('id', 'DESC')->get();

        $due = $Sale->GrandTotal - $Sale->paid_amount;

        return response()->json(['payments' => $payments, 'due' => $due]);

    }

    //------------- Reference Number Order SALE -----------\\

    public function getNumberOrder()
    {

        $last = DB::table('sales')->latest('id')->first();

        if ($last) {
            $item = $last->Ref;
            $nwMsg = explode("_", $item);
            $inMsg = $nwMsg[1] + 1;
            $code = $nwMsg[0] . '_' . $inMsg;
        } else {
            $code = 'SL_1111';
        }
        return $code;
    }

    //------------- SALE PDF -----------\\

    public function Sale_PDF(Request $request, $id)
    {

        $details = array();
        $helpers = new helpers();
        $sale_data = Sale::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);

        $sale['client_name'] = $sale_data['client']->name;
        $sale['client_phone'] = $sale_data['client']->phone;
        $sale['client_adr'] = $sale_data['client']->adresse;
        $sale['client_email'] = $sale_data['client']->email;
        $sale['TaxNet'] = number_format($sale_data->TaxNet, 2, '.', '');
        $sale['discount'] = number_format($sale_data->discount, 2, '.', '');
        $sale['shipping'] = number_format($sale_data->shipping, 2, '.', '');
        $sale['statut'] = $sale_data->statut;
        $sale['Ref'] = $sale_data->Ref;
        $sale['date'] = $sale_data->date;
        $sale['GrandTotal'] = number_format($sale_data->GrandTotal, 2, '.', '');
        $sale['payment_status'] = $sale_data->payment_statut;

        $detail_id = 0;
        foreach ($sale_data['details'] as $detail) {

            //check if detail has sale_unit_id Or Null
            if($detail->sale_unit_id !== null){
                $unit = Unit::where('id', $detail->sale_unit_id)->first();
            }else{
                $product_unit_sale_id = Product::with('unitSale')
                ->where('id', $detail->product_id)
                ->first();
                $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
            }

            if ($detail->product_variant_id) {

                $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                    ->where('id', $detail->product_variant_id)->first();

                $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
            } else {
                $data['code'] = $detail['product']['code'];
            }

                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = number_format($detail->quantity, 2, '.', '');
                $data['total'] = number_format($detail->total, 2, '.', '');
                $data['name'] = $detail['product']['name'];
                $data['unitSale'] = $unit->ShortName;
                $data['price'] = number_format($detail->price, 2, '.', '');

            if ($detail->discount_method == '2') {
                $data['DiscountNet'] = number_format($detail->discount, 2, '.', '');
            } else {
                $data['DiscountNet'] = number_format($detail->price * $detail->discount / 100, 2, '.', '');
            }

            $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
            $data['Unit_price'] = number_format($detail->price, 2, '.', '');
            $data['discount'] = number_format($detail->discount, 2, '.', '');

            if ($detail->tax_method == '1') {
                $data['Net_price'] = $detail->price - $data['DiscountNet'];
                $data['taxe'] = number_format($tax_price, 2, '.', '');
            } else {
                $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                $data['taxe'] = number_format($detail->price - $data['Net_price'] - $data['DiscountNet'], 2, '.', '');
            }

            $details[] = $data;
        }
        $settings = Setting::where('deleted_at', '=', null)->first();
        $symbol = $helpers->Get_Currency_Code();

        $pdf = \PDF::loadView('pdf.sale_pdf', [
            'symbol' => $symbol,
            'setting' => $settings,
            'sale' => $sale,
            'details' => $details,
        ]);

        return $pdf->output();

    }

    //----------------Show Form Create Sale ---------------\\

    public function create(Request $request)
    {

        $this->authorizeForUser($request->user('api'), 'create', Sale::class);

        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        $payment_terms= PaymentTerm::where('deleted_at', '=', null)->get();
        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
        $plt_rates = PLTRate::latest()->firstOrFail();
        $stripe_key = config('app.STRIPE_KEY');

        return response()->json([
            'stripe_key' => $stripe_key,
            'clients' => $clients,
            'warehouses' => $warehouses,
            'payment_terms'=> $payment_terms,
            'plt_rates' => $plt_rates ? $plt_rates->plt_rate : 0,
        ]);

    }

      //------------- Show Form Edit Sale -----------\\

      public function edit(Request $request, $id)
      {
  
          $this->authorizeForUser($request->user('api'), 'update', Sale::class);
          $role = Auth::user()->roles()->first();
          $view_records = Role::findOrFail($role->id)->inRole('record_view');
          $Sale_data = Sale::with(['details.product.unitSale','serviceDetails'])
              ->where('deleted_at', '=', null)
              ->findOrFail($id);
          $details = array();
          // Check If User Has Permission view All Records
          if (!$view_records) {
              // Check If User->id === sale->id
              $this->authorizeForUser($request->user('api'), 'check_record', $Sale_data);
          }
  
          if ($Sale_data->client_id) {
              if (Client::where('id', $Sale_data->client_id)
                  ->where('deleted_at', '=', null)
                  ->first()) {
                  $sale['client_id'] = $Sale_data->client_id;
              } else {
                  $sale['client_id'] = '';
              }
          } else {
              $sale['client_id'] = '';
          }
  
          if ($Sale_data->warehouse_id) {
              if (Warehouse::where('id', $Sale_data->warehouse_id)
                  ->where('deleted_at', '=', null)
                  ->first()) {
                  $sale['warehouse_id'] = $Sale_data->warehouse_id;
              } else {
                  $sale['warehouse_id'] = '';
              }
          } else {
              $sale['warehouse_id'] = '';
          }
  
          $sale['date'] = $Sale_data->date;
          $sale['tax_rate'] = $Sale_data->tax_rate;
          $sale['TaxNet'] = $Sale_data->TaxNet;
          $sale['discount'] = $Sale_data->discount;
          $sale['shipping'] = $Sale_data->shipping;
          $sale['statut'] = $Sale_data->statut;
          $sale['notes'] = $Sale_data->notes;
          $sale['pon'] = $Sale_data->pon;
          $sale['pod'] = $Sale_data->pod;
          $sale['plt'] = $Sale_data->plt;
          $sale['sale_type'] = $Sale_data->sale_type;
      
          $detail_id = 0;
          foreach ($Sale_data['details'] as $detail) {

                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id !== null){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();
                    $data['no_unit'] = 1;
                }else{
                    $product_unit_sale_id = Product::with('unitSale')
                    ->where('id', $detail->product_id)
                    ->first();
                    $unit = Unit::where('id', $product_unit_sale_id['unitSale']->id)->first();
                    $data['no_unit'] = 0;
                }
            
        
              if ($detail->product_variant_id) {
                  $item_product = product_warehouse::where('product_id', $detail->product_id)
                      ->where('deleted_at', '=', null)
                      ->where('product_variant_id', $detail->product_variant_id)
                      ->where('warehouse_id', $Sale_data->warehouse_id)
                      ->first();
  
                  $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                      ->where('id', $detail->product_variant_id)->first();
  
                  $item_product ? $data['del'] = 0 : $data['del'] = 1;
                  $data['product_variant_id'] = $detail->product_variant_id;
                  $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                  $data['Variant'] = $productsVariants->name;
                  $data['ProductVariantQty'] = $productsVariants->qty;
                  
                 
                  if ($unit && $unit->operator == '/') {

                     $data['stock'] = 0;
                      $data['stock'] = $item_product ? $item_product->qte *  $productsVariants->qty : 0;
                  } else if ($unit && $unit->operator == '*') {
                     $data['stock'] =$item_product ? $item_product->qte / $productsVariants->qty : 0;
                    //   $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                  } else {
                      $data['stock'] = 0;
                   
                  }
  
              } else {
                  $item_product = product_warehouse::where('product_id', $detail->product_id)
                      ->where('deleted_at', '=', null)->where('warehouse_id', $Sale_data->warehouse_id)
                      ->where('product_variant_id', '=', null)->first();
  
                  $item_product ? $data['del'] = 0 : $data['del'] = 1;
                  $data['product_variant_id'] = null;
                  $data['code'] = $detail['product']['code'];
                  $data['Variant'] = $unit->ShortName;
                   $item['VariantQty'] = $item_product->qte;
                //   $data['product_variant_name'] = $unit->ShortName;

                  if ($unit && $unit->operator == '/') {
                   
                      $data['stock'] = $item_product ? $item_product->qte * 1 : 0;
                    } else if ($unit && $unit->operator == '*') {
      
                      $data['stock'] = $item_product ? $item_product->qte / 1 : 0;
                  } else {
                            $data['stock'] = 0;   
                  }

                  if($detail['product']['is_combine'] == 1){
                        $combines = Combine::where('product_id',$detail['product']['id'])->get();
                        $val =[];
                        foreach($combines as $combine){
                        $product_warehouse = product_warehouse::where('product_id',$combine->product_combine_id)->where('warehouse_id',1)->get();
                        $val []= $product_warehouse;
                        
                        }
                    
                        $minQty = PHP_INT_MAX; // Set an initial value to compare against

                    // Loop through the nested arrays and find the smallest qty
                        foreach ($val as $nestedArray) {
                            foreach ($nestedArray as $item) {
                                // $minQty = min($minQty, $item['qte']-($quantity * $combine->qty)-( $combine->qty+$combine->qty));
                                $minQty = min($minQty, $item['qte'] / $combine->qty);
                            }
                        }
                    //     $data['stock'] =  $minQty;
                        $data['stock'] = $minQty;
                        // $data['test'] = $detail['product'];
                    }
               
                }

                
                
                $data['id'] = $detail->id;
                $data['detail_id'] = $detail_id += 1;
                $data['product_id'] = $detail->product_id;
                $data['total'] = $detail->total;
                $data['name'] = $detail['product']['name'];
                $data['is_stock'] = $detail['product']['is_stock'];
                $data['is_combine'] = $detail['product']['is_combine'];
                $data['quantity'] = $detail->quantity;
                $data['qte_copy'] = $detail->quantity;
                $data['etat'] = 'current';
                $data['unitSale'] = $unit->ShortName;
                $data['sale_unit_id'] = $unit->id;
              

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }

                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;
                
                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;

                if ($detail->tax_method == '1') {
                    $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['taxe'] = $tax_price;
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                } else {
                    $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                }

               $details[] = $data;
          }
        $price_service = 0;
        foreach ($Sale_data['serviceDetails'] as $service_detail) {
                $service_data['id'] = $service_detail->id;
                $service_data['name'] = $service_detail['service']->name;
                $service_data['service_id'] = $service_detail->service_id;
                $service_data['description'] = $service_detail->description;
                $service_data['sale_id'] = $service_detail->sale_id;
                $service_data['price'] = $service_detail->price;
                $service_data['total'] = $service_detail->total;
                $price_service += $service_detail->total;
                $data_details[] = $service_data;
        }

     
  
          $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
          $payment_terms = PaymentTerm::where('deleted_at', '=', null)->get();
          $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);
          $payment_term_id = Sale::findOrFail($id);
          if (empty($data_details) ) { 
            $data_details = [];
          }
          return response()->json([
              'details' => $details,
              'sale' => $sale,
              'clients' => $clients,
              'warehouses' => $warehouses,
              'payment_terms' =>$payment_terms,
              'payment_term_id'=>$payment_term_id,
              'service_details'=>$data_details,
              'service_total_price' =>  $price_service,
          ]);
  
      }


    //------------- SEND SALE TO EMAIL -----------\\

    public function Send_Email(Request $request)
    {
        $this->authorizeForUser($request->user('api'), 'view', Sale::class);

        $sale = $request->all();
        $pdf = $this->Sale_PDF($request, $sale['id']);
        $this->Set_config_mail(); // Set_config_mail => BaseController
        $mail = Mail::to($request->to)->send(new SaleMail($sale, $pdf));
        return $mail;
    }

    //------------- Show Form Convert To Sale -----------\\

    public function Elemens_Change_To_Sale(Request $request, $id)
    {

        $this->authorizeForUser($request->user('api'), 'update', Quotation::class);
        $role = Auth::user()->roles()->first();
        $view_records = Role::findOrFail($role->id)->inRole('record_view');
        $Quotation = Quotation::with('details.product.unitSale')
            ->where('deleted_at', '=', null)
            ->findOrFail($id);
        $details = array();
        // Check If User Has Permission view All Records
        if (!$view_records) {
            // Check If User->id === Quotation->id
            $this->authorizeForUser($request->user('api'), 'check_record', $Quotation);
        }

        if ($Quotation->client_id) {
            if (Client::where('id', $Quotation->client_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $sale['client_id'] = $Quotation->client_id;
            } else {
                $sale['client_id'] = '';
            }
        } else {
            $sale['client_id'] = '';
        }

        if ($Quotation->warehouse_id) {
            if (Warehouse::where('id', $Quotation->warehouse_id)
                ->where('deleted_at', '=', null)
                ->first()) {
                $sale['warehouse_id'] = $Quotation->warehouse_id;
            } else {
                $sale['warehouse_id'] = '';
            }
        } else {
            $sale['warehouse_id'] = '';
        }

        $sale['date'] = $Quotation->date;
        $sale['TaxNet'] = $Quotation->TaxNet;
        $sale['tax_rate'] = $Quotation->tax_rate;
        $sale['discount'] = $Quotation->discount;
        $sale['shipping'] = $Quotation->shipping;
        $sale['statut'] = 'pending';
        $sale['notes'] = $Quotation->notes;

        $detail_id = 0;
        foreach ($Quotation['details'] as $detail) {
           
                //check if detail has sale_unit_id Or Null
                if($detail->sale_unit_id !== null){
                    $unit = Unit::where('id', $detail->sale_unit_id)->first();

                if ($detail->product_variant_id) {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('product_variant_id', $detail->product_variant_id)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('deleted_at', '=', null)
                        ->first();
                    $productsVariants = ProductVariant::where('product_id', $detail->product_id)
                        ->where('id', $detail->product_variant_id)->where('deleted_at', null)->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = $detail->product_variant_id;
                    $data['code'] = $productsVariants->name . '-' . $detail['product']['code'];
                
                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }

                } else {
                    $item_product = product_warehouse::where('product_id', $detail->product_id)
                        ->where('warehouse_id', $Quotation->warehouse_id)
                        ->where('product_variant_id', '=', null)
                        ->where('deleted_at', '=', null)
                        ->first();

                    $item_product ? $data['del'] = 0 : $data['del'] = 1;
                    $data['product_variant_id'] = null;
                    $data['code'] = $detail['product']['code'];

                    if ($unit && $unit->operator == '/') {
                        $data['stock'] = $item_product ? $item_product->qte * $unit->operator_value : 0;
                    } else if ($unit && $unit->operator == '*') {
                        $data['stock'] = $item_product ? $item_product->qte / $unit->operator_value : 0;
                    } else {
                        $data['stock'] = 0;
                    }
                }
                
                $data['id'] = $id;
                $data['detail_id'] = $detail_id += 1;
                $data['quantity'] = $detail->quantity;
                $data['product_id'] = $detail->product_id;
                $data['total'] = $detail->total;
                $data['name'] = $detail['product']['name'];
                $data['etat'] = 'current';
                $data['qte_copy'] = $detail->quantity;
                $data['unitSale'] = $unit->ShortName;
                $data['sale_unit_id'] = $unit->id;

                if ($detail->discount_method == '2') {
                    $data['DiscountNet'] = $detail->discount;
                } else {
                    $data['DiscountNet'] = $detail->price * $detail->discount / 100;
                }
                $tax_price = $detail->TaxNet * (($detail->price - $data['DiscountNet']) / 100);
                $data['Unit_price'] = $detail->price;
                $data['tax_percent'] = $detail->TaxNet;
                $data['tax_method'] = $detail->tax_method;
                $data['discount'] = $detail->discount;
                $data['discount_Method'] = $detail->discount_method;

                if ($detail->tax_method == '1') {
                    $data['Net_price'] = $detail->price - $data['DiscountNet'];
                    $data['taxe'] = $tax_price;
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                } else {
                    $data['Net_price'] = ($detail->price - $data['DiscountNet']) / (($detail->TaxNet / 100) + 1);
                    $data['taxe'] = $detail->price - $data['Net_price'] - $data['DiscountNet'];
                    $data['subtotal'] = ($data['Net_price'] * $data['quantity']) + ($tax_price * $data['quantity']);
                }

                $details[] = $data;
            }
        }

        $warehouses = Warehouse::where('deleted_at', '=', null)->get(['id', 'name']);
        $clients = Client::where('deleted_at', '=', null)->get(['id', 'name']);

        return response()->json([
            'details' => $details,
            'sale' => $sale,
            'clients' => $clients,
            'warehouses' => $warehouses,
        ]);

    }

    //-------------------Sms Notifications -----------------\\
    public function Send_SMS(Request $request)
    {
        $sale = Sale::where('deleted_at', '=', null)->findOrFail($request->id);
        $url = url('/api/Sale_PDF/' . $request->id);
        $receiverNumber = $sale['client']->phone;
        $message = "Dear" .' '.$sale['client']->name." \n We are contacting you in regard to a invoice #".$sale->Ref.' '.$url.' '. "that has been created on your account. \n We look forward to conducting future business with you.";
        try {
  
            $account_sid = env("TWILIO_SID");
            $auth_token = env("TWILIO_TOKEN");
            $twilio_number = env("TWILIO_FROM");
  
            $client = new Client_Twilio($account_sid, $auth_token);
            $client->messages->create($receiverNumber, [
                'from' => $twilio_number, 
                'body' => $message]);
    
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    public function GetPLTRate(Request $request){

        $plt_rate = GetPLTRate::orderBy('id', 'desc')->first();

       return $plt_rate;
    }

    public function GetExchangeRate(Request $request){

        $totalriel = ExchangeRate::orderBy('id', 'desc')->first();

       return $totalriel;
    }

    public function updateAction(Request $request ,$id){
            request()->validate([
            'action_id' => 'required',
            'description' => 'nullable',
        ]);
        $sale = Sale::find($id);

        $sale->update([
            'action_id' => $request->action_id,
            'description' => $request->description,
        ]);
         return response()->json(['success' => true]);
    }

    public function service(){

        $services = Service::where('status',"active")->get();
        return $services;
    }

 

}
