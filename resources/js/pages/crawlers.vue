<template>
  <card :count="count" :nogutter="true" :title="title" model="crawler">
    <p v-if="loading">{{ $t('loading') }}</p>
    <data-table :records="response" model="crawler" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

    data() {
      return {
        loading: true,
        title: this.$t('crawlers')
      }
    },
    metaInfo() {
      return {
        title: this.$t('crawlers')
      }
    },

    computed: mapGetters({
      response: 'crawler/records',
      count: 'crawler/count',
    }),

    methods: {
      async fetchRecords() {
        await this.$store.dispatch('crawler/fetchRecords').then(() => {
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
