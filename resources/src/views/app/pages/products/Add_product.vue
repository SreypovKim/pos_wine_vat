<template>
  <div class="main-content">
    <breadcumb :page="$t('AddProduct')" :folder="$t('Products')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <validation-observer ref="Create_Product" v-if="!isLoading">
      <b-form @submit.prevent="Submit_Product" enctype="multipart/form-data">
        <b-row>
          <b-col md="8">
            <b-card>
              <b-row>
                <!-- Name -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Name"
                    :rules="{required:true , min:3 , max:255}"
                    v-slot="validationContext">
                    <b-form-group :label="$t('Name_product')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="Name-feedback"
                        label="Name"
                        :placeholder="$t('Enter_Name_Product')"
                        v-model="product.name"
                      ></b-form-input>
                      <b-form-invalid-feedback id="Name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                
                <!-- Code Product"-->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Code Product"
                    :rules="{ required: true}"
                  >
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('CodeProduct')">
                      <div class="input-group">
                        <b-form-input
                          :class="{'is-invalid': !!errors.length}"
                          :state="errors[0] ? false : (valid ? true : null)"
                          aria-describedby="CodeProduct-feedback"
                          type="text"
                          v-model="product.code"
                        ></b-form-input>
                        <div class="input-group-append">
                          <span class="input-group-text">
                            <a @click="generateNumber()">
                              <i class="i-Bar-Code"></i>
                            </a>
                          </span>
                        </div>
                        <b-form-invalid-feedback id="CodeProduct-feedback">{{ errors[0] }}</b-form-invalid-feedback>
                      </div>
                        <span>{{$t('Scan_your_barcode_and_select_the_correct_symbology_below')}}</span>
                        <b-alert
                          show
                          variant="danger"
                          class="error mt-1"
                          v-if="code_exist !=''"
                        >{{code_exist}}</b-alert>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Category -->
                <b-col md="4" class="mb-2">
                  <validation-provider name="category" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Categorie')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Category')"
                        v-model="product.category_id"
                        @input="ChooseCategory"
                        :options="categories.map(categories => ({label: categories.name, value: categories.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>
                   <!-- Sub Category -->
                <b-col md="4" class="mb-2">
                  <validation-provider name="subcategory" :rules="{ required: false}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('SubCategorie')">
                      <v-select
                        :reduce="label => label.value" 
                        :placeholder="$t('Choose_Sub_Category')"   
                        v-model="product.subcategory_id"                   
                       :options="subcategories ? subcategories.map(subcategory => ({ label: subcategory.name, value: subcategory.id })) : [{ label: 'Other', value: 0 }]"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                  <!-- Is Stock -->
                <b-col md="4" class="mb-2">
                  <validation-provider name="is_stock" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('Is Stock')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose Stock')"
                        v-model="product.is_stock"
                        :options="stocks.map(stocks => ({label: stocks.name, value: stocks.value}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Product volume -->
                <b-col md="4" class="mb-2">
                  <validation-provider
                    name="Product Cost"
                    :rules="{ required: false }"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('Volume (ml)')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="ProductCost-feedback"
                        label="volume"
                        :placeholder="$t('volume')"
                        v-model="product.volume"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="ProductCost-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>


                <!-- Brand  -->
                <b-col md="4" class="mb-2">
                  <b-form-group :label="$t('Brand')">
                    <v-select
                      :placeholder="$t('Choose_Brand')"
                      :reduce="label => label.value"
                      v-model="product.brand_id"
                      :options="brands.map(brands => ({label: brands.name, value: brands.id}))"
                    />
                  </b-form-group>
                </b-col>

                <!-- Barcode Symbology  -->
                <b-col md="4" class="mb-2">
                  <validation-provider name="Barcode Symbology" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('BarcodeSymbology')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.Type_barcode"
                        :reduce="label => label.value"
                        :placeholder="$t('Choose_Symbology')"
                        :options="
                            [
                              {label: 'Code 128', value: 'CODE128'},
                              {label: 'Code 39', value: 'CODE39'},
                              {label: 'EAN8', value: 'EAN8'},
                              {label: 'EAN13', value: 'EAN13'},
                              {label: 'UPC', value: 'UPC'},
                            ]"
                      ></v-select>
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Product Cost -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Product Cost"
                    :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('PurchasePrice')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="ProductCost-feedback"
                        label="Cost"
                        :placeholder="$t('Enter_Product_Cost')"
                        v-model="product.cost"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="ProductCost-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Product Price -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Product Price"
                    :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('SellPrice')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="ProductPrice-feedback"
                        label="Price"
                        :placeholder="$t('Enter_Product_Price')"
                        v-model="product.price"
                      ></b-form-input>

                      <b-form-invalid-feedback
                        id="ProductPrice-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Unit Product -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Unit Product" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitProduct')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.unit_id"
                        class="required"
                        required
                        @input="Selected_Unit"
                      
                        :reduce="label => label.value"
                        :options="units.map(units => ({label: units.name, value: units.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Unit Sale -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Unit Sale" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitSale')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.unit_sale_id"
                       
                        :reduce="label => label.value"
                        :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Unit Purchase -->
                <b-col md="6" class="mb-2">
                  <validation-provider name="Unit Purchase" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('UnitPurchase')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.unit_purchase_id"
                       
                        :reduce="label => label.value"
                        :options="units_sub.map(units_sub => ({label: units_sub.name, value: units_sub.id}))"
                      />
                      <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Stock Alert -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Stock Alert"
                    :rules="{ regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('StockAlert')">
                      <b-form-input
                        :state="getValidationState(validationContext)"
                        aria-describedby="StockAlert-feedback"
                        label="Stock alert"
                        :placeholder="$t('Enter_Stock_alert')"
                        v-model="product.stock_alert"
                      ></b-form-input>
                      <b-form-invalid-feedback
                        id="StockAlert-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Order Tax -->
                <b-col md="6" class="mb-2">
                  <validation-provider
                    name="Order Tax"
                    :rules="{regex: /^\d*\.?\d*$/}"
                    v-slot="validationContext"
                  >
                    <b-form-group :label="$t('OrderTax')">
                      <div class="input-group">
                        <input
                          :state="getValidationState(validationContext)"
                          aria-describedby="OrderTax-feedback"
                          v-model="product.TaxNet"
                          type="number"
                          class="form-control"
                        >
                        <div class="input-group-append">
                          <span class="input-group-text">%</span>
                        </div>
                      </div>
                      <b-form-invalid-feedback
                        id="OrderTax-feedback"
                      >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                    </b-form-group>
                  </validation-provider>
                </b-col>

                <!-- Tax Method -->
                <b-col lg="6" md="6" sm="12" class="mb-2">
                  <validation-provider name="Tax Method" :rules="{ required: true}">
                    <b-form-group slot-scope="{ valid, errors }" :label="$t('TaxMethod')">
                      <v-select
                        :class="{'is-invalid': !!errors.length}"
                        :state="errors[0] ? false : (valid ? true : null)"
                        v-model="product.tax_method"
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

                <b-col md="12" class="mb-2">
                  <b-form-group :label="$t('Note')">
                    <textarea
                      rows="4"
                      class="form-control"
                      :placeholder="$t('Afewwords')"
                      v-model="product.note"
                    ></textarea>
                  </b-form-group>
                </b-col>
              </b-row>
            </b-card>
          </b-col>
          <b-col md="4">
            <!-- upload-multiple-image -->
            <b-card>
              <div class="card-header">
                <h5>{{$t('MultipleImage')}}</h5>
              </div>
              <div class="card-body">
                <b-row class="form-group">
                  <b-col md="12 mb-5">
                    <div
                      id="my-strictly-unique-vue-upload-multiple-image"
                      class="d-flex justify-content-center"
                    >
                      <vue-upload-multiple-image
                      @upload-success="uploadImageSuccess"
                      @before-remove="beforeRemove"
                      dragText="Drag & Drop Multiple images For product"
                      dropText="Drag & Drop image"
                      browseText="(or) Select"
                      accept=image/gif,image/jpeg,image/png,image/bmp,image/jpg
                      primaryText='success'
                      markIsPrimaryText='success'
                      popupText='have been successfully uploaded'
                      :data-images="images"
                      idUpload="myIdUpload"
                      :showEdit="false"
                      />
                    </div>
                  </b-col>
                  <!-- Multiple Variants -->
                 <b-col md="12 mb-2">
                    <ValidationProvider rules vid="product" v-slot="x">
                      <div class="form-check">
                        <label class="checkbox checkbox-outline-primary">
                          <input type="checkbox" v-model="product.is_variant">
                          <span>{{$t('ProductHasMultiVariants')}}</span>
                          <span class="checkmark"></span>
                        </label>
                      </div>
                    </ValidationProvider>
                  </b-col>
                  <!-- <b-col md="12 mb-5" v-show="product.is_variant">
                    <vue-tags-input
                      placeholder="+ add"
                      v-model="tag"
                      :tags="variants"
                      class="tag-custom text-15"
                      @adding-duplicate="showNotifDuplicate()"
                      @tags-changed="newTags => variants = newTags"
                    />
                  </b-col> -->
                    <table class="table table-bordered"  v-show="product.is_variant">
                            <thead class="text text-success">
                              <tr>                            
                                  <th>Name</th>
                                  <th>QTY</th>
                                  <th>Price</th>
                                  <th>
                                    <b-button size="sm" variant="primary" @click="addRow">Add</b-button>
                                  </th>
                              </tr>
                              </thead>
                              <tbody>
                                <tr  v-for='variant, index in variants'>  
                                  <td>
                                  <input class="form-control"  v-model="variants[index].name" type="text" />
                                  </td>
                                  <td>
                                  <input class="form-control"  v-model="variants[index].qty" type="text" />
                                  </td>
                                  <td>
                                  <input class="form-control"  v-model="variants[index].price" type="text" />
                                  </td>
                                  <td>
                                     <b-button size="sm" variant="danger" @click="deleteRow(index)">Remove</b-button>
                                  </td> 
                              </tr>                        
                      </tbody>
                  </table>

                  <!-- Combine Product -->
                   <b-col md="12 mb-2">
                    <ValidationProvider rules vid="product" v-slot="x">
                      <div class="form-check">
                        <label class="checkbox checkbox-outline-primary">
                          <input type="checkbox" v-model="product.is_combine">
                          <span>{{$t('Combine Product')}}</span>
                          <span class="checkmark"></span>
                        </label>
                      </div>
                    </ValidationProvider>
                  </b-col>
                  <!-- <b-col md="12 mb-5" v-show="product.is_variant">
                    <vue-tags-input
                      placeholder="+ add"
                      v-model="tag"
                      :tags="variants"
                      class="tag-custom text-15"
                      @adding-duplicate="showNotifDuplicate()"
                      @tags-changed="newTags => variants = newTags"
                    />
                  </b-col> -->
                    <table class="table table-bordered"  v-show="product.is_combine">
                            <thead class="text text-success">
                              <tr>                            
                                  <th style="width: 200px;" >Name</th>
                                  <th>QTY</th>
                                
                                  <th>
                                    <b-button size="sm" variant="primary" @click="combineAddRow">Add</b-button>
                                  </th>
                              </tr>
                              </thead>
                              <tbody>
                                <tr  v-for='combine, index in combines'>  
                                  <td>
                                    <v-select
                                      :placeholder="$t('Choose_Brand')"
                                      :reduce="label => label.value"
                                      v-model="combines[index].product_combine_id"
                                      :options="products.map(products => ({label: products.name, value: products.id}))"
                                      @input="changedValue(combines[index].product_id,index)" 
                                    />
                                   
                                  </td>
                                  <td>
                                  <input class="form-control"  v-model="combines[index].qty" type="text" />
                                  </td>
                                
                                  <td>
                                     <b-button size="sm" variant="danger" @click="combineDeleteRow(index)">Remove</b-button>
                                  </td> 
                              </tr>                        
                      </tbody>
                  </table>
                </b-row>
              </div>
            </b-card>
          </b-col>
          <b-col md="12" class="mt-3">
             <b-button variant="primary" type="submit" :disabled="SubmitProcessing">{{$t('submit')}}</b-button>
              <div v-once class="typo__p" v-if="SubmitProcessing">
                <div class="spinner sm spinner-primary mt-3"></div>
              </div>
          </b-col>
        </b-row>
      </b-form>
    </validation-observer>
  </div>
