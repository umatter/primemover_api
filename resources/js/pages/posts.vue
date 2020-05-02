<template>
  <card :count="count" :nogutter="true" :title="title" model="post">
    <p v-if="loading">{{ $t('loading') }}</p>
    <div class="mb-3" v-for="item in response">
      <h1 class="content-heading">{{ item.title }}</h1>
      <div v-html="item.body"></div>
    </div>

  </card>
</template>

<script>

  import {mapGetters} from "vuex";

  export default {

    middleware: 'auth',

    data() {
      return {
        loading: true,
        title: this.$t('posts')
      }
    },
    metaInfo() {
      return {
        title: this.$t('posts')
      }
    },

    computed: mapGetters({
      response: 'post/records',
      count: 'post/count',
    }),

    methods: {
      async fetchRecords() {
        await this.$store.dispatch('post/fetchRecords').then(() => {
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
