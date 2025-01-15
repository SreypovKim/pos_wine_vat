<template>
  <div class="main-content">
    <breadcumb :page="$t('AddSale')" :folder="$t('ListSales')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <validation-observer ref="create_sale" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Sale">
        <b-row>
          <b-col lg="12" md="12" sm="12">
            <b-card>
              <b-row>
                <!-- date  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="date"
                    :rules="{ required: true}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('date')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="date-feedback"
                        type="date"
                        v-model="sale.date"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>
                <!-- Customer -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider name="Customer" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Customer')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="sale.client_id"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Customer')"
                        :options="clients.map(clients => ({label: clients.name, value: clients.id}))"
                     
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- warehouse -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider name="warehouse" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('warehouse')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        :disabled="details.length > 0"
                        @input="Selected_Warehouse"
                        v-model="sale.warehouse_id"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Warehouse')"
                        :options="warehouses.map(warehouses => ({label: warehouses.name, value: warehouses.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                 <!--  -->
         
                <b-col md="6" class="mb-5">
                  <h6>Payment Term</h6>
                    <v-select
                    :reduce="label => label.value"
                    :placeholder="$t('Choose_Payment_Term')"
                    v-model="sale.payment_term_id"
                    :options="payment_terms.map(payment_terms => ({label: payment_terms.name, value: payment_terms.id}))"/>
                  <div id="autocomplete" class="autocomplete">
                  </div>
                </b-col>

                <b-col lg="6" md="" class="mb-5" id="sale_type">
                  <validation-provider name="Sale Type">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Sale Type')">
                      <v-select
                        @input="ChooseSaleType"
                        v-model="sale.sale_type"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Sale_type')"
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        :options="
                          [
                            {label: 'Tax Invoice', value: '1'},
                            {label: 'Commercial Invoice', value: '2'}
                          ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>
              
               
              <b-col md="12" class="mb-4">
              <div class="tabset">
                  <!-- Tab 1 -->
                  <input type="radio" name="tabset" id="tab1" aria-controls="product" checked>
                  <label for="tab1">Product</label>
                  <!-- Tab 2 -->
                  <!-- <input type="radio" name="tabset" id="tab2" aria-controls="service">
                  <label for="tab2">Service</label> -->
                  <!-- Tab 3 -->
                  
                  
                  
                  <div class="tab-panels">
                    <section id="product" class="tab-panel">
                       <b-col md="12" class="mb-5">
                        <h6>{{$t('ProductName')}}</h6>
                      
                        <div id="autocomplete" class="autocomplete">
                          <input 
                          :placeholder="$t('Scan_Search_Product_by_Code_Name')"
                            @keyup="search()" 
                            @focus="handleFocus"
                            @blur="handleBlur"
                            v-model="search_input"  
                            class="autocomplete-input" />
                          <ul class="autocomplete-result-list" v-show="focused">
                            <li class="autocomplete-result" v-for="product_fil in product_filter" v-if="product_fil.is_stock == 1 && product_fil.qte_sale >= product_fil.VariantQty || product_fil.is_stock == 0"   @mousedown="SearchProduct(product_fil)">{{getResultValue(product_fil)}}</li>
                          </ul>
                      </div>
                      </b-col>
                      <b-col md="12" class="mb-4">
                      <h5>{{$t('order_products')}} *</h5>
                      <div class="table-responsive">
                        <table class="table table-hover">
                          <thead class="bg-gray-300">
                            <tr>
                              <th scope="col">#</th>
                              <th scope="col">{{$t('ProductName')}}</th>
                              <th scope="col">{{$t('UnitPrice')}}</th>
                              <th scope="col">{{$t('CurrentStock')}}</th>
                              <th scope="col">{{$t('Qty')}}</th>
                              <th scope="col">{{$t('Discount')}}</th>
                              <th scope="col">{{$t('Tax')}}</th>
                              <th scope="col">{{$t('SubTotal')}}</th>
                              <th scope="col" class="text-center">
                                <i class="i-Close-Window text-25"></i>
                              </th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="details.length <=0">
                              <td colspan="9">{{$t('NodataAvailable')}}</td>
                            </tr>
                            <tr v-for="detail  in details">
                              <td>{{detail.detail_id}}</td>
                              <td>
                          
                                <span>{{detail.name}}</span>
                                <i @click="Modal_Updat_Detail(detail)" class="i-Edit"></i>
                              </td>
                              <td>{{currentUser.currency}}{{formatNumber(detail.Net_price,2)}}</td>
                              <td>
                                <span
                                  class="badge badge-outline-warning">
                              {{parseInt(detail.product_qty)}} {{detail.Variant}}
                              </span>
                              </td>
                              <td>
                                <div class="quantity">
                                  <b-input-group>
                                    <b-input-group-prepend>
                                      <span
                                        class="btn btn-primary btn-sm"
                                        @click="decrement(detail ,detail.detail_id)"
                                      >-</span>
                                    </b-input-group-prepend>
                                    <input
                                      class="form-control"
                                      @keyup="Verified_Qty(detail,detail.detail_id)"
                                      :min="0.00"
                                      :max="detail.stock"
                                      v-model.number="detail.quantity"
                                    >
                                    <b-input-group-append>
                                      <span
                                        class="btn btn-primary btn-sm"
                                        @click="increment(detail ,detail.detail_id)"
                                      >+</span>
                                    </b-input-group-append>
                                  </b-input-group>
                                </div>
                              </td>
                              <td>{{currentUser.currency}}{{formatNumber(detail.DiscountNet * detail.quantity,2)}}</td>
                              <td>{{currentUser.currency}}{{formatNumber(detail.taxe  * detail.quantity,2)}}</td>
                              <td>{{currentUser.currency}}{{detail.subtotal.toFixed(2)}}</td>
                              <td>
                                <a
                                  @click="delete_Product_Detail(detail.detail_id)"
                                  class="btn btn-icon btn-sm"
                                  title="Delete"
                                >
                                  <i class="i-Close-Window text-25 text-danger"></i>
                                </a>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                       <!-- order products  -->
               
                     
                    </b-col>

                  </section>
                    <section id="service" class="tab-panel">
                       <!-- search Service  -->
   
                 <b-col md="12" class="mb-5">
                  <h6>{{$t('Service')}}</h6>
                 
                  <div id="autocomplete" class="autocomplete">
                    <input 
                     :placeholder="$t('Search Service')"
              
                      @focus="handleFocus"
                      @blur="handleBlur"
                      v-model="search_service"  
                      class="autocomplete-input" />
                    
                </div>


                
                </b-col>
               


                <!-- Product -->
               
                  <!-- Service -->
                      <div class="container-fluid">
                        <div class="scrolling-wrapper row flex-row flex-nowrap">
                          <div class="scroll" v-for="service in filteredList"  >
                            <div class="card card-block"  @click="ClickService(service.id)">
                            <div class="card-body align-items-center d-flex justify-content-center">
                                <div class="title">{{service.name}} </div>
                                
                              </div>
                            </div>
                            
                          </div>
                        </div>
                    </div>
                    <b-col md="12" class="mb-4">
                      <h5>{{$t('order_products')}} *</h5>
                      <div class="table-responsive">
                        <table class="table table-hover">
                          <thead class="bg-gray-300">
                            <tr>
                              <th scope="col">#</th>
                              <th scope="col">{{$t('Service')}}</th>
                              <th scope="col">{{$t('Description')}}</th>
                              <th scope="col">{{$t('UnitPrice')}}</th>
                              <th scope="col">{{$t('SubTotal')}}</th>
                              <th scope="col" class="text-center">
                                <i class="i-Close-Window text-25"></i>
                              </th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="service_details.length <=0">
                              <td colspan="9">{{$t('NodataAvailable')}}</td>
                            </tr>
                            <tr v-for="service_detail  in service_details">
                              <td>{{service_detail.id}}</td>
                              <td>
                          
                                <span>{{service_detail.name}}</span>
                               
                              </td>
                              <td>
                                <textarea  v-model="service_detail.description"></textarea>
                              </td>
                              <td>{{currentUser.currency}}{{formatNumber(service_detail.price,2)}}</td>
                              
                            
                              <td>{{currentUser.currency}}{{service_detail.price.toFixed(2)}}</td>
                              <td>
                                <a
                                  @click="delete_Service_Detail(service_detail.id)"
                                  class="btn btn-icon btn-sm"
                                  title="Delete"
                                >
                                  <i class="i-Close-Window text-25 text-danger"></i>
                                </a>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                       <!-- order products  -->
               
                     
                    </b-col>
                    
                      
                    </section>
                   
                  </div>
                  
                </div>
              </b-col>
                <!-- Order PON  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="Purchase Order"
                    :rules="{ required: false}"
                    v-slot="validationContext">
                    <b-form-group :label="$t('Purchase Order')">
                      <b-input-group >
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="OrderTax-feedback"
                          label="Order Tax"
                          v-model="sale.pon"
                         
                        ></b-form-input>
                      </b-input-group>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>
                 <!-- Order POD  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="date"
                    :rules="{ required: false}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Purchase Order Date')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="date-feedback"
                        type="date"
                        v-model="sale.pod"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>
                <!-- Order Tax  -->
                <b-col lg="4" md="4" sm="12" class="mb-3">
                  <validation-provider
                    name="Sale Tax"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext">
                    <b-form-group :label="$t('SaleTax')">
                      <b-input-group append="%">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="OrderTax-feedback"
                          label="Sale Tax"
                          v-model.number="sale.tax_rate"
                          @keyup="keyup_OrderTax()"
                        ></b-form-input>
                      </b-input-group>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                 <!-- PLT Tax 
                 <b-col lg="3" md="3" sm="12" class="mb-3">
                  <validation-provider
                    name="PLT Tax"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext">
                    <b-form-group :label="$t('PLTRate')">
                      <b-input-group append="%">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="OrderTax-feedback"
                          label="PLT Tax"
                          v-model.number="sale.plt_rate"
                          @keyup="keyup_PLTTax()"
                        ></b-form-input>
                      </b-input-group>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col> -->

                <!-- Discount -->
                <b-col lg="3" md="3" sm="12" class="mb-3">
                  <validation-provider
                    name="Discount"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Discount')">
                      <b-input-group append="$">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Discount-feedback"
                          label="Discount"
                          v-model.number="sale.discount"
                          @keyup="keyup_Discount()"
                        ></b-form-input>
                      </b-input-group>
                      <b-form-invalid-feedback
                        id="Discount-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Shipping  -->
                <b-col lg="3" md="3" sm="12" class="mb-3">
                  <validation-provider
                    name="Shipping"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Shipping')">
                      <b-input-group append="$">
                        <b-form-input
                          :state="getValidationState(validationContext)"
                          aria-describedby="Shipping-feedback"
                          label="Shipping"
                          v-model.number="sale.shipping"
                          @keyup="keyup_Shipping()"
                        ></b-form-input>
                      </b-input-group>

                      <b-form-invalid-feedback
                        id="Shipping-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Status  -->
                <b-col lg="3" md="3" sm="12" class="mb-3">
                  <validation-provider name="Status" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Status')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="sale.statut"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Status')"
                        :options="
                                [
                                  {label: 'completed', value: 'completed'},
                                  {label: 'Pending', value: 'pending'},
                                  {label: 'ordered', value: 'ordered'}
                                ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- PaymentStatus  -->
                <b-col md="3">
                  <validation-provider name="PaymentStatus">
                    <b-form-group :label="$t('PaymentStatus')">
                      <v-select
                        @input="Selected_PaymentStatus"
                        :reduce="label => label.value"
                        v-model="payment.status"
                        :placeholder="$t('Choose_Status')"
                        :options="
                                [
                                  {label: 'Paid', value: 'paid'},
                                  {label: 'partial', value: 'partial'},
                                  {label: 'Pending', value: 'pending'},
                                ]"
                      ></v-select>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Payment choice -->
                <b-col md="4" v-if="payment.status != 'pending'">
                  <validation-provider name="Payment choice" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Paymentchoice')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        :reduce="label => label.value"
                        @input="Selected_PaymentMethod"
                        v-model="payment.Reglement"
                        :placeholder="$t('PleaseSelect')"
                        :options="
                                  [
                                  {label: 'Cash', value: 'Cash'},
                                  {label: 'credit card', value: 'credit card'},
                                  {label: 'cheque', value: 'cheque'},
                                  {label: 'Western Union', value: 'Western Union'},
                                  {label: 'bank transfer', value: 'bank transfer'},
                                  {label: 'other', value: 'other'},
                                  ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                  <!-- Received  Amount  -->
                  <b-col md="4" v-if="payment.status != 'pending'">
                      <validation-provider
                        name="Received Amount"
                        :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                        v-slot="validationContext"
                      >
                        <b-form-group :label="$t('Received_Amount')">
                          <b-form-input
                            @keyup="Verified_Received_Amount(payment.received_amount)"
                            label="Received_Amount"
                            :placeholder="$t('Received_Amount')"
                            v-model.number="payment.received_amount"
                            :state="getValidationState(validationContext)"
                            aria-describedby="Received_Amount-feedback"
                          ></b-form-input>
                          <b-form-invalid-feedback
                            id="Received_Amount-feedback"
                          >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                        </b-form-group>
                      </validation-provider>
                    </b-col>


                <!-- Amount  -->
                <b-col md="4" v-if="payment.status != 'pending'">
                  <validation-provider
                    name="Amount"
                    :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Paying_Amount')">
                      <b-form-input
                        :disabled="payment.status == 'paid'"
                        label="Amount"
                        :placeholder="$t('Paying_Amount')"
                        v-model.number="payment.amount"
                        @keyup="Verified_paidAmount(payment.amount)"
                        :state="getValidationState(validationContext)"
                        aria-describedby="Amount-feedback"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="Amount-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- change  Amount  -->
                <b-col md="4" v-if="payment.status != 'pending'">
                  <label>{{$t('Change')}} :</label>
                  <p
                    class="change_amount"
                  >{{parseFloat(payment.received_amount - payment.amount).toFixed(2)}}</p>
                </b-col>

                 <b-col
                  md="12"
                  class="mt-3"
                  v-if="payment.status != 'pending' && payment.Reglement == 'credit card'"
                >
                  <form id="payment-form">
                    <label
                      for="card-element"
                      class="leading-7 text-sm text-gray-600"
                    >{{$t('Credit_Card_Info')}}</label>
                    <div id="card-element">
                      <!-- Elements will create input elements here -->
                    </div>
                    <!-- We'll put the error messages in this element -->
                    <div id="card-errors" role="alert"></div>
                  </form>
                </b-col>

                <b-col md="12" class="mt-3">
                  <b-form-group :label="$t('Note')">
                    <textarea
                      v-model="sale.notes"
                      rows="4"
                      class="form-control"
                      :placeholder="$t('Afewwords')"
                    ></textarea>
                  </b-form-group>
                </b-col>
                <div class="offset-md-9 col-md-3 mt-4">
                        <table class="table table-striped table-sm">
                          <tbody>
                            <tr>
                              <td class="bold">{{$t('SubTotal')}}</td>
                              <td>
                                <span>{{currentUser.currency}}{{ formatNumber(parseFloat(sub_t.toFixed(2)),2) }}</span>
                              </td>
                            </tr>
                            <tr>
                              <td class="bold">{{$t('Discount')}}</td>
                              <td>{{currentUser.currency}}{{sale.discount.toFixed(2)}}</td>
                            </tr>
                            <tr>
                              <td class="bold">{{$t('SaleTax')}}</td>
                              <td>
                                <span>{{currentUser.currency}}{{sale.TaxNet.toFixed(2)}}​({{formatNumber(sale.tax_rate,2)}} %)</span>
                              </td>
                            </tr>
                            <tr>
                              <td class="bold">{{$t('PLT')}}</td>
                              <td>
                                <span>{{currentUser.currency}}{{ formatNumber(sub_after_plt,2) }} ​({{ plt_rates}} %)</span>
                              </td>
                            </tr>
                            <tr>
                              <td class="bold">{{$t('Shipping')}}</td>
                              <td>{{currentUser.currency}}{{sale.shipping.toFixed(2)}}</td>
                            </tr>
                            <tr>
                              <td>
                                <span class="font-weight-bold">{{$t('Total')}}</span>
                              </td>
                              <td>
                                <span
                                  class="font-weight-bold"
                                >{{currentUser.currency}}{{GrandTotal.toFixed(2)}}</span>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                

                <b-col md="12">
                  <b-form-group>
                    <b-button variant="primary" :disabled="paymentProcessing" @click="Submit_Sale">{{$t('submit')}}</b-button>
                    <div v-once class="typo__p" v-if="paymentProcessing">
                    <div class="spinner sm spinner-primary mt-3"></div>
                  </div>
                  </b-form-group>
                </b-col>
              </b-row>
            </b-card>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>

    <!-- Modal Update detail Product -->
    <validation-observer ref="Update_Detail">
      <b-modal hide-footer size="md" id="form_Update_Detail" :title="detail.name">
        <b-form @submit.prevent="submit_Update_Detail">
          <b-row>
            <!-- Unit Price -->
            <b-col lg="12" md="12" sm="12">
              <validation-provider
                name="Product Price"
                :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('ProductPrice')" id="Price-input">
                  <b-form-input
                    label="Product Price"
                    v-model="detail.Unit_price"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Price-feedback"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Price-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Tax Method -->
            <b-col lg="12" md="12" sm="12">
              <validation-provider name="Tax Method" :rules="{ required: true}">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('TaxMethod')">
                  <v-select
                    :class="{'is-invalid': !!errors.length}"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="detail.tax_method"
                    :reduce="label => label.value"
                    :placeholder="$t('Choose_Method')"
                    :options="
                           [
                            {label: 'Exclusive', value: '1'},
                            {label: 'Inclusive', value: '2'}
                           ]"
                  ></v-select>
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Tax Rate -->
            <b-col lg="12" md="12" sm="12">
              <validation-provider
                name="Order Tax"
                :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('OrderTax')">
                  <b-input-group append="%">
                    <b-form-input
                      label="Order Tax"
                      v-model="detail.tax_percent"
                      :state="getValidationState(validationContext)"
                      aria-describedby="OrderTax-feedback"
                    ></b-form-input>
                  </b-input-group>
                  <b-form-invalid-feedback id="OrderTax-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Discount Method -->
            <b-col lg="12" md="12" sm="12">
              <validation-provider name="Discount Method" :rules="{ required: true}">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('Discount_Method')">
                  <v-select
                    v-model="detail.discount_Method"
                    :reduce="label => label.value"
                    :placeholder="$t('Choose_Method')"
                    :class="{'is-invalid': !!errors.length}"
                    :state="errors[0] ? false : (valid ? true : null)"
                    :options="
                           [
                            {label: 'Percent %', value: '1'},
                            {label: 'Fixed', value: '2'}
                           ]"
                  ></v-select>
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Discount Rate -->
            <b-col lg="12" md="12" sm="12">
              <validation-provider
                name="Discount Rate"
                :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Discount')">
                  <b-form-input
                    label="Discount"
                    v-model.number="detail.discount"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Discount-feedback"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Discount-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Unit Sale -->
            <b-col lg="12" md="12" sm="12">
              <validation-provider name="Unit Sale" :rules="{ required: true}">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitSale')">
                  <v-select
                    :class="{'is-invalid': !!errors.length}"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="detail.sale_unit_id"
                    :placeholder="$t('Choose_Unit_Sale')"
                    :reduce="label => label.value"
                    :options="units.map(units => ({label: units.name, value: units.id}))"
                  />
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <b-col md="12">
              <b-form-group>
                <b-button
                  variant="primary"
                  type="submit"
                  :disabled="paymentProcessing"
                >{{$t('submit')}}</b-button>
                <div v-once class="typo__p" v-if="paymentProcessing">
                  <div class="spinner sm spinner-primary mt-3"></div>
                </div>
              </b-form-group>
            </b-col>
          </b-row>
        </b-form>
      </b-modal>
    </validation-observer>
  </div>
</template>


<script>
import { mapActions, mapGetters } from "vuex";
import NProgress from "nprogress";
import { loadStripe } from "@stripe/stripe-js";

export default {
  metaInfo: {
    title: "Create Sale"
  },
  data() {
    return {
      active: 0,
      focused: false,
      timer:null,
      search_input:'',
      product_filter:[],
      stripe_key:'',
      stripe: {},
      cardElement: {},
      paymentProcessing: false,
      isLoading: true,
      warehouses: [],
      clients: [],
      client: {},
      products: [],
      details: [],
      detail: {},
      sales: [],
      payment_terms:[],
      payment: {
        status: "pending",
        Reglement: "Cash",
        amount: "",
        received_amount: "",
      },
      sale: {
        id: "",
        date: new Date().toISOString().slice(0, 10),
        statut: "completed",
        notes: "",
        client_id: 1,
        warehouse_id: 1,
        payment_term_id:1,
        tax_rate: 0,
        TaxNet: 0,
        shipping: 0,
        discount: 0,
        pod: new Date().toISOString().slice(0, 10),
        pon: "",
        sale_type: "",
        plt: 0,
      },
      timer:null,
      total: 0,
      plt_rates: "",
      sub_after_plt: "",
      GrandTotal: 0,
      sub_t: 0,
      units:[],
      services:[],
      search_service: "",
      service_details:[],
      service_price: 0,
      response_mini_qty:0,
      combines:[],
      product: {
        id: "",
        code: "",
        stock: "",
        quantity: 1,
        discount: "",
        DiscountNet: "",
        discount_Method: "",
        name: "",
        kh_name: "",
        sale_unit_id:"",
        fix_stock:"",
        fix_price:"",
        unitSale: "",
        Net_price: "",
        Unit_price: "",
        Total_price: "",
        subtotal: "",
        product_id: "",
        detail_id: "",
        taxe: "",
        tax_percent: "",
        tax_method: "",
        product_variant_id: "",
        is_stock: ""
      }
    };
  },

  computed: {
    ...mapGetters(["currentUser"]),
    filteredList() {
      return this.services.filter(service => {
        return service.name.toLowerCase().includes(this.search_service.toLowerCase())
      })
    },
  },

  mounted() {

     axios
        .get("Products/Warehouse/" + 1 + "?stock=" + 1)
         .then(response => {
            this.products = response.data;
            console.log(this.products)
             NProgress.done();

            })
          .catch(error => {
          });

      axios
        .get("sale/service")
         .then(response => {
             this.services = response.data;
             NProgress.done();
            

            })
          .catch(error => {
          });
  },
 

  methods: {

     ChooseSaleType(id) {
  this.sale_type = id; // Set the selected sale type
  
  if (this.sale_type === "2") {
    // Apply 10% tax for "Commercial Invoice"
    for (let i = 0; i < this.details.length; i++) {
      this.details[i].tax_percent = 10; // Set tax to 10%

      // Recalculate taxe for each item
      if (this.details[i].tax_method === "1") {
        // Exclusive Tax
        this.details[i].Net_price = parseFloat(
          this.details[i].Unit_price - this.details[i].DiscountNet
        );
        this.details[i].taxe = parseFloat(
          (this.details[i].tax_percent * this.details[i].Net_price) / 100
        );
      } else {
        // Inclusive Tax
        this.details[i].Net_price = parseFloat(
          (this.details[i].Unit_price - this.details[i].DiscountNet) /
            (1 + this.details[i].tax_percent / 100)
        );
        this.details[i].taxe = parseFloat(
          this.details[i].Unit_price - this.details[i].Net_price - this.details[i].DiscountNet
        );
      }
    }
  } else {
    // Reset tax_percent and taxe for "Tax Invoice"
    for (let i = 0; i < this.details.length; i++) {
      this.details[i].tax_percent = 0;
      this.details[i].taxe = 0;
    }
  }

  this.Calcul_Total(); // Update totals
  this.$forceUpdate(); // Refresh the view
},

    // changeClient(id){
    //   axios
    //     .get("sale/service/client/"+id)
    //      .then(response => {
    //          this.services = response.data;
    //          NProgress.done();
    //          console.log(response.data);
             

    //         })
    //       .catch(error => {
    //       });
    // },
    async loadStripe_payment() {
      this.stripe = await loadStripe(`${this.stripe_key}`);
      const elements = this.stripe.elements();

      this.cardElement = elements.create("card", {
        classes: {
          base:
            "bg-gray-100 rounded border border-gray-300 focus:border-indigo-500 text-base outline-none text-gray-700 p-3 leading-8 transition-colors duration-200 ease-in-out"
        }
      });

      this.cardElement.mount("#card-element");
    },
    ClickService: function(id){
       axios.get("services/" + id).then(response => {
        //  this.details.push(response.data);
        let service_detail = response.data;
            service_detail['price'] = service_detail.price;
            service_detail['quantity'] = 1;
            // this.service_details.push(service_detail);
            var value = this.service_details.find((service_details) => service_details.id == id);
            if(value){
              this.makeToast("warning", this.$t("AlreadyAdd"), this.$t("Warning"));
            }else{
             this.service_details.push(service_detail);
              this.service_price += service_detail.price;
              this.GrandTotal += service_detail.price;
            
            }
           
      });
    },
     activate (index) {
      this.active = index
    },
 

     handleFocus() {
      this.focused = true
    },

    handleBlur() {
      this.focused = false
    },
    

     //---------------------- Event Select Payment Method ------------------------------\\

    Selected_PaymentMethod(value) {
      if (value == "credit card") {
        setTimeout(() => {
          this.loadStripe_payment();
        }, 500);
      }
    },

    //---------------------- Event Select Payment Status ------------------------------\\

    Selected_PaymentStatus(value){
      if (value == "paid") {
        var payment_amount = this.GrandTotal.toFixed(2);
        this.payment.amount = this.formatNumber(payment_amount, 2);
        this.payment.received_amount = this.formatNumber(payment_amount, 2);
      }else{
        this.payment.amount = 0;
        this.payment.received_amount = 0;
      }
    },

    //---------- keyup paid Amount

    Verified_paidAmount() {
      if (isNaN(this.payment.amount)) {
        this.payment.amount = 0;
      } else if (this.payment.amount > this.payment.received_amount) {
          this.makeToast(
            "warning",
            this.$t("Paying_amount_is_greater_than_Received_amount"),
            this.$t("Warning")
          );
          this.payment.amount = 0;
      } 
      else if (this.payment.amount > this.GrandTotal) {
        this.makeToast(
          "warning",
          this.$t("Paying_amount_is_greater_than_Grand_Total"),
          this.$t("Warning")
        );
        this.payment.amount = 0;
      }
    },

    //---------- keyup Received Amount

    Verified_Received_Amount() {
      if (isNaN(this.payment.received_amount)) {
        this.payment.received_amount = 0;
      } 
    },


  
    //--- Submit Validate Create Sale
    Submit_Sale() {
      this.$refs.create_sale.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        
        } else if (this.payment.amount > this.payment.received_amount) {
          this.makeToast(
            "warning",
            this.$t("Paying_amount_is_greater_than_Received_amount"),
            this.$t("Warning")
          );
          this.payment.received_amount = 0;
        
        }
          else if (this.payment.amount > this.GrandTotal) {
            this.makeToast(
              "warning",
              this.$t("Paying_amount_is_greater_than_Grand_Total"),
              this.$t("Warning")
            );
            this.payment.amount = 0;
             
          }else{
            this.Create_Sale();
          }
      });
    },
    //---Submit Validation Update Detail
    submit_Update_Detail() {
      this.$refs.Update_Detail.validate().then(success => {
        if (!success) {
          return;
        } else {
          this.Update_Detail();
        }
      });
    },
    //---Validate State Fields
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //---------------------- Get_sales_units ------------------------------\\
    Get_sales_units(value) {
      axios
        .get("Get_sales_units?id=" + value)
        .then(({ data }) => (this.units = data));
    },

    //------ Show Modal Update Detail Product
    Modal_Updat_Detail(detail) {
      this.detail = {};
      this.Get_sales_units(detail.product_id);
      this.detail.detail_id = detail.detail_id;
      this.detail.sale_unit_id = detail.sale_unit_id;
      this.detail.name = detail.name;
      this.detail.kh_name = detail.kh_name;
      this.detail.Unit_price = detail.Unit_price;
      this.detail.fix_price = detail.fix_price;
      this.detail.fix_stock = detail.fix_stock;
      this.detail.stock = detail.stock;
      this.detail.tax_method = detail.tax_method;
      this.detail.discount_Method = detail.discount_Method;
      this.detail.discount = detail.discount;
      this.detail.quantity = detail.quantity;
      this.detail.tax_percent = detail.tax_percent;
      this.$bvModal.show("form_Update_Detail");
    },


    //------ Submit Update Detail Product

    Update_Detail() {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id === this.detail.detail_id) {

          // this.convert_unit();
           for(var k=0; k<this.units.length; k++){
              if (this.units[k].id == this.detail.sale_unit_id) {
                if(this.units[k].operator == '/'){
                  this.details[i].stock       = this.detail.fix_stock  * this.units[k].operator_value;
                  this.details[i].unitSale    = this.units[k].ShortName;

                }else{
                  this.details[i].stock       = this.detail.fix_stock  / this.units[k].operator_value;
                  this.details[i].unitSale    = this.units[k].ShortName;
                }
              }
            }

            if (this.details[i].stock < this.details[i].quantity) {
              this.details[i].quantity = this.details[i].stock;
            } else {
              this.details[i].quantity =1;
            }
                      
          this.details[i].Unit_price = this.detail.Unit_price;
          this.details[i].tax_percent = this.detail.tax_percent;
          this.details[i].tax_method = this.detail.tax_method;
          this.details[i].discount_Method = this.detail.discount_Method;
          this.details[i].discount = this.detail.discount;
          this.details[i].sale_unit_id = this.detail.sale_unit_id;

          if (this.details[i].discount_Method == "2") {
            //Fixed
            this.details[i].DiscountNet = this.details[i].discount;
          } else {
            //Percentage %
            this.details[i].DiscountNet = parseFloat(
              (this.details[i].Unit_price * this.details[i].discount) / 100
            );
          }

          if (this.details[i].tax_method == "1") {
            //Exclusive
            this.details[i].Net_price = parseFloat(
              this.details[i].Unit_price - this.details[i].DiscountNet
            );

            this.details[i].taxe = parseFloat(
              (this.details[i].tax_percent *
                (this.details[i].Unit_price - this.details[i].DiscountNet)) /
                100
            );
          } else {
            //Inclusive
            this.details[i].Net_price = parseFloat(
              (this.details[i].Unit_price - this.details[i].DiscountNet) /
                (this.details[i].tax_percent / 100 + 1)
            );

            this.details[i].taxe = parseFloat(
              this.details[i].Unit_price -
                this.details[i].Net_price -
                this.details[i].DiscountNet
            );
          }

          this.$forceUpdate();
        }
      }
      this.Calcul_Total();
      this.$bvModal.hide("form_Update_Detail");
    },



    // Search Products
    search(){

      if (this.timer) {
            clearTimeout(this.timer);
            this.timer = null;
      }

      if (this.search_input.length < 1) {

        return this.product_filter= [];
      }
      if (this.sale.warehouse_id != "" &&  this.sale.warehouse_id != null) {
        this.timer = setTimeout(() => {
          const product_filter = this.products.filter(product => product.name === this.search_input || product.code === this.search_input || product.barcode.includes(this.search_input));
          // const product_filter = this.products.filter(product => product.name === this.search_input);
         
            if(product_filter.length === 1){
 
                this.SearchProduct(product_filter[0])
            }else{
           
                this.product_filter=  this.products.filter(product => {
                  return (
                    // product.name.toLowerCase().startsWith(this.search_input.toLowerCase())
                    product.name.toLowerCase().startsWith(this.search_input.toLowerCase()) ||
                    product.code.toLowerCase().startsWith(this.search_input.toLowerCase()) ||
                    product.barcode.toLowerCase().startsWith(this.search_input.toLowerCase())
                    );
                });
            }
        }, 800);
      } else {
        this.makeToast(
          "warning",
          this.$t("SelectWarehouse"),
          this.$t("Warning")
        );
      }

    },

    //------------------------- get Result Value Search Product

    getResultValue(result) {
      return result.name + " " + "(" + result.Variant + ")";
    },

    //------------------------- Submit Search Product

    SearchProduct(result) {
      this.product = {};
      if (
        this.details.length > 0 &&
        this.details.some(detail => detail.name === result.name)
      ) {
        this.makeToast("warning", this.$t("AlreadyAdd"), this.$t("Warning"));
      } else {
        this.product.code = result.code;
        this.product.stock = result.qte_sale;
        this.product.fix_stock = result.qte;
        if (result.qte_sale < 1) {
          this.product.quantity = result.qte_sale;
        } else {
          this.product.quantity = 1;
        }
        this.product.product_variant_id = result.product_variant_id;
        this.Get_Product_Details(result.id);
      }

      this.search_input= '';
      this.product_filter = [];
    },

    //---------------------- Event Select Warehouse ------------------------------\\
    Selected_Warehouse(value) {
      this.search_input= '';
      this.product_filter = [];
      this.Get_Products_By_Warehouse(value);
    },

     //------------------------------------ Get Products By Warehouse -------------------------\\

    Get_Products_By_Warehouse(id) {
      // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
      axios
        .get("Products/Warehouse/" + id + "?stock=" + 1)
         .then(response => {
            this.products = response.data;
             NProgress.done();

            })
          .catch(error => {
          });
    },

    //----------------------------------------- Add Product to order list -------------------------\\
    add_product() {
      if (this.details.length > 0) {
        this.Last_Detail_id();
      } else if (this.details.length === 0) {
        this.product.detail_id = 1;
      }
      var value = this.details.find((details) => details.product_id == this.product.product_id);
      if(value){
         this.makeToast("warning", this.$t("AlreadyAdd"), this.$t("Warning"));
      }else{
        // console.log([this.product.current , this.product.ProductVariantQty , this.product.ProductVariantQty,this.product.is_stock])
         if(this.product.fix_stock / this.product.ProductVariantQty >= this.product.ProductVariantQty && this.product.is_stock == 1){
          if(this.product.is_combine ==1){
              this.product.quantity = 0
          }
          this.details.push(this.product);
  
          }
            
          if(this.product.is_stock == 0 ){
              this.product.quantity = 1;
              // this.details.push(this.product);
               if(this.product.is_stock == 0 ){
              this.product.quantity = 1;
                this.product.subtotal = this.product.quantity *  this.product.Net_price;
              axios
                .get("Products/combine/" + this.product.product_id)
                .then(response => {
                  
                     if(response.data == 1){
                      if(this.product.is_combine ==1){
                        this.product.quantity = 0
                      }
                      this.details.push(this.product)
                     }else{
                      this.makeToast(
                          "warning",
                          this.$t("Product combine not Stock"),
                          this.$t("Warning")
                        );
                     }
                     
                    })
                  .catch(error => {
                  });
                // ;
              
            }
          }
        
      }
     
      // console.log(this.product.product_id,this.details);
    },

    //-----------------------------------Verified QTY ------------------------------\\
    Verified_Qty(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id === id) {
          if (isNaN(detail.quantity)) {
            this.details[i].quantity = detail.stock;
           
          }
          else {
          
            if(detail.quantity > detail.stock && this.details[i].is_combine == 1){
             const relatedCombines = this.combines.filter(combine => combine.product_id === this.details[i].product_id);
             relatedCombines.forEach(combine => {  
                  const relatedItem = this.details.find(i => i.product_id === combine.product_combine_id);
                  if(relatedItem){
                  
                    
                   const find_qty = ((relatedItem.product_qty * relatedItem.ProductVariantQty)-relatedItem.ProductVariantQty* relatedItem.quantity)  / combine.qty;
                 
                    if(this.details[i].quantity >= parseInt(find_qty)){
                        this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                        this.details[i].quantity =  parseInt(find_qty);
                       
                    }
                   
                  }else{

                    if(this.details[i].quantity >=  parseInt(this.details[i].product_qty )){
                      this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                      this.details[i].quantity =  parseInt(this.details[i].product_qty);
                    }  
                  }
                  
            })
              
            }else{
              axios
                .get("Products/Products_combine_qty/" + detail.product_id+"/"+detail.quantity)
                .then(response => {
                    this.response_mini_qty = response.data;
                    })
                  .catch(error => {
              });
             const relatedCombines = this.combines.filter(combine => combine.product_combine_id === this.details[i].product_id);
              if(relatedCombines.length > 0){
                relatedCombines.forEach(combine => {  
                  const relatedItem = this.details.find(i => i.product_id === combine.product_id);
                  
                  const Item = this.details.find(i => i.product_id === combine.product_combine_id);
                  if(relatedItem){
                    
                    let find_qty_item =(this.details[i].stock-(relatedItem.quantity *combine.qty))/Item.ProductVariantQty;

                      if(this.details[i].quantity  >= parseInt(find_qty_item)){
                      this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                      this.details[i].quantity =  parseInt(find_qty_item);
                      

                      }
                
                  
                  }else{
                 
                    
                      if(!this.details[i].product_qty){
                        this.details[i].product_qty=this.details[i].stock;
                      }
                      if(this.details[i].quantity >=  parseInt(this.details[i].product_qty)){
                        this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                        this.details[i].quantity =  parseInt(this.details[i].product_qty);
                      }
                  }
                });
              }else{
                if(!this.details[i].product_qty){
                  this.details[i].product_qty = this.details[i].stock;
                }
                if(this.details[i].quantity >=  parseInt(this.details[i].product_qty)){
                  this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                  this.details[i].quantity =  parseInt(this.details[i].product_qty);
                }
              }
             
               
              
            }
          }


        }
      }
      this.$forceUpdate();
      this.Calcul_Total();
    },

  

    Verifie(qty,detail,id){
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id === id) {
          this.details[i].quantity = qty;
        }
      }
    },


    //-----------------------------------increment QTY ------------------------------\\

    increment(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (detail.quantity + 1 > this.details[i].product_qty) {
            this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
          } else {
            if( this.response_mini_qty > this.details[i].quantity && this.details[i].detail_id == id || this.response_mini_qty=="" && this.details[i].detail_id == id){
                this.details[i].quantity++;
                  if(this.response_mini_qty > this.details[i].quantity || this.response_mini_qty==""){
                      if(this.details[i].is_combine == 1){
                         const relatedCombines = this.combines.filter(combine => combine.product_id === this.details[i].product_id);
                            relatedCombines.forEach(combine => {  
                              const relatedItem = this.details.find(i => i.product_id === combine.product_combine_id);
                              if(relatedItem){
                          
                                let find_qty = ((relatedItem.product_qty *relatedItem.ProductVariantQty)-relatedItem.quantity * relatedItem.ProductVariantQty) / combine.qty
                                if(this.details[i].quantity >= parseInt(find_qty)){
                                  this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                                  this.details[i].quantity =  parseInt(find_qty);
                                 
                                  
                                }    
                              }
                            });
                      }else{
                         const relatedCombines = this.combines.filter(combine => combine.product_combine_id === this.details[i].product_id);
                          relatedCombines.forEach(combine => {  
                                const relatedItem = this.details.find(i => i.product_id === combine.product_id);
                                const Item = this.details.find(i => i.product_id === combine.product_combine_id);
                                if(relatedItem){
                                    // let find_qty_item = ((relatedItem.product_qty * combine.qty)-(relatedItem.quantity * combine.qty)) / Item.ProductVariantQty;
                                    let find_qty_item =(this.details[i].stock-(relatedItem.quantity *combine.qty))/Item.ProductVariantQty;
                                    console.log([relatedItem.product_qty * combine.qty,relatedItem.quantity , combine.qty , Item.ProductVariantQty]);
                                  
                                    if(this.details[i].quantity  >= parseInt(find_qty_item)){
                                    this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                                    this.details[i].quantity =  parseInt(find_qty_item);
                                   
                                    }
                                }
                              });
                      }

                  }else{
                    this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                    
                    
                  }

            }else{
              
                const relatedCombines = this.combines.filter(combine => combine.product_combine_id === this.details[i].product_id);
                relatedCombines.forEach(combine => {  
                    const relatedItem = this.details.find(i => i.product_id === combine.product_id);
                        const Item = this.details.find(i => i.product_id === combine.product_combine_id);
                        if(relatedItem){
                            let find_qty_item =(this.details[i].stock-(relatedItem.quantity *combine.qty))/Item.ProductVariantQty;
                            this.details[i].quantity ++;
                            if(this.details[i].quantity  >= parseInt(find_qty_item )){
                            this.makeToast("warning", this.$t("LowStock"), this.$t("Warning"));
                            this.details[i].quantity =  parseInt(find_qty_item );
        
                            }
                            
                            
                        }
                      });
         
            }

           
          
          }
     
        }
      }
      this.$forceUpdate();
      this.Calcul_Total();
    },

    //-----------------------------------decrement QTY ------------------------------\\

      decrement(detail, id) {
      for (var i = 0; i < this.details.length; i++) {
        if (this.details[i].detail_id == id) {
          if (detail.quantity - 1 > 0) {
            if (detail.etat == "new" && detail.quantity - 1 > detail.stock) {
              this.makeToast(
                "warning",
                this.$t("LowStock"),
                this.$t("Warning")
              );
            } else if (
              detail.etat == "stock" &&
              detail.quantity - 1 > detail.stock + detail.qte_copy
            ) {
              this.makeToast(
                "warning",
                this.$t("LowStock"),
                this.$t("Warning")
              );
            } else {
              this.formatNumber(this.details[i].quantity--, 2);
            }
          }
        }
      }
      this.$forceUpdate();
      this.Calcul_Total();
    },


    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

    //-----------------------------------------Calcul Total ------------------------------\\
    Calcul_Total() {
      this.total = 0;
      this.sub_total = 0;

      // Calculate subtotal for all items
      for (let i = 0; i < this.details.length; i++) {
        const tax = this.details[i].taxe * this.details[i].quantity;
        this.details[i].subtotal = parseFloat(
          this.details[i].quantity * this.details[i].Net_price + tax
        );
        this.total += this.details[i].subtotal;
        this.sub_total += this.details[i].subtotal;
      }

      this.sub_t = this.sub_total;

      const total_without_discount = parseFloat(this.total - this.sale.discount);

      if (this.sale.sale_type == 1) {
        // Sale Type = 1: Tax Invoice (10% tax)
        this.sale.tax_rate = 10;
        this.sale.TaxNet = parseFloat(
          (total_without_discount * this.sale.tax_rate) / 100
        );
        this.sub_after_plt = parseFloat(
          total_without_discount * (this.plt_rates / 100)
        );
        this.GrandTotal = parseFloat(
          total_without_discount +
          this.sale.TaxNet +
          this.sale.shipping +
          this.sub_after_plt
        );
      } else if (this.sale.sale_type == 2) {
        // Sale Type = 2: Commercial Invoice (add tax back in GrandTotal)
        this.sale.tax_rate = 0; // Ensure no tax rate is applied in other calculations
        this.sale.TaxNet = 0; // Keep TaxNet as 0 for sale_type = 2

        // Step 1: Remove embedded tax from the total
        const net_price_excluding_tax = parseFloat(this.total / 1.1);

        // Step 2: Apply the discount to the net price
        const discounted_net_price = net_price_excluding_tax - this.sale.discount;

        // Step 3: Calculate tax to add back in GrandTotal (10% of discounted net price)
        const tax_to_add_back = parseFloat((discounted_net_price * 10) / 100);

        // Step 4: Recalculate PLT based on discounted net price
        this.sub_after_plt = parseFloat(
          discounted_net_price * (this.plt_rates / 100)
        );

        // Step 5: Calculate GrandTotal
        this.GrandTotal = parseFloat(
          discounted_net_price +
          tax_to_add_back +
          this.sale.shipping +
          this.sub_after_plt
        );
      } else {
        // Default: No special sale type handling
        this.sale.TaxNet = 0;
        this.sub_after_plt = 0;
        this.GrandTotal = total_without_discount + this.sale.shipping;
      }

      // Round GrandTotal
      this.GrandTotal = parseFloat(this.GrandTotal.toFixed(2));

      // Update payment amount if status is paid
      if (this.payment.status === "paid") {
        this.payment.amount = this.formatNumber(this.GrandTotal, 2);
      }

    },

    //-----------------------------------Delete Detail Product ------------------------------\\
    delete_Product_Detail(id) {
      for (var i = 0; i < this.details.length; i++) {
        if (id === this.details[i].detail_id) {
          this.details.splice(i, 1);
          this.Calcul_Total();
        }
      }
    },

    delete_Service_Detail(id) {
      for (var i = 0; i < this.service_details.length; i++) {
        if (id === this.service_details[i].id) {
          this.GrandTotal -= this.service_details[i].price;
          this.service_price -= this.service_details[i].price;
          this.service_details.splice(i, 1);
        }
      }
    },

    //-----------------------------------verified Order List ------------------------------\\

    verifiedForm() {
      if (this.details.length <= 0 && this.service_details.length <= 0 ) {
        this.makeToast(
          "warning",
          this.$t("AddProductToList"),
          this.$t("Warning")
        );
        return false;
      } else {
        var count = 0;
        for (var i = 0; i < this.details.length; i++) {
          if (
            this.details[i].quantity == "" ||
            this.details[i].quantity === 0
          ) {
            count += 1;
          }
        }

        if (count > 0) {
          this.makeToast("warning", this.$t("AddQuantity"), this.$t("Warning"));

          return false;
        } else {
          return true;
        }
      }
    },

    //---------- keyup OrderTax
    keyup_OrderTax() {
      if (isNaN(this.sale.tax_rate)) {
        this.sale.tax_rate = 0;
      } else {
        this.Calcul_Total();
      }
    },

    keyup_PLTTax() {
      if (isNaN(this.sale.plt)) {
        this.sale.plt = 0;
      } else {
        this.Calcul_Total();
      }
    },

    //---------- keyup Discount

    keyup_Discount() {
      if (isNaN(this.sale.discount)) {
        this.sale.discount = 0;
      } else {
        this.Calcul_Total();
      }
    },

    //---------- keyup Shipping

    keyup_Shipping() {
      if (isNaN(this.sale.shipping)) {
        this.sale.shipping = 0;
      } else {
        this.Calcul_Total();
      }
    },

    async processPayment() {
      this.paymentProcessing = true;
      const { token, error } = await this.stripe.createToken(
        this.cardElement
      );
      if (error) {
        this.paymentProcessing = false;
        NProgress.done();
        this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
      } else {
        axios
          .post("sales", {
            date: this.sale.date,
            client_id: this.sale.client_id,
            warehouse_id: this.sale.warehouse_id,
            statut: this.sale.statut,
            notes: this.sale.notes,
            tax_rate: this.sale.tax_rate,
            plt_rate: this.sale.plt_rate,
            TaxNet: this.sale.TaxNet,
            payment_term_id : this.sale.payment_term_id,
            sale_type: this.sale.sale_type,
            discount: this.sale.discount,
            shipping: this.sale.shipping,
            GrandTotal: this.GrandTotal,
            details: this.details,
            service_details: this.service_details,
            payment: this.payment,
            amount: parseFloat(this.payment.amount).toFixed(2),
            received_amount: parseFloat(this.payment.received_amount).toFixed(2),
            change: parseFloat(this.payment.received_amount - this.payment.amount).toFixed(2),
            token: token.id
          })
          .then(response => {
            // console.log(response.data);
           this.paymentProcessing = false;
           this.makeToast(
             "success",
             this.$t("Create.TitleSale"),
             this.$t("Success")
           );
           NProgress.done();
           this.$router.push({ name: "index_sales" });
          })
          .catch(error => {
            this.paymentProcessing = false;
            NProgress.done();
            this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          });
      }
    },
    //--------------------------------- Create Sale -------------------------\\
    Create_Sale() {
      if (this.verifiedForm()) {
        // Start the progress bar.
        NProgress.start();
        NProgress.set(0.1);
         if(this.payment.Reglement  == 'credit card'){
          if(this.stripe_key != ''){
            this.processPayment();
          }else{
            this.makeToast("danger", this.$t("credit_card_account_not_available"), this.$t("Failed"));
            NProgress.done();
          }
        }else{
          this.paymentProcessing = true;
          axios
            .post("sales", {
              date: this.sale.date,
              client_id: this.sale.client_id,
              pod: this.sale.pod,
              pon: this.sale.pon,
              warehouse_id: this.sale.warehouse_id,
              payment_term_id: this.sale.payment_term_id,
              statut: this.sale.statut,
              notes: this.sale.notes,
              tax_rate: this.sale.tax_rate,
              plt: this.sale.plt,
              TaxNet: this.sale.TaxNet,
              discount: this.sale.discount,
              sale_type: this.sale.sale_type,
              shipping: this.sale.shipping,
              GrandTotal: this.GrandTotal,
              details: this.details,
              service_details: this.service_details,
              payment: this.payment,
              amount: parseFloat(this.payment.amount).toFixed(2),
              received_amount: parseFloat(this.payment.received_amount).toFixed(2),
              change: parseFloat(this.payment.received_amount - this.payment.amount).toFixed(2),
            })
            .then(response => {
              this.makeToast(
                "success",
                this.$t("Create.TitleSale"),
                this.$t("Success")
              );
              NProgress.done();
              this.paymentProcessing = false;
              this.$router.push({ name: "index_sales" });
              // console.log(response.data);
             
            })
            .catch(error => {
              NProgress.done();
              this.paymentProcessing = false;
              this.makeToast(
                "danger",
                this.$t("InvalidData"),
                this.$t("Failed")
              );
            });
        }
      }
    },

    //-------------------------------- Get Last Detail Id -------------------------\\
    Last_Detail_id() {
      this.product.detail_id = 0;
      var len = this.details.length;
      this.product.detail_id = this.details[len - 1].detail_id + 1;
    },

    //---------------------------------Get Product Details ------------------------\\

    Get_Product_Details(product_id) {
      axios.get("Products/" + product_id).then(response => {
        this.product.discount = 0;
        this.product.DiscountNet = 0;
        this.product.discount_Method = "2";
        this.product.product_id = response.data.id;
        this.product.name = response.data.name;
        this.product.kh_name = response.data.kh_name;
        var Net_price = response.data.Net_price;
        this.product.Unit_price = response.data.Unit_price;
        this.product.taxe = response.data.tax_price;
        this.product.tax_method = response.data.tax_method;
        this.product.tax_percent = response.data.tax_percent;
        this.product.unitSale = response.data.unitSale;
        this.product.fix_price = response.data.fix_price;
        this.product.sale_unit_id = response.data.sale_unit_id;
        this.product.is_stock = response.data.is_stock;
        this.product.is_combine = response.data.is_combine;
        let qty = 0;
        if(this.product.is_combine == 1){
          axios.get("Products/Products_combine_qty/" + this.product.product_id+"/"+qty)
            .then(response => {
                  this.product.product_qty =response.data;
                   
            })
          .catch
        }
        if(this.product.product_variant_id){
            axios.get("ProductVarians/"+this.product.product_variant_id).then(response => {
                this.product.Net_price = response.data.price ? response.data.price : Net_price;
                 this.product.ProductVariantQty = response.data.qty;
                   this.product.Variant = response.data.name;
                 this.product.product_qty = this.product.stock / response.data.qty;
                this.add_product();
                this.Calcul_Total();
          })
        }else{
              this.product.Net_price = Net_price;
                this.product.ProductVariantQty = 1;
                this.product.Variant = response.data.unitSale;
                this.product.product_qty = this.product.stock;
                this.add_product();
                this.Calcul_Total();
                
        }
       
      });
    },



    //---------------------------------------Get Elements ------------------------------\\
    GetElements() {
      axios
        .get("sales/create")
        .then(response => {
          this.clients = response.data.clients;
          this.warehouses = response.data.warehouses;
          this.stripe_key = response.data.stripe_key;
          this.payment_terms = response.data.payment_terms;
          this.plt_rates = response.data.plt_rates;

         // console.log(this.plt_rates);
        
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },
      GetCombine() {
       axios
        .get("Products/Getcombine")
        .then(response => {
          this.combines = response.data;
     
        })
        .catch(response => {
          this.isLoading = false;
        });
    },
  },
  

  //----------------------------- Created function-------------------
  created() {
    this.GetElements();
    this.GetCombine();
  }
};
</script>

