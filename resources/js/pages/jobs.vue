<template>
  <card :count="count" :nogutter="true" :title="title" model="job">
    <p v-if="loading">{{ $t('loading') }}</p>
    <data-table :records="response" model="job" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

    data() {
      return {
        loading: true,
        title: this.$t('jobs')
      }
    },
    metaInfo() {
      return {
        title: this.$t('jobs')
      }
    },

    computed: mapGetters({
      response: 'job/records',
      count: 'job/count',
    }),

    methods: {
      async fetchRecords() {
        await this.$store.dispatch('job/fetchRecords').then(() => {
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
