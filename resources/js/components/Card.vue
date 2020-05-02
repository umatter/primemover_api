<template>

  <div class="block block-fx-pop block-themed">
    <div class="block-header bg-dark" v-if="title">
      <h3 class="block-title">{{ title }} <small>Subtitle</small></h3>
      <div class="block-options">
        <router-link v-if="model" :to="{ name: model + '/add' }" active-class="active" class="btn btn-sm btn-primary">
          <i class="fa fa-plus fa-fw"></i>
        </router-link>
      </div>
    </div>
    <div class="block-content">
      <slot/>
    </div>
    <div v-if="count" class="block-content block-content-full block-content-sm bg-body-light font-size-sm">
      {{ $t('entries') }}: {{ count }}
    </div>
  </div>


</template>

<script>
  export default {
    name: 'Card',

    props: {
      title: {
        type: String, default: null
      },
      model: {
        type: String, default: null
      },
      count: {
        type: Number,
        default: 0
      }
    }
  }
</script>
