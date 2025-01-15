<template>
  <div class="main-content">
    <breadcumb :page="$t('PaymentTerms')" :folder="$t('Settings')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="payment_terms"
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
        @on-selected-rows-change="selectionChanged"
        :pagination-options="{
        enabled: true,
        mode: 'records',
        nextLabel: 'next',
        prevLabel: 'prev',
      }"
        styleClass="table-hover tableOne vgt-table"
      >
        <div slot="selected-row-actions">
          <button class="btn btn-danger btn-sm" @click="delete_by_selected()">{{$t('Del')}}</button>
        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button
            @click="New_PaymentTerm()"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_PaymentTerms(props.row)" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover @click="Remove_PaymentTerm(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Category">
      <b-modal hide-footer size="md" id="New_Payment_Term" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_PaymentTerm">
          <b-row>
          
            <!-- Name category -->
            <b-col md="12">
              <validation-provider
                name="Name Payment Term"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('PaymentTermName')">
                  <b-form-input
                    :placeholder="$t('Enter_name_payment_term')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Name-feedback"
                    label="Name"
                    v-model="payment_term.name"
                   
                  ></b-form-input>
                  <b-form-invalid-feedback id="Name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
              <!-- Code category -->
            <b-col md="12">
              <validation-provider
                name="Due Date"
                :rules="{ required: true , regex: /^\d*\.?\d*$/}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('PaymentTermDueDay')">
                  <b-form-input
                    :placeholder="$t('Enter_due_date')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Code-feedback"
                    label="due_date"
                    v-model="payment_term.due_day"
                  
                  ></b-form-input>
                  <b-form-invalid-feedback id="Code-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

           <b-col md="12">
              <validation-provider
                name="Brand Description"
                :rules="{ max:30}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('PaymentTermReason')">
                  <b-form-textarea
                    rows="3"
                    :placeholder="$t('Enter_Description_payment_term')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Description-feedback"
                    label="Description"
                     v-model="payment_term.reason"
                   
                  ></b-form-textarea>
                  <b-form-invalid-feedback
                    id="Description-feedback"
                  >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
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
    title: "Category"
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
      selectedIds: [],
      totalRows: "",
      search: "",
      limit: "10",
      categories: [],
      payment_terms: [],
      editmode: false,

      payment_term: {
        id: "",
        name: "",
        due_day: "",
        reason:""
      },

    };
  },
  computed: {
    columns() {
      return [
        {
          label: this.$t("PaymentTermName"),
          field: "name",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("PaymentTermDueDay"),
          field: "due_day",
          tdClass: "text-left",
          thClass: "text-left"
        },
         {
          label: this.$t("PaymentTermReason"),
          field: "reason",
          tdClass: "text-left",
          thClass: "text-left"
        },
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
        this.Get_PaymentTerms(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_PaymentTerms(1);
      }
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

    //---- Event on Sort Change
    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_PaymentTerms(this.serverParams.page);
    },

    //---- Event on Search

    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_PaymentTerms(this.serverParams.page);
    },

    //---- Validation State Form

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit Category
    Submit_PaymentTerm() {
      this.$refs.Create_Category.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_PaymentTerm();
          } else {
            this.Update__PaymentTerm();
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

    //------------------------------ Modal  (create category) -------------------------------\\
    New_PaymentTerm() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_Payment_Term");
    },

    //------------------------------ Modal (Update category) -------------------------------\\
    Edit_PaymentTerms(payment_term) {
      this.Get_PaymentTerms(this.serverParams.page);
      this.reset_Form();
      this.payment_term = payment_term;
      this.editmode = true;
      this.$bvModal.show("New_Payment_Term");
    },

    //--------------------------Get ALL Categories & Sub category ---------------------------\\

    Get_PaymentTerms(page) {
    
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "paymentterms?page=" +
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
        this.payment_terms = response.data.payment_terms;
        console.log(this.payment_terms);
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

    //----------------------------------Create new Category ----------------\\
    Create_PaymentTerm() {
      this.SubmitProcessing = true;
      axios
        .post("paymentterms", {
          name: this.payment_term.name,
          due_day: this.payment_term.due_day,
          reason: this.payment_term.reason,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Payment_Term");
          this.makeToast(
            "success",
            this.$t("Create.TitlePaymentTerm"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //---------------------------------- Update Category ----------------\\
    Update__PaymentTerm() {
      this.SubmitProcessing = true;
      axios
        .put("paymentterms/" +  this.payment_term.id, {
          name: this.payment_term.name,
          due_day: this.payment_term.due_day,
          reason: this.payment_term.reason
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_Payment_Term");
          this.makeToast(
            "success",
            this.$t("Update.TitlePaymentTerm"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //--------------------------- reset Form ----------------\\

    reset_Form() {
      this.payment_term = {
        id: "",
        name: "",
        due_day: "",
        reason:""
      };
    },

    //--------------------------- Remove Category----------------\\
    Remove_PaymentTerm(id) {
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
            .delete("paymentterms/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.CatDeleted"),
                "success"
              );

              Fire.$emit("Delete_Payment_Term");
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

    //---- Delete category by selection

    delete_by_selected() {
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
          // Start the progress bar.
          NProgress.start();
          NProgress.set(0.1);
          axios
            .post("paymentterms/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.TitlePaymentTerm"),
                "success"
              );

              Fire.$emit("Delete_Payment_Term");
            })
            .catch(() => {
              // Complete the animation of theprogress bar.
              setTimeout(() => NProgress.done(), 500);
              this.$swal(
                this.$t("Delete.Failed"),
                this.$t("Delete.Therewassomethingwronge"),
                "warning"
              );
            });
        }
      });
    }
  }, //end Methods

  //----------------------------- Created function-------------------

  created: function() {
    this.Get_PaymentTerms(1);

    Fire.$on("Event_Payment_Term", () => {
      setTimeout(() => {
        this.Get_PaymentTerms(this.serverParams.page);
        this.$bvModal.hide("New_Payment_Term");
      }, 500);
    });

    Fire.$on("Delete_Payment_Term", () => {
      setTimeout(() => {
        this.Get_PaymentTerms(this.serverParams.page);
      }, 500);
    });
  }
};
</script>