<style>
.scrolling-wrapper{
	overflow-x: auto;
	padding-top: 10px;
	padding-bottom: 10px;	
}
.scroll{
  	padding-right: 10px;
    padding-left: 10px;
}

.card-block{
	height: 100px;
  width: 100px;
	background-color: #fff;
	border: none;
	background: linear-gradient(to left bottom, rgba(#ff4057, 0.8), rgb(51, 51, 102) 90%), url('https://source.unsplash.com/1024x1024?mountains');
	background-position: center;
	background-size: cover;
	box-shadow: 0 20px 50px hsla(240%, 43%, 40%, 0.35);
	transition: all 0.2s ease-in-out !important;
	&:hover{
		transform: translateY(-5px);
		box-shadow: 0 30px 70px hsla(240%, 43%, 40%, 0.5);
	}
}

 &:hover {
    background: #ececec;
  }
  
  .title {
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #54759a;
    font-size: .85em;
    margin-bottom: 20px;
  }
   .search-wrapper {
    position: relative;
    label {
      position: absolute;
      font-size: 12px;
      color: rgba(0,0,0,.50);
      top: 8px;
      left: 12px;
      z-index: -1;
      transition: .15s all ease-in-out;
    }
    input {
      padding: 4px 12px;
      color: rgba(0,0,0,.70);
      border: 1px solid rgba(0,0,0,.12);
      transition: .15s all ease-in-out;
      background: white;
      &:focus {
        outline: none;
        transform: scale(1.05);
        & + label  {
          font-size: 10px;
          transform: translateY(-24px) translateX(-12px);
        }
      }
      &::-webkit-input-placeholder {
          font-size: 12px;
          color: rgba(0,0,0,.50);
          font-weight: 100;
      }
    }
  }

  .wrapper {
    display: flex;
    max-width: 444px;
    flex-wrap: wrap;
    padding-top: 12px;
  }



  .tabset > input[type="radio"] {
  position: absolute;
  left: -200vw;
}

.tabset .tab-panel {
  display: none;
}

.tabset > input:first-child:checked ~ .tab-panels > .tab-panel:first-child,
.tabset > input:nth-child(3):checked ~ .tab-panels > .tab-panel:nth-child(2),
.tabset > input:nth-child(5):checked ~ .tab-panels > .tab-panel:nth-child(3),
.tabset > input:nth-child(7):checked ~ .tab-panels > .tab-panel:nth-child(4),
.tabset > input:nth-child(9):checked ~ .tab-panels > .tab-panel:nth-child(5),
.tabset > input:nth-child(11):checked ~ .tab-panels > .tab-panel:nth-child(6) {
  display: block;
}

/*
 Styling
*/
body {
  font: 16px/1.5em "Overpass", "Open Sans", Helvetica, sans-serif;
  color: #333;
  font-weight: 300;
}

.tabset > label {
  position: relative;
  display: inline-block;
  padding: 15px 15px 25px;
  border: 1px solid transparent;
  border-bottom: 0;
  cursor: pointer;
  font-weight: 600;
}

.tabset > label::after {
  content: "";
  position: absolute;
  left: 15px;
  bottom: 10px;
  width: 22px;
  height: 4px;
  background: #8d8d8d;
}

input:focus-visible + label {
  outline: 2px solid rgba(0,102,204,1);
  border-radius: 3px;
}

.tabset > label:hover,
.tabset > input:focus + label,
.tabset > input:checked + label {
  color: #06c;
}

.tabset > label:hover::after,
.tabset > input:focus + label::after,
.tabset > input:checked + label::after {
  background: #06c;
}

.tabset > input:checked + label {
  border-color: #ccc;
  border-bottom: 1px solid #fff;
  margin-bottom: -1px;
}

.tab-panel {
  padding: 30px 0;
  border-top: 1px solid #ccc;
}

/*
 Demo purposes only
*/
*,
*:before,
*:after {
  box-sizing: border-box;
}


.tabset {
  max-width: 100%;
}
  

</style>