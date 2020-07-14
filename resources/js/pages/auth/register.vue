<template>
  <div class="row">
    <div class="col-lg-12 m-auto">
      <card :title="$t('register')" v-if="mustVerifyEmail">
        <div class="alert alert-success" role="alert">
          {{ $t('verify_email_address') }}
        </div>
      </card>
      <card :title="$t('register')" v-else>
        <form @keydown="form.onKeydown($event)" @submit.prevent="register">
          <!-- Name -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('name') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('name') }" class="form-control" name="name"
                     type="text" v-model="form.name">
              <has-error :form="form" field="name"/>
            </div>
          </div>

          <!-- Email -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('email') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('email') }" class="form-control" name="email"
                     type="email" v-model="form.email">
              <has-error :form="form" field="email"/>
            </div>
          </div>

          <!-- Password -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('password') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('password') }" class="form-control" name="password"
                     type="password" v-model="form.password">
              <has-error :form="form" field="password"/>
            </div>
          </div>

          <!-- Password Confirmation -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('confirm_password') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('password_confirmation') }"
                     class="form-control" name="password_confirmation"
                     type="password" v-model="form.password_confirmation">
              <has-error :form="form" field="password_confirmation"/>
            </div>
          </div>

          <div class="form-group row">
            <div class="col-md-7 offset-md-3 d-flex">

              <!-- GitHub Register Button -->
              <login-with-github/>


              <!-- Submit Button -->
              <v-button :loading="form.busy" class="ml-2">
                {{ $t('save') }}
              </v-button>


            </div>
            <div class="col-12">
              <hr>
            </div>

            <div class="col-md-7 offset-md-3 d-flex">

              <!-- Login Button -->
              <router-link :to="{ name: 'login' }" class="btn btn-warning mr-auto">
                {{ $t('back_to_login') }}
              </router-link>


            </div>
          </div>
        </form>
      </card>
    </div>
  </div>
</template>

<script>
  import Form from 'vform'
  import LoginWithGithub from '~/components/LoginWithGithub'
  import {apiDomain} from "../../config";

  export default {
    middleware: 'guest',

    components: {
      LoginWithGithub
    },

    metaInfo() {
      return {title: this.$t('register')}
    },

    data: () => ({
      form: new Form({
        name: '',
        email: '',
        password: '',
        password_confirmation: ''
      }),
      mustVerifyEmail: false
    }),

    methods: {
      async register() {
        // Register the user.
        const {data} = await this.form.post('/api/v1/register')

        console.log(data);

        // Must verify email fist.
        if (data.status) {
          this.mustVerifyEmail = true
        } else {
          // Log in the user.
          const {data: {token}} = await this.form.post('/api/v1/login')

          // Save the token.
          this.$store.dispatch('auth/saveToken', {token})

          // Update the user.
          await this.$store.dispatch('auth/updateUser', {user: data})

          // Redirect home.
          this.$router.push({name: 'home'})
        }
      }
    }
  }
</script>
