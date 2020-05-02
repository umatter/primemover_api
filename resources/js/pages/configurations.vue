<template>
  <card :count="count" :nogutter="true" :title="title" model="configuration">
    <p v-if="loading">{{ $t('loading') }}</p>
    <data-table :records="response" model="configuration" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

    data() {
      return {
        loading: true,
        title: this.$t('configurations')
      }
    },
    metaInfo() {
      return {
        title: this.$t('configurations')
      }
    },

    computed: mapGetters({
      response: 'configuration/records',
      count: 'configuration/count',
    }),

    methods: {
      async fetchRecords() {
        await this.$store.dispatch('configuration/fetchRecords').then(() => {
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
