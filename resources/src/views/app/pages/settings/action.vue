<template>
  <div class="main-content">
    <breadcumb :page="$t('Action')" :folder="$t('Settings')"/>

    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <b-card class="wrapper" v-if="!isLoading">
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="actions"
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
          <b-button @click="New_Action()" class="btn-rounded" variant="btn btn-primary btn-icon m-1">
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a @click="Edit_Action(props.row)" title="Edit" v-b-tooltip.hover>
              <i class="i-Edit text-25 text-success"></i>
            </a>
            <a title="Delete" v-b-tooltip.hover @click="Delete_Action(props.row.id)">
              <i class="i-Close-Window text-25 text-danger"></i>
            </a>
          </span>
          
        </template>
      </vue-good-table>
    </b-card>

    <validation-observer ref="Create_Action">
      <b-modal hide-footer size="md" id="New_Action" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_Action" enctype="multipart/form-data">
          <b-row>
            <!-- Brand Name -->
            <b-col md="12">
              <validation-provider
                name="Brand Name"
                :rules="{ required: true , min:3 , max:20}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('ActionName')">
                  <b-form-input
                    :placeholder="$t('Enter_Name_Action')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Name-feedback"
                    label="Name"
                    v-model="action.name"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Name-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Brand Description -->
            <b-col md="12">
              <validation-provider
                name="Brand Description"
                :rules="{ max:30}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('ActionReason')">
                  <b-form-textarea
                    rows="3"
                    :placeholder="$t('Enter_Reason_Action')"
                    :state="getValidationState(validationContext)"
                    aria-describedby="Description-feedback"
                    label="Description"
                    v-model="action.reason"
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
    title: "Brand"
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
      data: new FormData(),
      editmode: false,
      actions: [],
      limit: "10",
      action: {
        id: "",
        name: "",
        reason: ""
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
          label: this.$t("Reason"),
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
        this.Get_Actions(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Actions(1);
      }
    },

    //---- Event on Sort Change
    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Actions(this.serverParams.page);
    },

    //---- Event Select Rows
    selectionChanged({ selectedRows }) {
      this.selectedIds = [];
      selectedRows.forEach((row, index) => {
        this.selectedIds.push(row.id);
      });
    },

    //---- Event on Search

    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Actions(this.serverParams.page);
    },

    //---- Validation State Form

    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------------- Submit Validation Create & Edit Brand
    Submit_Action() {
      this.$refs.Create_Action.validate().then(success => {
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
            this.Update_Action();
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

    //------------------------------ Event Upload Image -------------------------------\\
    async onFileSelected(e) {
      const { valid } = await this.$refs.Image.validate(e);

      if (valid) {
        this.brand.image = e.target.files[0];
      } else {
        this.brand.image = "";
      }
    },

    //------------------------------ Modal (create Brand) -------------------------------\\
    New_Action() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_Action");
    },

    //------------------------------ Modal (Update Brand) -------------------------------\\
    Edit_Action(action) {
      this.Get_Actions(this.serverParams.page);
      this.reset_Form();
      this.action = action;
      this.editmode = true;
      this.$bvModal.show("New_Action");
    },

    //---------------------------------------- Get All brands-----------------\\
    Get_Actions(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get(
          "actions?page=" +
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
        //   this.brands = response.data.brands;
        //   this.totalRows = response.data.totalRows;
        this.actions = response.data.actions;
        console.log(response.data.actions);

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

    //---------------------------------------- Create new brand-----------------\\
    Create_Action() {
      var self = this;
      self.SubmitProcessing = true;
      self.data.append("name", self.action.name);
      self.data.append("reason", self.action.reason);
      axios
        .post("actions", self.data)
        .then(response => {
          self.SubmitProcessing = false;
          Fire.$emit("Event_Action");

          this.makeToast(
            "success",
            this.$t("Create.TitleAction"),
            this.$t("Success")
          );
        })
        .catch(error => {
           self.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //---------------------------------------- Update Brand-----------------\\
    Update_Action() {
      var self = this;
       self.SubmitProcessing = true;
      self.data.append("name", self.action.name);
      self.data.append("reason", self.action.reason);
      self.data.append("_method", "put");

      axios
        .post("actions/" + self.action.id, self.data)
        .then(response => {
           self.SubmitProcessing = false;
          Fire.$emit("Event_Action");

          this.makeToast(
            "success",
            this.$t("Update.TitleAction"),
            this.$t("Success")
          );
        })
        .catch(error => {
           self.SubmitProcessing = false;
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //---------------------------------------- Reset Form -----------------\\
    reset_Form() {
      this.brand = {
        id: "",
        name: "",
        description: "",
        image: ""
      };
      this.data = new FormData();
    },

    //---------------------------------------- Delete Brand -----------------\\
    Delete_Action(id) {
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
            .delete("actions/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.TitleAction"),
                "success"
              );

              Fire.$emit("Delete_Action");
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

    //---- Delete brands by selection

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
            .post("actions/delete/by_selection", {
              selectedIds: this.selectedIds
            })
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.TitleAction"),
                "success"
              );

              Fire.$emit("Delete_Action");
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
  created: function() {
    this.Get_Actions(1);

    Fire.$on("Event_Action", () => {
      setTimeout(() => {
        this.Get_Actions(this.serverParams.page);
        this.$bvModal.hide("New_Action");
        
      }, 500);
    });

    Fire.$on("Delete_Action", () => {
      setTimeout(() => {
        this.Get_Actions(this.serverParams.page);
      }, 500);
    });
  }
};
</script>