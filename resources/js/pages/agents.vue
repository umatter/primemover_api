<template>
  <card :count="meta.total" :links="links" :meta="meta" :nogutter="true" :title="title"
        @fetchRecords="fetchRecords($event)"
        model="agent">
    <p v-if="loading">{{ $t('loading') }}</p>
    <data-table :records="records" model="agent" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    //middleware: 'auth',

    data() {
      return {
        loading: true,
        title: this.$t('agents')
      }
    },
    metaInfo() {
      return {
        title: this.$t('agents')
      }
    },

    computed: mapGetters({
      response: 'agent/response',
      links: 'pagination/links',
      meta: 'pagination/meta',
      records: 'agent/records',
      record: 'agent/record',
    }),

    methods: {
      async fetchRecords(page, id) {

        await this.$store.dispatch('agent/fetchRecords', {page: page, single: id})
          .then(() => {
            this.loading = false;
          })
          .catch(e => {
            console.warn(e);
          });
      },
    },
    created() {
      this.fetchRecords()
    }

  }
</script>
