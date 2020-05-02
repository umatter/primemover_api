<template>

  <div class="dropdown d-inline-block">
    <button aria-expanded="false" aria-haspopup="true" class="btn btn-dark px-3" data-toggle="dropdown"
            id="dropdown-analytics-visitors-growth" type="button">
      {{ locales[locale] }} <i class="fa fa-fw fa-angle-down"></i>
    </button>

    <div aria-labelledby="dropdown-analytics-visitors-growth"
         class="dropdown-menu dropdown-menu-right font-size-sm" style="">
      <a :key="key" @click.prevent="setLocale(key)" class="dropdown-item" href="#"
         v-for="(value, key) in locales"
      >
        {{ value }}
      </a>
    </div>
  </div>
</template>

<script>
  import {mapGetters} from 'vuex'
  import {loadMessages} from '~/plugins/i18n'

  export default {
    computed: mapGetters({
      locale: 'lang/locale',
      locales: 'lang/locales'
    }),

    methods: {
      setLocale(locale) {
        if (this.$i18n.locale !== locale) {
          loadMessages(locale)

          this.$store.dispatch('lang/setLocale', {locale})
        }
      }
    }
  }
</script>
