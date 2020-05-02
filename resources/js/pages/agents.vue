<template>
  <card :count="count" :nogutter="true" :title="title" model="agent" >
    <p v-if="loading">{{ $t('loading') }}</p>
    <data-table :records="response" model="agent" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

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
      response: 'agent/records',
      count: 'agent/count',
    }),

    methods: {
      async fetchRecords() {
        await this.$store.dispatch('agent/fetchRecords').then(() => {
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
