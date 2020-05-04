<template>
  <card :title="$t('home')">
    <h2 class="h3 font-w400">{{ $t('the_institute') }}</h2>
    <p>{{ $t('home_intro') }}</p>
    <p>{{ $t('project_intro') }}</p>
    <h2 class="h3 font-w400">{{ $t('the_university') }}</h2>
    <p>{{ $t('company_intro') }}</p>
    <h2 class="h3 font-w400">{{ $t('the_project') }}</h2>
    <p>Something else...</p>

  </card>
</template>

<script>
export default {
  middleware: 'auth',

  metaInfo () {
    return { title: this.$t('home') }
  }
}
</script>
