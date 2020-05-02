<template>
  <div class="row">
    <div class="col-lg-12 m-auto">
      <card :title="$t('crawler')" v-if="mustVerifyEmail">
        <div class="alert alert-success" role="alert">
          {{ $t('verify_email_address') }}
        </div>
      </card>
      <card :title="$t('crawler')" v-else>
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

          <!-- Proxy -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('proxy') }}</label>
            <div class="col-md-7">
              <select :class="{ 'is-invalid': form.errors.has('proxy_id') }" :title="$t('proxy')" class="custom-select"
                      name="proxy_id" v-model="form.proxy_id">
                <option disabled  value="0">{{ $t('proxy') }}</option>
                <option v-for="proxy in proxies" :value="proxy.id">{{ proxy.name }}</option>
              </select>
              <has-error :form="form" field="proxy_id"/>
            </div>
          </div>

          <!-- Configuration -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('configuration') }}</label>
            <div class="col-md-7">
              <select :class="{ 'is-invalid': form.errors.has('configuration_id') }" :title="$t('configuration')" class="custom-select"
                      name="configuration_id" v-model="form.configuration_id">
                <option disabled selected value="0">{{ $t('configuration') }}</option>
                <option v-for="configuration in configurations" :value="configuration.id">{{ configuration.name }}</option>
              </select>
              <has-error :form="form" field="configuration_id"/>
            </div>
          </div>

          <!-- Agent -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('agent') }}</label>
            <div class="col-md-7">
              <select :class="{ 'is-invalid': form.errors.has('agent_id') }" :title="$t('agent')" class="custom-select"
                      name="agent_id" v-model="form.agent_id">
                <option disabled selected>{{ $t('agent') }}</option>
                <option v-for="agent in agents" :value="agent.id">{{ agent.name }}</option>
              </select>
              <has-error :form="form" field="agent_id"/>
            </div>
          </div>

          <!-- Query -->
          <div class="form-group row">
            <label class="col-md-3 col-form-label text-md-right">{{ $t('query') }}</label>
            <div class="col-md-7">
              <select :class="{ 'is-invalid': form.errors.has('query_id') }" :title="$t('query')" class="custom-select"
                      name="query_id" v-model="form.query_id">
                <option disabled selected value="0">{{ $t('query') }}</option>
                <option v-for="query in queries" :value="query.id">{{ query.name }}</option>
              </select>
              <has-error :form="form" field="query_id"/>
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
  import {mapGetters} from "vuex";

  export default {
    middleware: 'auth',

    metaInfo() {
      return {title: this.$t('crawler')}
    },

    data: () => ({
      form: new Form({
        name: '',
        active: 0,
        description: '',
      }),
      mustVerifyEmail: false
    }),

    computed: mapGetters({
      proxies: 'proxy/records',
      queries: 'query/records',
      agents: 'agent/records',
      configurations: 'configuration/records',
    }),

    methods: {
      async fetch() {
        await this.$store.dispatch('proxy/fetchRecords');
        await this.$store.dispatch('agent/fetchRecords');
        await this.$store.dispatch('configuration/fetchRecords');
        await this.$store.dispatch('query/fetchRecords');
      },

      async create() {
        if (this.form.post('/api/crawlers')) {
          this.$router.push({name: 'crawlers'})
        }
      }
    },
    created() {
      this.fetch();
    }
  }
</script>

