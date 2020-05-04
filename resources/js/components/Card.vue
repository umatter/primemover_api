<template>

  <div class="block block-fx-pop block-themed">
    <div class="block-header bg-dark" v-if="title">
      <h3 class="block-title">{{ title }} <small v-if="meta">{{ meta.total }}</small></h3>
      <div class="block-options">
        <router-link :to="{ name: model + '/add' }" active-class="active" class="btn btn-sm btn-primary" v-if="model">
          <i class="fa fa-plus fa-fw"></i>
        </router-link>
      </div>
    </div>
    <div class="block-content">
      <slot/>
    </div>
    <div class="block-content block-content-full block-content-sm bg-body-light font-size-sm " v-if="links">
      <nav aria-label="Page navigation">
        <ul class="pagination pagination-sm justify-content-center mb-0">

          <li :class="[{disabled: !links.prev}]" class="page-item" :title="( $t('previous'))" >
            <a @click.prevent="fetchRecords(meta.current_page - 1)" :aria-label="( $t('previous'))" class="page-link"
               href="javascript:void(0)"><span aria-hidden="true"><i class="fa fa-angle-left"></i></span>
              <span class="sr-only">{{ $t('previous')}}</span>
            </a>
          </li>
          <li>
            <span class="page-link">
            {{ meta.from }} - {{ meta.to }} / {{ meta.total }}
              </span>
          </li>

          <li :class="[{disabled: !links.next}]" class="page-item" :title="( $t('next'))" >
            <a @click.prevent="fetchRecords(meta.current_page + 1)" :aria-label="( $t('next'))" class="page-link"
               href="javascript:void(0)"><span aria-hidden="true"><i class="fa fa-angle-right"></i></span>
              <span class="sr-only">{{ $t('next') }}</span>
            </a>
          </li>
        </ul>
      </nav>
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
      },
      links: {
        type: Object,
      },
      meta: {
        type: Object,
      }
    },
    methods: {
      fetchRecords: function (e) {
        this.$emit('fetchRecords', e);
      }
    }
  }
</script>
