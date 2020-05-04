<template>
  <div class="row">
    <div class="col-lg-12 m-auto">
      <card :title="$t('query')" v-if="mustVerifyEmail">
        <div class="alert alert-success" role="alert">
          {{ $t('verify_email_address') }}
        </div>
      </card>
      <card :title="$t('query')" v-else>
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

          <!-- URL -->
          <div class="form-group row">
            <label for="url" class="col-md-3 col-form-label text-md-right">{{ $t('url') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('url') }" :placeholder="$t('url')" class="form-control"
                     name="url" id="url" type="text" v-model="form.url">
              <has-error :form="form" field="url"/>
            </div>
          </div>

          <!-- Query -->
          <div class="form-group row">
            <label for="query" class="col-md-3 col-form-label text-md-right">{{ $t('query') }}</label>
            <div class="col-md-7">
              <input :class="{ 'is-invalid': form.errors.has('url') }" :placeholder="$t('query')" class="form-control"
                     name="query" id="query" type="text" v-model="form.query">
              <has-error :form="form" field="query"/>
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
      return {title: this.$t('query')}
    },

    data: () => ({
      form: new Form({
        name: '',
        url: '',
        query: '',
        active: 0,
        description: '',
      }),
      mustVerifyEmail: false
    }),

    methods: {
      async create() {
        if (this.form.post('/api/queries')) {
          this.$router.push({name: 'queries'})
        }
      }
    }
  }
</script>
