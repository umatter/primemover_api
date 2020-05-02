<template>
  <card :nogutter="true" :title="$t('list')">
    <p v-if="loading">Loading...</p>
    <data-table :configurations="response.configurations" size="md" type="striped" v-if="!loading"></data-table>
  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

    data() {
      return {
        loading: true
      }
    },
    metaInfo() {
      return {title: this.$t('list')}
    },

    computed: mapGetters({
      response: 'bots/configurations'
    }),

    methods: {
      async fetchConfigurations() {
        await this.$store.dispatch('bots/fetchConfigurations').then((response) => {
          this.loading = false;
          console.log(response)
        }).catch(e => {
          console.log(e);
        });
      },
    },
    created() {
      this.fetchConfigurations()
    }

  }
</script>
