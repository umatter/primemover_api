<template>
  <card :count="meta.total" :links="links" :meta="meta" :nogutter="true" :title="title"
        model="experiment"
        @fetchRecords="fetchRecords($event)"
  >
    <p v-if="loading">
      {{ $t('loading') }}
    </p>
    <data-table v-if="!loading" :records="records" model="experiment" size="md" type="striped" />
  </card>
</template>

<script>

import { mapGetters } from 'vuex'

export default {

  middleware: 'auth',

  data () {
    return {
      loading: true,
      title: this.$t('experiments')
    }
  },
  metaInfo () {
    return {
      title: this.$t('experiments')
    }
  },

  computed: mapGetters({
    response: 'experiment/response',
    links: 'pagination/links',
    meta: 'pagination/meta',
    records: 'experiment/records',
    record: 'experiment/record'
  }),
  created () {
    this.fetchRecords()
  },

  methods: {
    async fetchRecords (page, id) {
      await this.$store.dispatch('experiments/fetchRecords', { page: page, single: id })
        .then(() => {
          this.loading = false
        })
        .catch(e => {
          console.warn(e)
        })
    }
  }

}
</script>
