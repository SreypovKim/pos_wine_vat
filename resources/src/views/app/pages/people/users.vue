<template>
  <div class="main-content">
    <breadcumb :page="$t('UserManagement')" :folder="$t('Users')"/>
    <div v-if="isLoading" class="loading_page spinner spinner-primary mr-3"></div>
    <div v-else>
      <vue-good-table
        mode="remote"
        :columns="columns"
        :totalRows="totalRows"
        :rows="users"
        @on-page-change="onPageChange"
        @on-per-page-change="onPerPageChange"
        @on-sort-change="onSortChange"
        @on-search="onSearch"
        :search-options="{
        enabled: true,
        placeholder: $t('Search_this_table'),  
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
          <b-button variant="outline-info m-1" size="sm" v-b-toggle.sidebar-right>
            <i class="i-Filter-2"></i>
            {{ $t("Filter") }}
          </b-button>
          <b-button @click="Users_PDF()" size="sm" variant="outline-success m-1">
            <i class="i-File-Copy"></i> PDF
          </b-button>
          <b-button @click="Users_Excel()" size="sm" variant="outline-danger m-1">
            <i class="i-File-Excel"></i> EXCEL
          </b-button>
          <b-button
            @click="New_User()"
            size="sm"
            variant="btn btn-primary btn-icon m-1"
            v-if="currentUserPermissions && currentUserPermissions.includes('users_add')"
          >
            <i class="i-Add"></i>
            {{$t('Add')}}
          </b-button>
        </div>

        <template slot="table-row" slot-scope="props">
          <span v-if="props.column.field == 'actions'">
            <a
              @click="Edit_User(props.row)"
              v-if="currentUserPermissions && currentUserPermissions.includes('users_edit')"
              title="Edit"
              v-b-tooltip.hover
            >
              <i class="i-Edit text-25 text-success"></i>
            </a>
          </span>

          <div v-else-if="props.column.field == 'statut'">
            <label class="switch switch-primary mr-3">
              <input @change="isChecked(props.row)" type="checkbox" v-model="props.row.statut">
              <span class="slider"></span>
            </label>
          </div>
        </template>
      </vue-good-table>
    </div>

    <!-- Multiple Filters  -->
    <b-sidebar id="sidebar-right" :title="$t('Filter')" bg-variant="white" right shadow>
      <div class="px-3 py-2">
        <b-row>
          <!-- Name user  -->
          <b-col md="12">
            <b-form-group :label="$t('username')">
              <b-form-input label="Code" :placeholder="$t('username')" v-model="Filter_Name"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- User Phone -->
          <b-col md="12">
            <b-form-group :label="$t('Phone')">
              <b-form-input label="Phone" :placeholder="$t('SearchByPhone')" v-model="Filter_Phone"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- User Email  -->
          <b-col md="12">
            <b-form-group :label="$t('Email')">
              <b-form-input label="Email" :placeholder="$t('SearchByEmail')" v-model="Filter_Email"></b-form-input>
            </b-form-group>
          </b-col>

          <!-- Status  -->
          <b-col md="12">
            <b-form-group :label="$t('Status')">
              <v-select
                v-model="Filter_status"
                :reduce="label => label.value"
                :placeholder="$t('Choose_Status')"
                :options="
                        [
                           {label: 'Actif', value: '1'},
                           {label: 'Inactif', value: '0'}
                        ]"
              ></v-select>
            </b-form-group>
          </b-col>

          <b-col md="6" sm="12">
            <b-button @click="Get_Users(serverParams.page)" variant="primary m-1" size="sm" block>
              <i class="i-Filter-2"></i>
              {{ $t("Filter") }}
            </b-button>
          </b-col>
          <b-col md="6" sm="12">
            <b-button @click="Reset_Filter()" variant="danger m-1" size="sm" block>
              <i class="i-Power-2"></i>
              {{ $t("Reset") }}
            </b-button>
          </b-col>
        </b-row>
      </div>
    </b-sidebar>

    <!-- Add & Edit User -->
    <validation-observer ref="Create_User">
      <b-modal hide-footer size="lg" id="New_User" :title="editmode?$t('Edit'):$t('Add')">
        <b-form @submit.prevent="Submit_User" enctype="multipart/form-data">
          <b-row>
            <!-- First name -->
            <b-col md="6" sm="12">
              <validation-provider
                name="Firstname"
                :rules="{ required: true , min:3 , max:30}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Firstname')">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="Firstname-feedback"
                    label="Firstname"
                    v-model="user.firstname"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Firstname-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Last name -->
            <b-col md="6" sm="12">
              <validation-provider
                name="lastname"
                :rules="{ required: true , min:3 , max:30}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('lastname')">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="lastname-feedback"
                    label="lastname"
                    v-model="user.lastname"
                  ></b-form-input>
                  <b-form-invalid-feedback id="lastname-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Username -->
            <b-col md="6" sm="12">
              <validation-provider
                name="username"
                :rules="{ required: true , min:3 , max:30}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('username')">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="username-feedback"
                    label="username"
                    v-model="user.username"
                  ></b-form-input>
                  <b-form-invalid-feedback id="username-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Phone -->
            <b-col md="6" sm="12">
              <validation-provider
                name="Phone"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Phone')">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="Phone-feedback"
                    label="Phone"
                    v-model="user.phone"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Phone-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- Email -->
            <b-col md="6" sm="12">
              <validation-provider
                name="Email"
                :rules="{ required: true}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Email')">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="Email-feedback"
                    label="Email"
                    v-model="user.email"
                  ></b-form-input>
                  <b-form-invalid-feedback id="Email-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                  <b-alert
                    show
                    variant="danger"
                    class="error mt-1"
                    v-if="email_exist !=''"
                  >{{email_exist}}</b-alert>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- password -->
            <b-col md="6" sm="12" v-if="!editmode">
              <validation-provider
                name="password"
                :rules="{ required: true , min:6 , max:14}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('password')">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="password-feedback"
                    label="password"
                    type="password"
                    v-model="user.password"
                  ></b-form-input>
                  <b-form-invalid-feedback id="password-feedback">{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- role -->
            <b-col md="6" sm="12">
              <validation-provider name="role" :rules="{ required: true}">
                <b-form-group slot-scope="{ valid, errors }" :label="$t('RoleName')">
                  <v-select
                    :class="{'is-invalid': !!errors.length}"
                    :state="errors[0] ? false : (valid ? true : null)"
                    v-model="user.role_id"
                    :reduce="label => label.value"
                    :placeholder="$t('PleaseSelect')"
                    :options="roles.map(roles => ({label: roles.name, value: roles.id}))"
                    :change="changeRole(user.role_id)"
                  />
                  <b-form-invalid-feedback>{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>
            <!-- Avatar -->
            <b-col md="6" sm="12">
              <validation-provider name="Avatar" ref="Avatar" rules="mimes:image/*|size:200">
                <b-form-group slot-scope="{validate, valid, errors }" :label="$t('UserImage')">
                  <input
                    :state="errors[0] ? false : (valid ? true : null)"
                    :class="{'is-invalid': !!errors.length}"
                    @change="onFileSelected"
                    label="Choose Avatar"
                    type="file"
                  >
                  <b-form-invalid-feedback id="Avatar-feedback">{{ errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <!-- New Password -->
            <b-col md="6" v-if="editmode">
              <validation-provider
                name="New password"
                :rules="{min:6 , max:14}"
                v-slot="validationContext"
              >
                <b-form-group :label="$t('Newpassword')">
                  <b-form-input
                    :state="getValidationState(validationContext)"
                    aria-describedby="Nawpassword-feedback"
                    :placeholder="$t('LeaveBlank')"
                    label="New password"
                    v-model="user.NewPassword"
                  ></b-form-input>
                  <b-form-invalid-feedback
                    id="Nawpassword-feedback"
                  >{{ validationContext.errors[0] }}</b-form-invalid-feedback>
                </b-form-group>
              </validation-provider>
            </b-col>

            <b-col md="12"  v-show="check_role">
              <div>
                <label>Work Day</label>
              </div>
              <div>
                <div class="row">
                  <div class="col-md-2">
                    <div><label for="jack">Sunday</label></div>
                    <input type="checkbox" v-model="schedule.sun" @click="checkSunday(e)"> 
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("Start time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.sun_start_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                        
                      />
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("End time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.sun_end_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                </div>
                 <div class="row">
                  <div class="col-md-2">
                    <div><label for="jack">Monday</label></div>
                    <input type="checkbox" v-model="schedule.mon"  @click="checkMonday(e)"> 
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("Start time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.mon_start_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("End time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.mon_end_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                </div>
                 <div class="row">
                  <div class="col-md-2">
                    <div><label for="jack">Tuesday</label></div>
                    <input type="checkbox" v-model="schedule.tue" @click="checkTuesday(e)">
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("Start time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.tue_start_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("End time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.tue_end_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-2">
                    <div><label for="jack">Wednesday</label></div>
                    <input type="checkbox" v-model="schedule.wed" @click="checkWednesday(e)">
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("Start time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.wed_start_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("End time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.wed_end_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                </div> 
                 <div class="row">
                  <div class="col-md-2">
                    <div> <label for="jack">Thursday</label></div>
                    <input type="checkbox" v-model="schedule.thu" @click="checkThursday(e)">
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("Start time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.thu_start_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("End time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.thu_end_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                </div> 
                <div class="row">
                  <div class="col-md-2">
                    <div><label for="jack">Friday</label></div>
                    <input type="checkbox" v-model="schedule.fri" @click="checkFriday(e)"> 
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("Start time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.fri_start_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("End time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.fri_end_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                </div>
                 <div class="row">
                  <div class="col-md-2">
                     <div><label for="jack">Saturday</label></div>
                    <input type="checkbox" v-model="schedule.sat" @click="checkSaturday(e)"> 
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("Start time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.sat_start_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                  <div class="col-md-5">
                      <label><strong>{{ $t("End time") }}</strong></label>
                      <b-form-timepicker 
                        v-model="schedule.sat_end_time"
                        no-flip
                        no-close-button
                        offset="0"
                        inutes-step="15"
                        class="mb-3"
                      />
                  </div>
                </div>                        
              </div>
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
import jsPDF from "jspdf";
import "jspdf-autotable";

export default {
  metaInfo: {
    title: "Users"
  },
  data() {
    return {
      editmode: false,
      isLoading: true,
      SubmitProcessing:false,
      email_exist:"",
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
      Filter_Name: "",
      Filter_Email: "",
      Filter_status: "",
      Filter_Phone: "",
      permissions: {},
      users: [],
      roles: [],
      check_role: false,
      data: new FormData(),
      user: {
        firstname: "",
        lastname: "",
        username: "",
        password: "",
        NewPassword: null,
        email: "",
        phone: "",
        statut: "",
        role_id: "",
        avatar: "", 
        
      },
      schedule: {
        sun: 0,
        sun_start_time: "",
        sun_end_time: "",
        mon: 0,
        mon_start_time: "",
        mon_end_time: "",
        tue: 0,
        tue_start_time: "",
        tue_end_time: "",
        wed: 0,
        wed_start_time: "",
        wed_end_time: "",
        thu: 0,
        thu_start_time: "",
        thu_end_time: "",
        fri: 0,
        fri_start_time: "",
        fri_end_time: "",
        sat: 0,
        sat_start_time: "",
        sat_end_time: "",
        
      }
    };
  },

  computed: {
    ...mapGetters(["currentUserPermissions"]),
    columns() {
      return [
        {
          label: this.$t("Firstname"),
          field: "firstname",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("lastname"),
          field: "lastname",
          tdClass: "text-left",
          thClass: "text-left"
        },

        {
          label: this.$t("username"),
          field: "username",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Email"),
          field: "email",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Phone"),
          field: "phone",
          tdClass: "text-left",
          thClass: "text-left"
        },
        {
          label: this.$t("Status"),
          field: "statut",
          html: true,
          sortable: false,
          tdClass: "text-center",
          thClass: "text-center"
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
    changeRole(id) {
      let existing =this.roles.find(role => role.id == id);
      if(existing){
        if(existing.name == "Doctor" || existing.name == "doctor" ){
         this.check_role = true;
        }else{
          this.check_role = false;
        }
      }
    },
    checkSunday: function(e){
     this.schedule.sun_start_time = 'undefined';
     this.schedule.sun_end_time = 'undefined';
    },
    checkMonday: function(e){
     this.schedule.mon_start_time = 'undefined';
     this.schedule.mon_end_time = 'undefined';
    },
    checkTuesday: function(e){
     this.schedule.tue_start_time = 'undefined';
     this.schedule.tue_end_time = 'undefined';
    },
    checkWednesday: function(e){
     this.schedule.wed_start_time = 'undefined';
     this.schedule.wed_end_time = 'undefined';
    },
    checkThursday: function(e){
     this.schedule.thu_start_time = 'undefined';
     this.schedule.thu_end_time = 'undefined';
    },
    checkFriday: function(e){
     this.schedule.fri_start_time = 'undefined';
     this.schedule.fri_end_time = 'undefined';
    },
    checkSaturday: function(e){
     this.schedule.sat_start_time = 'undefined';
     this.schedule.sat_end_time = 'undefined';
    },
    //------------- Submit Validation Create & Edit User
    Submit_User() {
      this.$refs.Create_User.validate().then(success => {
        if (!success) {
          this.makeToast(
            "danger",
            this.$t("Please_fill_the_form_correctly"),
            this.$t("Failed")
          );
        } else {
         
          if (!this.editmode) {
            this.Create_User();
          } else {
            this.Update_User();
          }
        }
      });
    },

    //------ update Params Table
    updateParams(newProps) {
      this.serverParams = Object.assign({}, this.serverParams, newProps);
    },

    //---- Event Page Change
    onPageChange({ currentPage }) {
      if (this.serverParams.page !== currentPage) {
        this.updateParams({ page: currentPage });
        this.Get_Users(currentPage);
      }
    },

    //---- Event Per Page Change
    onPerPageChange({ currentPerPage }) {
      if (this.limit !== currentPerPage) {
        this.limit = currentPerPage;
        this.updateParams({ page: 1, perPage: currentPerPage });
        this.Get_Users(1);
      }
    },

    //------ Event Sort Change
    onSortChange(params) {
      this.updateParams({
        sort: {
          type: params[0].type,
          field: params[0].field
        }
      });
      this.Get_Users(this.serverParams.page);
    },

    //------ Event Search
    onSearch(value) {
      this.search = value.searchTerm;
      this.Get_Users(this.serverParams.page);
    },

    //------ Event Validation State
    getValidationState({ dirty, validated, valid = null }) {
      return dirty || validated ? valid : null;
    },

    //------ Reset Filter
    Reset_Filter() {
      this.search = "";
      this.Filter_Name = "";
      this.Filter_status = "";
      this.Filter_Phone = "";
      this.Filter_Email = "";
      this.Get_Users(this.serverParams.page);
    },

    //------ Toast
    makeToast(variant, msg, title) {
      this.$root.$bvToast.toast(msg, {
        title: title,
        variant: variant,
        solid: true
      });
    },

    //------ Checked Status User
    isChecked(user) {
      axios
        .put("users/Activated/" + user.id, {
          statut: user.statut,
          id: user.id
        })
        .then(response => {
          if (response.data.success) {
            if (user.statut) {
              user.statut = 1;
              this.makeToast(
                "success",
                this.$t("ActivateUser"),
                this.$t("Success")
              );
            } else {
              user.statut = 0;
              this.makeToast(
                "success",
                this.$t("DisActivateUser"),
                this.$t("Success")
              );
            }
          } else {
            user.statut = 1;
            this.makeToast(
              "warning",
              this.$t("Delete.Therewassomethingwronge"),
              this.$t("Warning")
            );
          }
        })
        .catch(error => {
          user.statut = 1;
          this.makeToast(
            "warning",
            this.$t("Delete.Therewassomethingwronge"),
            this.$t("Warning")
          );
        });
    },

    //--------------------------- Users PDF ---------------------------\\
    Users_PDF() {
      var self = this;

      let pdf = new jsPDF("p", "pt");
      let columns = [
        { title: "First Name", dataKey: "firstname" },
        { title: "Last Name", dataKey: "lastname" },
        { title: "Username", dataKey: "username" },
        { title: "Email", dataKey: "email" },
        { title: "Phone", dataKey: "phone" }
      ];
      pdf.autoTable(columns, self.users);
      pdf.text("User List", 40, 25);
      pdf.save("User_List.pdf");
    },

    //------------------------ Users Excel ---------------------------\\
    Users_Excel() {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      axios
        .get("users/export/Excel", {
          responseType: "blob", // important
          headers: {
            "Content-Type": "application/json"
          }
        })
        .then(response => {
          const url = window.URL.createObjectURL(new Blob([response.data]));
          const link = document.createElement("a");
          link.href = url;
          link.setAttribute("download", "List_Users.xlsx");
          document.body.appendChild(link);
          link.click();
          // Complete the animation of theprogress bar.
          setTimeout(() => NProgress.done(), 500);
        })
        .catch(() => {
          // Complete the animation of theprogress bar.
          setTimeout(() => NProgress.done(), 500);
        });
    },

    // Simply replaces null values with strings=''
    setToStrings() {
      if (this.Filter_status === null) {
        this.Filter_status = "";
      }
    },

    //----------------------------------- Get All Users  ---------------------------\\
    Get_Users(page) {
      // Start the progress bar.
      NProgress.start();
      NProgress.set(0.1);
      this.setToStrings();
      axios
        .get(
          "users?page=" +
            page +
            "&name=" +
            this.Filter_Name +
            "&statut=" +
            this.Filter_status +
            "&phone=" +
            this.Filter_Phone +
            "&email=" +
            this.Filter_Email +
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
          this.users = response.data.users;
          if(this.users.schedule){
            console.log(1);
          }else{
            console.log(2);
          }
          this.roles = response.data.roles;
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

    //------------------------------ Show Modal (Create User) -------------------------------\\
    New_User() {
      this.reset_Form();
      this.editmode = false;
      this.$bvModal.show("New_User");
      this.check_role = false;
     
    },

    //------------------------------ Show Modal (Update User) -------------------------------\\
    Edit_User(user) {
      this.Get_Users(this.serverParams.page);
      this.reset_Form();
      this.user = user;
      if(this.user.schedule){
         this.schedule = this.user.schedule;
      }
      this.user.NewPassword = null;
      this.editmode = true;
      this.check_role = false;
      this.$bvModal.show("New_User");
     
    },

    //------------------------------ Event Upload Avatar -------------------------------\\
    async onFileSelected(e) {
      const { valid } = await this.$refs.Avatar.validate(e);

      if (valid) {
        this.user.avatar = e.target.files[0];
      } else {
        this.user.avatar = "";
      }
    },

    //------------------------ Create User ---------------------------\\
    Create_User() {
      var self = this;
      self.SubmitProcessing = true;
      self.data.append("firstname", self.user.firstname);
      self.data.append("lastname", self.user.lastname);
      self.data.append("username", self.user.username);
      self.data.append("email", self.user.email);
      self.data.append("password", self.user.password);
      self.data.append("phone", self.user.phone);
      self.data.append("role", self.user.role_id);
      self.data.append("avatar", self.user.avatar);

      self.data.append("sun", self.schedule.sun);
      self.data.append("sun_start_time", self.schedule.sun_start_time);
      self.data.append("sun_end_time", self.schedule.sun_end_time);
      self.data.append("mon", self.schedule.mon);
      self.data.append("mon_start_time", self.schedule.mon_start_time);
      self.data.append("mon_end_time", self.schedule.mon_end_time);
      self.data.append("tue", self.schedule.tue);
      self.data.append("tue_start_time", self.schedule.tue_start_time);
      self.data.append("tue_end_time", self.schedule.tue_end_time);
      self.data.append("wed", self.schedule.wed);
      self.data.append("wed_start_time", self.schedule.wed_start_time);
      self.data.append("wed_end_time", self.schedule.wed_end_time);
      self.data.append("thu", self.schedule.thu);
      self.data.append("thu_start_time", self.schedule.thu_start_time);
      self.data.append("thu_end_time", self.schedule.thu_end_time);
      self.data.append("fri", self.schedule.fri);
      self.data.append("fri_start_time", self.schedule.fri_start_time);
      self.data.append("fri_end_time", self.schedule.fri_end_time);
      self.data.append("sat", self.schedule.sat);
      self.data.append("sat_start_time", self.schedule.sat_start_time);
      self.data.append("sat_end_time", self.schedule.sat_end_time);
     
      axios
        .post("users", self.data)
        .then(response => {
          self.SubmitProcessing = false;
          Fire.$emit("Event_User");

          this.makeToast(
            "success",
            this.$t("Create.TitleUser"),
            this.$t("Success")
          );
         
        })
        .catch(error => {
          self.SubmitProcessing = false;
          if (error.errors.email.length > 0) {
            self.email_exist = error.errors.email[0];
          }
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
        });
    },

    //----------------------- Update User ---------------------------\\
    Update_User() {
      var self = this;
      self.SubmitProcessing = true;
      self.data.append("firstname", self.user.firstname);
      self.data.append("lastname", self.user.lastname);
      self.data.append("username", self.user.username);
      self.data.append("email", self.user.email);
      self.data.append("NewPassword", self.user.NewPassword);
      self.data.append("phone", self.user.phone);
      self.data.append("role", self.user.role_id);
      self.data.append("statut", self.user.statut);
      self.data.append("avatar", self.user.avatar);



      self.data.append("sun", self.schedule.sun);
      self.data.append("sun_start_time", self.schedule.sun_start_time);
      self.data.append("sun_end_time", self.schedule.sun_end_time);
      self.data.append("mon", self.schedule.mon);
      self.data.append("mon_start_time", self.schedule.mon_start_time);
      self.data.append("mon_end_time", self.schedule.mon_end_time);
      self.data.append("tue", self.schedule.tue);
      self.data.append("tue_start_time", self.schedule.tue_start_time);
      self.data.append("tue_end_time", self.schedule.tue_end_time);
      self.data.append("wed", self.schedule.wed);
      self.data.append("wed_start_time", self.schedule.wed_start_time);
      self.data.append("wed_end_time", self.schedule.wed_end_time);
      self.data.append("thu", self.schedule.thu);
      self.data.append("thu_start_time", self.schedule.thu_start_time);
      self.data.append("thu_end_time", self.schedule.thu_end_time);
      self.data.append("fri", self.schedule.fri);
      self.data.append("fri_start_time", self.schedule.fri_start_time);
      self.data.append("fri_end_time", self.schedule.fri_end_time);
      self.data.append("sat", self.schedule.sat);
      self.data.append("sat_start_time", self.schedule.sat_start_time);
      self.data.append("sat_end_time", self.schedule.sat_end_time);
      self.data.append("_method", "put");

      axios
        .post("users/" + this.user.id, self.data)
        .then(response => {
          this.makeToast(
            "success",
            this.$t("Update.TitleUser"),
            this.$t("Success")
          );
          Fire.$emit("Event_User");
          self.SubmitProcessing = false;
          console.log(response.data);
        })
        .catch(error => {
          if (error.errors.email.length > 0) {
            self.email_exist = error.errors.email[0];
          }
          this.makeToast("danger", this.$t("InvalidData"), this.$t("Failed"));
          self.SubmitProcessing = false;
        });
    },

    //----------------------------- Reset Form ---------------------------\\
    reset_Form() {
      this.user = {
        id: "",
        firstname: "",
        lastname: "",
        username: "",
        password: "",
        NewPassword: null,
        email: "",
        phone: "",
        statut: "",
        role_id: "",
        avatar: "",
      };
      this.email_exist= "";
      this.schedule = {
        sun: false,
        mon: false,
        tue: false,
        wed: false,
        thu: false,
        fri: false,
        sat: false,
        start_time: "",
        end_time: "",
        
      }
    },

    //--------------------------------- Remove User ---------------------------\\
    Remove_User(id) {
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
            .delete("users/" + id)
            .then(() => {
              this.$swal(
                this.$t("Delete.Deleted"),
                this.$t("Delete.UserDeleted"),
                "success"
              );

              Fire.$emit("Delete_User");
            })
            .catch(() => {
              this.$swal(
                this.$t("Delete.Failed"),
                "this User already linked with other operation",
                "warning"
              );
            });
        }
      });
    }
  }, // END METHODS

  //----------------------------- Created function-------------------
  created: function() {
    this.Get_Users(1);

    Fire.$on("Event_User", () => {
      setTimeout(() => {
        this.Get_Users(this.serverParams.page);
        this.$bvModal.hide("New_User");
        
      }, 500);
    });

    Fire.$on("Delete_User", () => {
      setTimeout(() => {
        this.Get_Users(this.serverParams.page);
      }, 500);
    });
  }
};
  

</script>
