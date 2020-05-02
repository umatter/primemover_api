<template>
  <card :count="count" :nogutter="true" :title="title" model="proxy">
    <p v-if="loading">{{ $t('loading') }}</p>
    <data-table :records="response" model="proxy" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

    data() {
      return {
        loading: true,
        title: this.$t('proxies')
      }
    },
    metaInfo() {
      return {
        title: this.$t('proxies')
      }
    },

    computed: mapGetters({
      response: 'proxy/records',
      count: 'proxy/count',
    }),

    methods: {
      async fetchRecords() {
        await this.$store.dispatch('proxy/fetchRecords').then(() => {
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
