<template>
  <div class="main-content">
    <breadcumb :page="$t('Services')" :folder="$t('Settings')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="services"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        enabled: true,
        placeholder: $t('Search_this_table'),  
      }"
        :select-options="{ 
          enabled: true ,
          clearSelectionText: '',
        }"
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
        styleClass="table-hover tableOne vgt-table"
      >
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button @click="New_Service()" class="btn-rounded" variant="btn btn-primary btn-icon m-1">
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Service(props.row)" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover @click="Remove_Service(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
          <div v-else-if="props.column.field == 'BaseUnit'">
            <span v-if="props.row.base_unit_name != ''">{{props.row.base_unit_name}}</span>
            <span v-else>N/D</span>
          </div>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Service">
      <b-modal hide-footer size="md" id="New_Service" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Unit">
          <b-row>
            <!-- Name -->
            <b-col md="12">
              <validation-provider
                name="Code Currency"
                :rules="{ required: true , max:15}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Name')">
                  <b-form-input
                    :placeholder="$t('Enter_Name_Unit')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Name-feedback"
                    label="Name"
                    v-model="service.name"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- ShortName -->
            <b-col md="12">
              <validation-provider
                name="ShortName"
                :rules="{ required: true , max:15}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Price')">
                  <b-form-input
                    :placeholder="$t('Enter_Price_Service')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="ShortName-feedback"
                    label="Price"
                    v-model="service.price"
                  ></b-form-input>
                  <b-form-invalid-feedback id="ShortName-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

             <!-- <b-col md="6">
              <validation-provider
                name="Start Age"
                :rules="{integer}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Start Age')">
                  <b-form-input
                    :placeholder="$t('Enter start age')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="ShortName-feedback"
                    label="Start Age"
                    v-model="service.start_age"
                  ></b-form-input>
                  <b-form-invalid-feedback id="ShortName-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <b-col md="6">
              <validation-provider
                name="End Age"
                :rules="{integer}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('End Age')">
                  <b-form-input
                    :placeholder="$t('Enter End age')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="ShortName-feedback"
                    label="End Age"
                    v-model="service.end_age"
                  ></b-form-input>
                  <b-form-invalid-feedback id="ShortName-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col> -->

            <!-- Base unit -->
            <b-col md="12">
              <b-form-group :label="$t('Status')">
                <v-select
                  @input="Status"
                  v-model="service.status"
                  :reduce="label => label.value"
                  :placeholder="$t('Choose_Base_Status')"
                  :options="status_base.map(status_base => ({label: status_base.name, value:status_base.name}))"
                />
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
import NProgress from "nprogress";

export default {
  metaInfo: {
    title: "Unit"
  },
  data() {
    return {
      isLoading: true,
      SubmitProcessing:false,
      serverParams: {
        columnFilters: {},
        sort: {
          field: "id",
          type: "desc"
        },
        page: 1,
        perPage: 10
      },
      totalRows: "",
      search: "",
      limit: "10",
      services: [],
      status_base: [ { name: "Active"}, { name: "Inactive"},],
      editmode: false,
      show_operator: false,
      service: {
        id: "",
        name: "",
        status: "",
        price:"",
        start_age:"",
        end_age:""

      }
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("Name"),
          field: "name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "status",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Price"),
          field: "price",
          tdClass: "text-left",
          thClass: "text-left"
        },
        // {
        //   label: this.$t("Start age"),
        //   field: "start_age",
        //   tdClass: "text-left",
        //   thClass: "text-left"
        // },
        // {
        //   label: this.$t("End age"),
        //   field: "end_age",
        //   tdClass: "text-left",
        //   thClass: "text-left"
        // },
        {
          label: this.$t("Action"),
          field: "actions",
          html: true,
          tdClass: "text-right",
          thClass: "text-right",
          sortable: false
        }
      ];
    }
  },

  methods: {
    //---- update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Services(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Services(1);
      }
    },

    //---- Event Sort Change
    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Services(this.serverParams.page);
    },

    //---- Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Services(this.serverParams.page);
    },

    //---- Validation State Form
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit Unit
    Submit_Unit() {
      this.$refs.Create_Service.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_Service();
          } else {
            this.Update_Service();
          }
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

    //------------------------------ Modal (create Unit) -------------------------------\\
    New_Service() {
      this.reset_Form();
      this.show_operator = false;
      this.editmode = false;
      this.$bvModal.show("New_Service");
    },

    //------------------------------ Modal (Update Unit) -------------------------------\\
    Edit_Service(service) {
      this.Get_Services(this.serverParams.page);
      this.reset_Form();
      this.service = service;
      this.editmode = true;
      this.$bvModal.show("New_Service");
    },

    Selected_Base_Unit(value) {
      if (value == null) {
        this.show_operator = false;
      } else {
        this.show_operator = true;
      }
    },

    //----------------------------------------  Get All services -------------------------\\
    Get_Services(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "services?page=" +
            page +
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
          this.services = response.data.services;
          this.totalRows = response.data.totalRows;
        
        
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

    //---------------------------------------- Set To Strings-------------------------\\
    setToStrings() {
      // Simply replaces null values with strings=''
      if (this.service.base_service === null) {
        this.service.base_service = "";
      }
    },
    //---------------- Send Request with axios ( Create Unit) --------------------\\
    Create_Service() {
      this.SubmitProcessing = true;
      this.setToStrings();
      axios
        .post("services", {
          name: this.service.name,
          price: this.service.price,
          base_status: this.service.status,
          // start_age: this.service.start_age,
          // end_age: this.service.end_age
        })
        .then(response => {
           this.SubmitProcessing = false;
          Fire.$emit("Event_Service");

          this.makeToast(
            "success",
            this.$t("Create.TitleService"),
            this.$t("Success")
          );
        })
        .catch(error => {
           this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //--------------- Send Request with axios ( Update Unit) --------------------\\
    Update_Service() {
       this.SubmitProcessing = true;
      this.setToStrings();
      axios
        .put("services/" + this.service.id, {
          name: this.service.name,
          price: this.service.price,
          base_status: this.service.status,
          // start_age: this.service.start_age,
          // end_age: this.service.end_age
         
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Service");

          this.makeToast(
            "success",
            this.$t("Update.TitleService"),
            this.$t("Success")
          );
         
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //------------------------------ reset Form ------------------------------\\
    reset_Form() {
      this.service = {
        id: "",
        name: "",
        price: "",
        base_unit: [],
        // start_age: "",
        // end_age: "",
      };
    },

    //--------------------------------- Remove Unit --------------------\\
    Remove_Service(id) {
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
            .delete("services/" + id)
            .then(response => {
              if (response.data.success) {
                this.$swal(
                  this.$t("Delete.Deleted"),
                  this.$t("Delete.UnitDeleted"),
                  "success"
                );
              } else {
                this.$swal(
                  this.$t("Delete.Failed"),
                  this.$t("Unit_already_linked_with_sub_unit"),
                  "warning"
                );
              }
              Fire.$emit("Delete_Unit");
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

  }, //end Method

  //----------------------------- Created function-------------------
  created: function() {
    this.Get_Services(1);

    Fire.$on("Event_Service", () => {
      setTimeout(() => {
        this.Get_Services(this.serverParams.page);
        this.$bvModal.hide("New_Service");
      }, 500);
    });

    Fire.$on("Delete_Unit", () => {
      setTimeout(() => {
        this.Get_Services(this.serverParams.page);
      }, 500);
    });
  }
};
</script>