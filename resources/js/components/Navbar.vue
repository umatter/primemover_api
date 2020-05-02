<template>
  <header id="page-header">
    <!-- Header Content -->
    <div class="content-header">
      <!-- Left Section -->
      <div class="d-flex align-items-center">
        <!-- Logo -->
        <router-link :to="{ name: user ? 'home' : 'welcome' }" class="navbar-brand">
          <a class="font-size-lg font-w600 text-white" href="#">
            <span class="text-white-75"><span><i class="si si-rocket mr-2"></i></span>{{ appName }}</span>

          </a>
        </router-link>
        <!-- END Logo -->

        <!-- Open Search Section -->
        <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
        <button class="d-none btn btn-dual ml-2" data-action="header_search_on" data-toggle="layout" type="button">
          <i class="si si-magnifier"></i>
        </button>
        <!-- END Open Search Section -->
      </div>
      <!-- END Left Section -->

      <!-- Right Section -->
      <div>
        <locale-dropdown/>
        <!-- User Dropdown -->
        <div class="dropdown d-inline-block" v-if="user">
          <button aria-expanded="false" aria-haspopup="true" class="btn btn-dual dropdown-toggle"
                  data-toggle="dropdown" id="page-header-user-dropdown" type="button">

            <span class="d-none d-sm-inline ml-1">{{ user.name }}</span>

          </button>
          <div aria-labelledby="page-header-user-dropdown"
               class="dropdown-menu dropdown-menu-lg dropdown-menu-right p-0">
            <div class="rounded-top font-w600 text-white"
                 >

              <div class="p-3 bg-primary-dark-op">
                <a class="text-white font-w600" href="#">{{ user.name }}</a>
                <div class="text-white-75">{{ user.email }}</div>
              </div>
            </div>
            <div class="p-2">

              <router-link :to="{ name: 'settings.profile' }"
                           class="dropdown-item d-flex justify-content-between align-items-center">
                {{ $t('settings') }}
                <i class="fa fa-fw fa-user-circle text-black-50 ml-1"></i>
              </router-link>
              <!--
              <div class="dropdown-divider" role="separator"></div>
              <a class="dropdown-item d-flex justify-content-between align-items-center" href="javascript:void(0)">
                Preferences
                <i class="fa fa-fw fa-wrench text-black-50 ml-1"></i>
              </a>
              -->
              <div class="dropdown-divider" role="separator"></div>
              <a @click.prevent="logout" class="dropdown-item d-flex justify-content-between align-items-center"
                 href="#">
                {{ $t('logout') }}
                <i class="fa fa-fw fa-sign-out-alt text-danger ml-1"></i>
              </a>


            </div>
          </div>
        </div>
        <!-- END User Dropdown -->
      </div>
      <!-- END Right Section -->
    </div>
    <!-- END Header Content -->

    <!-- Header Search -->
    <div class="overlay-header bg-header-dark" id="page-header-search">
      <div class="content-header">
        <form class="w-100" method="POST">
          <div class="input-group">
            <div class="input-group-prepend">
              <!-- Layout API, functionality initialized in Template._uiApiLayout() -->
              <button class="btn btn-primary" data-action="header_search_off" data-toggle="layout" type="button">
                <i class="fa fa-fw fa-times-circle"></i>
              </button>
            </div>
            <input class="form-control" id="page-header-search-input" name="page-header-search-input"
                   placeholder="Search your websites.."
                   type="text">
          </div>
        </form>
      </div>
    </div>
    <!-- END Header Search -->

    <!-- Header Loader -->
    <!-- Please check out the Loaders page under Components category to see examples of showing/hiding it -->
    <div class="overlay-header bg-primary" id="page-header-loader">
      <div class="content-header">
        <div class="w-100 text-center">
          <i class="fa fa-fw fa-2x fa-spinner fa-spin text-white"></i>
        </div>
      </div>
    </div>
    <!-- END Header Loader -->
  </header>

</template>


<script>
  import {mapGetters} from 'vuex'
  import LocaleDropdown from './LocaleDropdown'

  export default {
    components: {
      LocaleDropdown
    },

    data: () => ({
      appName: window.config.appName
    }),

    computed: mapGetters({
      user: 'auth/user'
    }),

    methods: {
      async logout() {
        // Log out the user.
        await this.$store.dispatch('auth/logout')

        // Redirect to login.
        this.$router.push({name: 'login'})
      }
    }
  }
</script>

