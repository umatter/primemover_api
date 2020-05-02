<template>
  <card :count="count" :nogutter="true" :title="title" model="query">
    <p v-if="loading">{{ $t('loading') }}</p>
    <data-table :records="response" model="query" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

    data() {
      return {
        loading: true,
        title: this.$t('queries')
      }
    },
    metaInfo() {
      return {
        title: this.$t('queries')
      }
    },

    computed: mapGetters({
      response: 'query/records',
      count: 'query/count',
    }),

    methods: {
      async fetchRecords() {
        await this.$store.dispatch('query/fetchRecords').then(() => {
          this.loading = false;
        }).catch(e => {
          console.warn(e);
        });
      },
    },
    created() {
      this.fetchRecords()
    }

  }
</script>