</template>


<script>
import VueUploadMultipleImage from "vue-upload-multiple-image";
import VueTagsInput from "@johmun/vue-tags-input";
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Create Product"
  },
  data() {
    return {

      tag: "",
      len: 8,
      images: [],
      imageArray: [],
      change: false,
      isLoading: true,
      SubmitProcessing:false,
      data: new FormData(),
      categories: [],
      subcategories: [],
      units: [],
      units_sub: [],
      brands: [],
      roles: {},
      // variants: [],
      variants:[],
      products:[],
      combines:[],
      stocks: [{'name': 'Stock', 'value' : 1},{'name': 'No Stock', value: 0}],
      product: {
        name: "",
        code: "",
        Type_barcode: "CODE128",
        cost: "",
        price: "",
        brand_id: 1,
        category_id: "",
        subcategory_id: "",
        TaxNet: "0",
        tax_method: "1",
        unit_id: 1,
        unit_sale_id: 1,
        unit_purchase_id: 1,
        stock_alert: "1",
        image: "",
        note: "",
        is_variant: false,
        is_combine: false,
        is_stock:"",
        volume:""
      },
      code_exist: "",
      
    };
  },
  mounted() {
      this.units_sub = [];
      this.product.unit_sale_id = 1;
      this.product.unit_purchase_id = 1;
      this.Get_Units_SubBase(1);
      this.variants.splice(0);
      this.combines.splice(0);    
  },

  components: {
    VueUploadMultipleImage,
    VueTagsInput
  },

  methods: {
    addRow: function () {
      this.variants.push({ value: 'def' });
    },
    deleteRow: function (index) {
      this.variants.splice(index, 1);
    },

    combineAddRow: function () {
      this.combines.push({ value: 'def' });
    },
    combineDeleteRow: function (index) {
      this.combines.splice(index, 1);
    },

    changedValue: function(id,index) {
        // alert([id,index]);
        axios.get(
          "GetProduct/"+id 
        )
        .then(response => {
           console.log(response.data);
          // Complete the animation of theprogress bar.
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    ChooseCategory(id) {
       axios
        .get(
          "subcategories/"+id 
        )
        .then(response => {
            this.product.subcategory_id ="";
            
            this.subcategories = response.data.sub_categories;
        
          // Complete the animation of theprogress bar.
          NProgress.done();
          this.isLoading = false;
        })
        .catch(response => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },
    //------------- Submit Validation Create Product
    Submit_Product() {
      this.$refs.Create_Product.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          this.Create_Product();
        }
      });
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //------ Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------Show Notification If Variant is Duplicate
    showNotifDuplicate() {
      this.makeToast(
        "warning",
        this.$t("VariantDuplicate"),
        this.$t("Warning")
      );
    },

    //------ Event upload Image Success
    uploadImageSuccess(formData, index, fileList, imageArray) {
      this.images = fileList;
    },

    //------ Event before Remove Image
    beforeRemove(index, done, fileList) {
      var remove = confirm("remove image");
      if (remove == true) {
        this.images = fileList;
        done();
      } else {
      }
    },

    //-------------- Product Get Elements
    GetElements() {
      axios
        .get("Products/create")
        .then(response => {
          this.categories = response.data.categories;
          this.brands = response.data.brands;
          this.units = response.data.units;
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },
    GetProducts() {
      axios
        .get("GetProducts")
        .then(response => {
          this.products = response.data;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //---------------------- Get Sub Units with Unit id ------------------------------\\
    Get_Units_SubBase(value) {
      axios
        .get("Get_Units_SubBase?id=" + value)
        .then(({ data }) => (this.units_sub = data));

    },

    //---------------------- Event Select Unit Product ------------------------------\\
    Selected_Unit(value) {
      this.units_sub = [];
      this.product.unit_sale_id = "";
      this.product.unit_purchase_id = "";
      this.Get_Units_SubBase(value);
      console.log(value);
    },

    //------------------------------ Create new Product ------------------------------\\
    Create_Product() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      var self = this;
      self.SubmitProcessing = true;

      if (self.product.is_variant && self.variants.length <= 0) {
        self.product.is_variant = false;
      }
      // append objet product
      Object.entries(self.product).forEach(([key, value]) => {
        self.data.append(key, value);
      });

      // append array variants
      if (self.variants.length) {
        for (var i = 0; i < self.variants.length; i++) {
          self.data.append("variants[" + i + "]", [self.variants[i].name, self.variants[i].qty,self.variants[i].price]);
        }
      }

        // append array combines
       if (self.combines.length) {
         for (var i = 0; i < self.combines.length; i++) {
          self.data.append("combines[" + i + "]", [self.combines[i].product_combine_id, self.combines[i].qty]);
         }
       }
      //append array images
      if (self.images.length > 0) {
        for (var k = 0; k < self.images.length; k++) {
          Object.entries(self.images[k]).forEach(([key, value]) => {
            self.data.append("images[" + k + "][" + key + "]", value);
          });
        }
      }
      axios
        .post("Products", self.data,{
         
        })
        .then(response => {
         
          // Complete the animation of theprogress bar.
          NProgress.done();
          self.SubmitProcessing = false;
          this.$router.push({ name: "index_products" });
          this.makeToast(
            "success",
            this.$t("Successfully_Created"),
            this.$t("Success")
          );
       
        })
        .catch(error => {
          // Complete the animation of theprogress bar.
          NProgress.done();
          if (error.errors.code.length > 0) {
            self.code_exist = error.errors.code[0];
          }
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          self.SubmitProcessing = false;
        });
    }
  }, //end Methods

  //-----------------------------Created function-------------------

  created: function() {
    this.GetElements();
    this.GetProducts();
  }
};
</script>
