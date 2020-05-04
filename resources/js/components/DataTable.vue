<template>

  <table :class="thClassValue()" class="js-table-sections table table-hover table-vcenter" v-if="records.length">
    <thead>
    <tr>
      <th style="width: 30px;"></th>
      <th scope="col">{{ $t('name') }}</th>
      <th scope="col">{{ $t('active') }}</th>
      <th class="text-right d-none d-md-table-cell" scope="col">{{ $t('date') }}</th>
      <th class="text-right"></th>
    </tr>
    </thead>
    <template v-for="(item, index) in records" v-if="!loading">
      <tbody :class="{ 'show table-active': toggleShow(item.id) }" :id="item.id" :key="item.id"
             @click="tableToolsSections($event, item.id)"
             class="js-table-sections-header">
      <tr>
        <td class="text-center">
          <i class="fa fa-angle-right text-muted"></i>
        </td>
        <td>{{ item.name }}</td>
        <td class="text-success" v-show="item.active">

          {{ $t('active') }}

        </td>
        <td class="text-warning" v-show="!item.active">
          {{ $t('inactive') }}
        </td>
        <td class="text-right d-none d-md-table-cell">{{ $d(item.date, 'long') }}</td>
        <td class="text-right">

          <router-link :to="{ name: 'agent/edit', params: { id: item.id } }" class="btn btn-info btn-sm">
            <i class="si si-pencil"></i>
          </router-link>

          <button @click="destroyRecord(item.id)" class="btn btn-danger btn-sm">
            <i class="si si-trash"></i>
          </button>
        </td>
      </tr>

      </tbody>
      <tbody class="font-size-sm">
      <tr>
        <td></td>
        <td colspan="100">
          <small class="label" v-if="item.description">{{ $t('description') }}</small>
          <small class="description" v-if="item.description">{{ item.description }}</small>

          <!-- Proxy -->
          <template v-if="item.proxy">
            <small class="label">{{ $t('proxy') }}</small>
            <small class="description">{{ item.proxy.name }} | {{ item.proxy.hostname }}:{{ item.proxy.port }}</small>
          </template>

          <!-- Agent -->
          <template v-if="item.agent">
            <small class="label">{{ $t('agent') }}</small>
            <small class="description">{{ item.agent.name }} | {{ item.agent.identification }}</small>
          </template>

          <!-- Configuration -->
          <template v-if="item.configuration">
            <small class="label">{{ $t('configuration') }}</small>
            <small class="description">{{ item.configuration.name }}</small>
          </template>

          <!-- Query -->
          <template v-if="item.query">
            <small class="label">{{ $t('query') }}</small>
            <small class="description">{{ item.query.name }}</small>
          </template>


          <small class="label" v-if="item.browser">{{ $t('browser') }}</small>
          <small class="description" v-if="item.browser">{{ item.browser }}</small>

          <small class="label" v-if="item.region">{{ $t('region') }}</small>
          <small class="description" v-if="item.region">{{ item.region }}</small>

          <small class="label" v-if="item.url">{{ $t('url') }}</small>
          <small class="description" v-if="item.url">{{ item.url }}</small>

          <small class="label" v-if="item.search_query">{{ $t('search_query') }}</small>
          <small class="description" v-if="item.search_query">{{ item.search }}</small>
        </td>

      </tr>
      </tbody>
    </template>
  </table>
  <p v-else>{{ $t('no_entries')}}</p>

</template>

<script>

  export default {
    name: 'DataTable',

    props: {
      title: {type: String, default: null},
      size: {type: String, default: 'md'},
      type: {type: String, default: 'striped'},
      records: {type: Array},
      model: {type: String, default: ''}
    },
    data() {
      return {
        tableSections: [],
        loading: false,
      }
    },

    methods: {
      async destroyRecord(id) {
        //this.loading = true;
        let type = this.model + '/destroyRecord';
        await this.$store.dispatch(type, {
          id: id
        }).then(() => {
          //this.loading = false;
        });
      },
      thClassValue: function () {
        return 'table-' + this.size;
      },

      toggleShow: function (e) {
        return this.tableSections.includes(e);
      },
      tableToolsSections: function (e, id) {
        let classList = e.target.classList;
        let check = classList.contains('btn') || classList.contains('si');
        //console.log(check);

        if (!check) {
          if (this.tableSections.includes(id)) {
            this.tableSections = this.tableSections.filter(item => item !== id)
          } else {
            this.tableSections.push(id);
          }

        }
      }
    },
    created() {
      //this.loading = false;
      //console.log(this.model)
    }
  }
</script>
<style scoped>
  th {
    border-top-width: 0;
  }

  .label {
    display: block;
    font-weight: bold;
  }

  .description {
    display: block;
    margin-bottom: 0.5rem;
  }

  button {
    position: relative;
    z-index: 10;
  }
</style>
