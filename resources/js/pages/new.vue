<template>
  <div class="row">
    <div class="col-lg-12 m-auto">
      <card :title="$t('create_new_configuration')" v-if="mustVerifyEmail">
        <div class="alert alert-success" role="alert">
          {{ $t('verify_email_address') }}
        </div>
      </card>
      <card :title="$t('create_new_configuration')" v-else>
        <form @keydown="form.onKeydown($event)" @submit.prevent="create">
          <!-- Name -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('name') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('name') }" :placeholder="$t('name')" class="form-control"
                     name="name" type="text" v-model="form.name">
              <has-error :form="form" field="name"/>
            </div>
          </div>

          <!-- Name -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right" for="description">{{ $t('description') }}</label>
            <div class="col-md-7">
              <textarea :class="{ 'is-invalid': form.errors.has('description') }" :placeholder="$t('description')"
                        class="form-control"
                        id="description" name="description" rows="3"
                        v-model="form.description"></textarea>
            </div>
          </div>

          <!-- Status -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('active') }}</label>
            <div class="col-md-7">
              <select :class="{ 'is-invalid': form.errors.has('active') }" :title="$t('active')" class="custom-select"
                      name="active" v-model="form.active">
                <option disabled selected value="">{{ $t('active') }}</option>
                <option value="1">{{ $t('active') }}</option>
                <option value="0">{{ $t('inactive') }}</option>
              </select>
              <has-error :form="form" field="active"/>
            </div>
          </div>

          <!-- URL -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('url') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('url') }" :placeholder="$t('url')" class="form-control"
                     name="url" type="text" v-model="form.url">
              <has-error :form="form" field="url"/>
            </div>
          </div>

          <!-- Query -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('search_query') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('search_query') }" :placeholder="$t('search_query')"
                     class="form-control"
                     name="search_query" type="text" v-model="form.search_query">
              <has-error :form="form" field="search_query"/>
            </div>
          </div>

          <!-- Browser -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('browser') }}</label>
            <div class="col-md-7">
              <select :class="{ 'is-invalid': form.errors.has('browser') }" :title="$t('browser')" class="custom-select"
                      name="browser" v-model="form.browser">
                <option disabled selected value="">{{ $t('browser') }}</option>
                <option value="2">Chrome</option>
                <option value="3">Firefox</option>
              </select>
              <has-error :form="form" field="browser"/>
            </div>
          </div>

          <!-- Region -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('region') }}</label>
            <div class="col-md-7">
              <select :class="{ 'is-invalid': form.errors.has('region') }" class="custom-select" name="region"
                      v-model="form.region">
                <option disabled selected value="">{{ $t('region') }}</option>
                <option value="2">Europe</option>
                <option value="3">USA</option>
              </select>
              <has-error :form="form" field="region"/>
            </div>
          </div>


          <div class="form-group row">
            <div class="col-md-7 offset-md-3 d-flex">
              <!-- Submit Button -->
              <v-button :loading="form.busy">
                {{ $t('save') }}
              </v-button>

            </div>
          </div>
        </form>
      </card>
    </div>
  </div>
</template>

<script>
  import Form from 'vform'

  export default {
    middleware: 'auth',

    metaInfo() {
      return {title: this.$t('new')}
    },

    data: () => ({
      form: new Form({
        name: '',
        active: 0,
        description: '',
        search_query: '',
        browser: '',
        region: '',
        url: '',
      }),
      mustVerifyEmail: false
    }),

    methods: {
      async create() {

        // Store data.
        const {data} = this.form.post('/api/configurations')
        //await this.$store.dispatch('bots/createConfiguration', {configuration: data})
        //console.log(data);
        // Redirect home.
        this.$router.push({name: 'list'})

      }
    }
  }
</script>
