<template>
  <div class="main-content">
    <breadcumb :page="$t('SaleDetail')" :folder="$t('Sales')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>

    <b-card v-if="!isLoading">
      <b-row>
        <b-col md="12" class="mb-5">
          <router-link
            v-if="currentUserPermissions && currentUserPermissions.includes('Sales_edit')"
            title="Edit"
            class="btn btn-success btn-icon ripple btn-sm"
            :to="{ name:'edit_sale', params: { id: $route.params.id } }"
          >
            <i class="i-Edit"></i>
            <span>{{$t('EditSale')}}</span>
          </router-link>
          <!-- <button @click="Sale_Email()" class="btn btn-info btn-icon ripple btn-sm">
            <i class="i-Envelope-2"></i>
            {{$t('Email')}}
          </button>
           <button @click="Sale_SMS()" class="btn btn-secondary btn-icon ripple btn-sm">
            <i class="i-Speach-Bubble"></i>
            SMS
          </button> -->
          <!-- <button @click="Sale_PDF()" class="btn btn-primary btn-icon ripple btn-sm">
            <i class="i-File-TXT"></i>
            PDF 
          </button> -->
          <!-- <button @click="print()" class="btn btn-warning btn-icon ripple btn-sm">
            <i class="i-Billing"></i>
            {{$t('print')}}
          </button> -->
          <button
            v-if="currentUserPermissions && currentUserPermissions.includes('Sales_delete')"
            @click="Delete_Sale()"
            class="btn btn-danger btn-icon ripple btn-sm"
          >
            <i class="i-Close-Window"></i>
            {{$t('Del')}}
          </button>
           <button
           @click="Action()"
            class="btn btn-primary btn-icon ripple btn-sm"
          >
            {{$t('Action')}}
          </button>
        </b-col>
      </b-row>
      <div class="invoice" id="print_Invoice">
        <div class="invoice-print">
          <b-row class="justify-content-md-center">
            <h4 class="font-weight-bold">{{$t('SaleDetail')}} : {{sale.Ref}}</h4>
          </b-row>
          <hr>
          <b-row class="mt-5">
            <b-col lg="3" md="3" sm="12" class="mb-3">
              <h5 class="font-weight-bold">{{$t('Customer_Info')}}</h5>
              <div>{{sale.client_name}}</div>
              <div>{{sale.client_email}}</div>
              <div>{{sale.client_phone}}</div>
              <div>{{sale.client_adr}}</div>
            </b-col>
            <b-col lg="3" md="3" sm="12" class="mb-3">
              <h5 class="font-weight-bold">{{$t('Company_Info')}}</h5>
              <div>{{company.CompanyName}}</div>
              <div>{{company.email}}</div>
              <div>{{company.CompanyPhone}}</div>
              <div>{{company.CompanyAdress}}</div>
            </b-col>
            <b-col lg="3" md="3" sm="12" class="mb-3">
              <h5 class="font-weight-bold">{{$t('Invoice_Info')}}</h5>
              <div>{{$t('Reference')}} : {{sale.Ref}}</div>
              <div>
                {{$t('PaymentStatus')}} :
                <span
                  v-if="sale.payment_status == 'paid'"
                  class="badge badge-outline-success"
                >{{$t('Paid')}}</span>
                <span
                  v-else-if="sale.payment_status == 'partial'"
                  class="badge badge-outline-primary"
                >{{$t('partial')}}</span>
                <span v-else class="badge badge-outline-warning">{{$t('Unpaid')}}</span>
              </div>
              <div>{{$t('warehouse')}} : {{sale.warehouse}}</div>
              <div>
                {{$t('Status')}} :
                <span
                  v-if="sale.statut == 'completed'"
                  class="badge badge-outline-success"
                >{{$t('complete')}}</span>
                <span
                  v-else-if="sale.statut == 'pending'"
                  class="badge badge-outline-info"
                >{{$t('Pending')}}</span>
                <span v-else class="badge badge-outline-warning">{{$t('Ordered')}}</span>
              </div>
            </b-col>
              <b-col lg="3" md="3" sm="12" class="mb-3">
              <h5 class="font-weight-bold">{{$t('ActionInfo')}}</h5>
              <div>{{sale.action_name}}</div>
              <div>{{sale.description}}</div>
             
            </b-col>
          </b-row>
          <b-row class="mt-3">
            <b-col md="12">
             
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
                     
                      <b-col md="12" class="mb-4">
                     
                       <!-- order products  -->
                        <h5 class="font-weight-bold">{{$t('Order_Summary')}}</h5>
                        <div class="table-responsive">
                        <table class="table table-hover table-md">
                          <thead class="bg-gray-300">
                            <tr>
                              <th scope="col">{{$t('ProductName')}}</th>
                              <!-- <th scope="col">{{$t('Net_Unit_Price')}}</th> -->
                              <th scope="col">{{$t('Quantity')}}</th>
                              <th scope="col">Price</th>
                              <th scope="col">{{$t('Discount')}}</th>
                              <th scope="col">{{$t('Tax')}}</th>
                              <th scope="col">{{$t('SubTotal')}}</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-for="detail in details">
                              <td>{{detail.name}}</td>
                              <!-- <td>{{currentUser.currency}}{{formatNumber(detail.Net_price,3)}}</td> -->
                              <td>{{formatNumber(detail.quantity,2)}} {{detail.variant_name}}</td>
                              <td>{{currentUser.currency}}{{formatNumber(detail.price,2)}}</td>
                              <td>{{currentUser.currency}}{{formatNumber(detail.DiscountNet,2)}}</td>
                              <td>{{currentUser.currency}}{{formatNumber(detail.taxe,2)}}</td>
                              <td>{{currentUser.currency}}{{detail.total.toFixed(2)}}</td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      </b-col>

                    </section>
                    <section id="service" class="tab-panel">
                       <!-- search Service  -->
   
                      <b-col md="12" class="mb-5">
                        <div class="table-responsive">
                        <table class="table table-hover">
                          <thead class="bg-gray-300">
                            <tr>
                             
                              <th scope="col">{{$t('Service')}}</th>
                              <th scope="col">{{$t('Description')}}</th>
                              <th scope="col">{{$t('UnitPrice')}}</th>
                              <th scope="col">{{$t('SubTotal')}}</th>
                             
                            </tr>
                          </thead>
                          <tbody>
                            <tr v-if="service_details.length <=0">
                              <td colspan="9">{{$t('NodataAvailable')}}</td>
                            </tr>
                            <tr v-for="service_detail  in service_details">
                              
                              <td>
                          
                                <span>{{service_detail.name}}</span>
                               
                              </td>
                              <td>
                          
                                 <textarea  v-model="service_detail.description"></textarea>
                               
                              </td>
                              <td>{{currentUser.currency}}{{formatNumber(service_detail.price,2)}}</td>
                              
                            
                              <td>{{currentUser.currency}}{{service_detail.price.toFixed(2)}}</td>
                             
                            </tr>
                          </tbody>
                        </table>
                      </div>


                
                      </b-col>
               


                <!-- Product -->
               
                  <!-- Service -->
                   
                    
                      
                    </section>
                   
                  </div>
                  
                </div>

              </b-col>
            <div class="offset-md-9 col-md-3 mt-4">
              <table class="table table-striped table-sm">
                <tbody>
                  
                  <tr>
                    <td>{{$t('SubTotal')}}</td>
                    <td>
                      <span>{{currentUser.currency}} {{formatNumber(sub_total,2)}}</span>
                    </td>
                  </tr>
                  <tr>
                    <td>{{$t('Discount')}}</td>
                    <td>{{currentUser.currency}}{{sale.discount.toFixed(2)}}</td>
                  </tr>
                  <tr>
                    <td>{{$t('SaleTax')}}</td>
                    <td>
                      <span>{{currentUser.currency}}{{sale.TaxNet.toFixed(2)}}({{formatNumber(sale.tax_rate,2)}} %)</span>
                    </td>
                  </tr>
                  <tr>
                    <td>{{$t('PLT')}}</td>
                    <td>
                      <span>{{currentUser.currency}}{{formatNumber(sale.plt,2)}} %</span>
                    </td>
                  </tr>
                  <tr>
                    <td>{{$t('Shipping')}}</td>
                    <td>{{currentUser.currency}}{{sale.shipping.toFixed(2)}}</td>
                  </tr>
                  <tr>
                    <td>
                      <span class="font-weight-bold">{{$t('Total')}}</span>
                    </td>
                    <td>
                      <span
                        class="font-weight-bold"
                      >{{currentUser.currency}}{{sale.GrandTotal}}</span>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <span class="font-weight-bold">{{$t('Paid')}}</span>
                    </td>
                    <td>
                      <span
                        class="font-weight-bold"
                      >{{currentUser.currency}}{{sale.paid_amount}}</span>
                    </td>
                  </tr>
                  <tr>
                    <td>
                      <span class="font-weight-bold">{{$t('Due')}}</span>
                    </td>
                    <td>
                      <span
                        class="font-weight-bold"
                      >{{currentUser.currency}}{{sale.due}}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </b-row>
        </div>
      </div>
    </b-card>


     <validation-observer ref="Create_Category">
      <b-modal hide-footer size="md" id="New_Category" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Action">
          <b-row>
             <!-- Action  -->
              <b-col md="12">
              <b-form-group :label="$t('Action')">
                <v-select
                  :reduce="label => label.value"
                  :placeholder="$t('Choose_Action')"
                  v-model="sale_action.action_id"
                  :options="actions.map(actions => ({label: actions.name, value: actions.id}))"
                />
              </b-form-group>
            </b-col>

              <!-- Action Description -->
            <b-col md="12">
        
                <b-form-group :label="$t('ActionReason')">
                  <b-form-textarea
                    rows="3"
                    :placeholder="$t('Enter_Reason_Action')"
                    aria-describedby="Description-feedback"
                    label="Description"
                    v-model="sale_action.description"
                  ></b-form-textarea>
                </b-form-group>
              
            </b-col>


             <b-col md="12" class="mt-3">
                <b-button variant="primary" type="submit"  :disabled="SubmitProcessing">{{$t('submit')}}</b-button>
                  <div v-once class="typo__p" v-if="SubmitProcessing">
                    <div class="spinner sm spinner-primary mt-3"></div>
                  </div>
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

