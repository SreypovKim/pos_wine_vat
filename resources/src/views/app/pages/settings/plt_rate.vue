<template>
  <div class="main-content">
    <breadcumb :page="$t('PLT')" :folder="$t('Settings')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :search-options="{
            enabled: true,
            placeholder:'search table'
          }"
        :totalRows="totalRows"
        :rows="plt_rates"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
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
        styleClass="table-hover tableOne vgt-table">
        <div slot="selected-row-actions">
          <button class="btn btn-danger btn-sm" @click="delete_by_selected()">{{$t('Del')}}</button>
        </div>
        <div slot="table-actions" class="mt-2 mb-3">
          <b-button
            @click="New_PLT_Rate"
            class="btn-rounded"
            variant="btn btn-primary btn-icon m-1">
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>
        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_PLT_Rate(props.row)" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover @click="Remove_PLT_Rate(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
        </template>
      </vue-good-table>
    </b-card>
    <validation-observer ref="Create_PLT_Rate">
      <b-modal hide-footer size="md" id="New_PLT_Rate" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_PLT_Rate">
          <b-row>
            <!-- PLT Date -->
            <b-col md="12">
              <validation-provider
                name="PLT Date"
                :rules="{ required: true}"
                v-slot="validationContext">
                <b-form-group :label="$t('Date')">
                  <b-form-input
                    :placeholder="$t('Enter_Date')"
                    :state="getValidationState(validationContext)"
                     aria-describedby="date-feedback"
                    type="date"
                    v-model="plt_rate.date"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Code-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- PLT Rate -->
            <b-col md="12">
              <validation-provider
                name="PLT Rate"
                :rules="{ required: true}"
                v-slot="validationContext">
                <b-form-group :label="$t('PLTRate')">
                  <b-form-input
                    :placeholder="$t('Enter_PLT_Rate')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="rate-feedback"
                    label="PLT_Rate"
                    v-model="plt_rate.plt_rate"
                  ></b-form-input>
                  <b-form-invalid-feedback id="rate-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Description -->
            <b-col md="12">
              <validation-provider
                name="Description"
                v-slot="validationContext">
                <b-form-group :label="$t('Description')">
                  <b-form-input
                    :placeholder="$t('Enter_Description')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="rate-feedback"
                    label="description"
                    v-model="plt_rate.description"
                  ></b-form-input>
                  <b-form-invalid-feedback id="rate-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
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
    title: "PLT_Rate"
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
      editmode: false,
      plt_rate: {
        id: "",
        date: new Date().toISOString().slice(0, 10),
        plt_rate: "",
        description: "",
      }
    };
  },

  computed: {
    columns() {
      return [
        {
          label: this.$t("Date"),
          field: "date",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("PLTRate"),
          field: "plt_rate",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Description"),
          field: "description",
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
        this.Get_PLT_Rate(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_PLT_Rate(1);
      }
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_PLT_Rate(this.serverParams.page);
    },

    //---- Event on Search

    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_PLT_Rate(this.serverParams.page);
    },

    //---- Validation State Form

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit Exchange_Rate
    Submit_PLT_Rate() {
      this.$refs.Create_PLT_Rate.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
          if (!this.editmode) {
            this.Create_PLT_Rate();
          } else {
            this.Update_PLT_Rate();
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

    //------------------------------ Modal (create Exchange_Rate) -------------------------------\\
    New_PLT_Rate() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_PLT_Rate");
    },

    //------------------------------ Modal (Update Exchange_Rate) -------------------------------\\
    Edit_PLT_Rate(plt_rate) {
      this.Get_PLT_Rate(this.serverParams.page);
      this.reset_Form();
      this.plt_rate = plt_rate;
      this.editmode = true;
      this.$bvModal.show("New_PLT_Rate");
    },

    //--------------------------Get ALL Exchange_Rate ---------------------------\\

    Get_PLT_Rate(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "plt_rate?page=" +
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
          this.plt_rates = response.data.plt_rates;
          this.totalRows = response.data.totalRows;
          console.log(new Date().toISOString().slice(0, 10));
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

    //----------------------------------Create new currency ----------------\\
    Create_PLT_Rate() {
      this.SubmitProcessing = true;
      
      if (!this.plt_rate.date) {
        this.plt_rate.date = new Date().toISOString().slice(0, 10); // Set default date if missing
      }

      axios
        .post("plt_rate", {
          date: this.plt_rate.date,
          plt_rate: this.plt_rate.plt_rate,
          description: this.plt_rate.description,
        }) 
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_PLT_Rate");

          this.makeToast(
            "success",
            this.$t("Create.TitlePLTRate"),
            this.$t("Success")
          );
        })
        .catch(error => {
          this.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //---------------------------------- Update Exchange Rate ----------------\\
    Update_PLT_Rate() {
      this.SubmitProcessing = true;
      axios
        .put("plt_rate/" + this.plt_rate.id, {
          date: this.plt_rate.date,
          plt_rate: this.plt_rate.plt_rate,
          description: this.plt_rate.description,
        })
        .then(response => {
          this.SubmitProcessing = false;
          Fire.$emit("Event_PLT_Rate");

          this.makeToast(
            "success",
            this.$t("Update.TitlePLTRate"),
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
      this.plt_rate = {
        id: "",
        date: "",
        plt_rate: "",
        description: "",
      };
    },

    //--------------------------- Remove Currency----------------\\
    Remove_PLT_Rate(id) {
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
            .delete("plt_rate/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.TitlePLTRate"),
                "success"
              );

              Fire.$emit("Delete_PLT_Rate");
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

    //---- Delete currency by selection

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
            .post("plt_rate/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.TitlePLTRate"),
                "success"
              );

              Fire.$emit("Delete_PLT_Rate");
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
    this.Get_PLT_Rate(1);

    Fire.$on("Event_PLT_Rate", () => {
      setTimeout(() => {
        this.Get_PLT_Rate(this.serverParams.page);
        this.$bvModal.hide("New_PLT_Rate");
      }, 500);
    });

    Fire.$on("Delete_PLT_Rate", () => {
      setTimeout(() => {
        this.Get_PLT_Rate(this.serverParams.page);
      }, 500);
    });
  }
};
</script>