export default {
  computed: mapGetters(["currentUserPermissions", "currentUser"]),
  metaInfo: {
    title: "Detail Sale"
  },

  data() {
    return {
      isLoading: true,
      sale: {},
      details: [],
      variants: [],
      company: {},
      actions:[],
      sub_total: "",
      service_details:[],
      email: {
        to: "",
        subject: "",
        message: "",
        client_name: "",
        Sale_Ref: ""
      },
      sale_action:{
        action_id:'',
        description:''
      },
      serverParams: {
        columnFilters: {},
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
    };
  },

  methods: {
    //------------------------------ Print -------------------------\\
    print() {
      var divContents = document.getElementById("print_Invoice").innerHTML;
      var a = window.open("", "", "height=500, width=500");
      a.document.write(
        '<link rel="stylesheet" href="/assets_setup/css/bootstrap.css"><html>'
      );
      a.document.write("<body >");
      a.document.write(divContents);
      a.document.write("</body></html>");
      a.document.close();
      a.print();
    },

    //----------------------------------- Invoice Sale PDF  -------------------------\\
    Sale_PDF() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      let id = this.$route.params.id;
     
       axios
        .get(`Sale_PDF/${id}`, {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", "Sale_" + this.sale.Ref + ".pdf");
          document.body.appendChild(link);
          link.click();
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
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

    //------------------------------Formetted Numbers -------------------------\\
    formatNumber(number, dec) {
      const value = (typeof number === "string"
        ? number
        : number.toLocaleString()
      ).split(".");
      if (dec <= 0) return value[0];
      let formated = value[1] || "";
      if (formated.length > dec)
        return `${value[0]}.${formated.substr(0, dec)}`;
      while (formated.length < dec) formated += "0";
      return `${value[0]}.${formated}`;
    },

    //---- SU

     Submit_Action() {
      this.$refs.Create_Category.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Action();
          } else {
            alert(2);
            // this.Update_Category();
          }
        }
      });
    },

     Create_Action() {
      this.SubmitProcessing = true;
      axios
        .post("sales/action/" + this.sale.id, {
          action_id: this.sale_action.action_id,
          description: this.sale_action.description,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Category");
          this.makeToast(
            "success",
            this.$t("Update.TitleAddAction"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //--------------------------------- Send Sale in Email ------------------------------\\
    Sale_Email() {
      this.email.to = this.sale.client_email;
      this.email.Sale_Ref = this.sale.Ref;
      this.email.client_name = this.sale.client_name;
      this.Send_Email();
    },

    Send_Email() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      let id = this.$route.params.id;
      axios
        .post("sales/send/email", {
          id: id,
          to: this.email.to,
          client_name: this.email.client_name,
          Ref: this.email.Sale_Ref
        })
        .then(response => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast(
            "success",
            this.$t("Send.TitleEmail"),
            this.$t("Success")
          );
        })
        .catch(error => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast("danger", this.$t("SMTPIncorrect"), this.$t("Failed"));
        });
    },
      //---------Action 
    Action(){
        this.editmode = false;
        this.$bvModal.show("New_Category");
    },
    //---------SMS notification
     Sale_SMS() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      let id = this.$route.params.id;
      axios
        .post("sales/send/sms", {
          id: id,
        })
        .then(response => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast(
            "success",
            this.$t("Send_SMS"),
            this.$t("Success")
          );
        })
        .catch(error => {
          // Complete the animation of the  progress bar.
          setTimeout(() => NProgress.done(), 500);
          this.makeToast("danger", this.$t("sms_config_invalid"), this.$t("Failed"));
        });
    },

    //----------------------------------- Get Details Sale ------------------------------\\
    Get_Details() {
      let id = this.$route.params.id;
      axios
        .get(`sales/${id}`)
        .then(response => {
          this.sale = response.data.sale;
          this.details = response.data.details;
          this.company = response.data.company;
          this.service_details = response.data.service_details;
          this.sub_total = response.data.sub_total;
          this.isLoading = false;

          console.log(this.sale.sale_type);
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },

    //------------------------------------------ DELETE Sale ------------------------------\\
    Delete_Sale() {
      let id = this.$route.params.id;
      this.$swal({
        title: this.$t("Delete.Title"),
        text: this.$t("Delete.Text"),
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        cancelButtonText: this.$t("Delete.cancelButtonText"),
        confirmButtonText: this.$t("Delete.confirmButtonText")
      }).then(result => {
        if (result.value) {
          axios
            .delete("sales/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.SaleDeleted"),
                "success"
              );
              this.$router.push({ name: "index_sales" });
            })
            .catch(() => {
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    },
     //---------------Get Action ----------------------
    Get_Actions() {
      axios
        .get(
         "actions?page=" +
            this.serverParams.page +
            "&SortField=" +
            this.serverParams.sort.field +
            "&SortType=" +
            this.serverParams.sort.type +
            "&search=" +
            this.search +
            "&limit=" +
            this.limit
        )
        .then(response => {
          this.actions = response.data.getActions;
      
          this.isLoading = false;
        })
        .catch(response => {
          setTimeout(() => {
            this.isLoading = false;
          }, 500);
        });
    },
  }, //end Methods
 
  //----------------------------- Created function-------------------

  created: function() {
    this.Get_Details();
    this.Get_Actions();

      Fire.$on("Event_Category", () => {
      setTimeout(() => {
        this.Get_Details()
        this.$bvModal.hide("New_Category");
      }, 500);
    });
  }
};

</script>

<style>
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