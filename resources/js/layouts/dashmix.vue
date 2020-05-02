<template>
  <!-- Page Container -->
  <!--
      Available classes for #page-container:

  GENERIC

      'enable-cookies'                            Remembers active color theme between pages (when set through color theme helper Template._uiHandleTheme())

  SIDEBAR & SIDE OVERLAY

      'sidebar-r'                                 Right Sidebar and left Side Overlay (default is left Sidebar and right Side Overlay)
      'sidebar-o'                                 Visible Sidebar by default (screen width > 991px)
      'sidebar-o-xs'                              Visible Sidebar by default (screen width < 992px)
      'sidebar-dark'                              Dark themed sidebar

      'side-overlay-hover'                        Hoverable Side Overlay (screen width > 991px)
      'side-overlay-o'                            Visible Side Overlay by default

      'enable-page-overlay'                       Enables a visible clickable Page Overlay (closes Side Overlay on click) when Side Overlay opens

      'side-scroll'                               Enables custom scrolling on Sidebar and Side Overlay instead of native scrolling (screen width > 991px)

  HEADER

      ''                                          Static Header if no class is added
      'page-header-fixed'                         Fixed Header


  Footer

      ''                                          Static Footer if no class is added
      'page-footer-fixed'                         Fixed Footer (please have in mind that the footer has a specific height when is fixed)

  HEADER STYLE

      ''                                          Classic Header style if no class is added
      'page-header-dark'                          Dark themed Header
      'page-header-glass'                         Light themed Header with transparency by default
                                                  (absolute position, perfect for light images underneath - solid light background on scroll if the Header is also set as fixed)
      'page-header-glass page-header-dark'         Dark themed Header with transparency by default
                                                  (absolute position, perfect for dark images underneath - solid dark background on scroll if the Header is also set as fixed)

  MAIN CONTENT LAYOUT

      ''                                          Full width Main Content if no class is added
      'main-content-boxed'                        Full width Main Content with a specific maximum width (screen width > 1200px)
      'main-content-narrow'                       Full width Main Content with a percentage width (screen width > 1200px)
  -->
  <div class="page-header-dark main-content-boxed" id="page-container">

    <!-- Header -->
    <navbar/>
    <!-- END Header -->

    <!-- Main Container -->
    <main class="bg-white" id="main-container">

      <!-- Navigation -->
      <div class="bg-white" v-if="user">
        <div class="content py-3">
          <ul class="nav nav-pills justify-content-center justify-content-md-start">
            <li class="nav-item mr-1">

              <router-link :to="{ name: 'home' }" active-class="active" class="nav-link">
                <i class="fa fa-home fa-fw"></i>
                <span class="d-none d-md-inline ml-1">{{ $t('home') }}</span>
              </router-link>
            </li>


            <li class="nav-item mr-1">
              <router-link :to="{ name: 'agents' }" active-class="active" class="nav-link">
                <i class="fa fa-id-card-alt fa-fw"></i>
                <span class="d-none d-md-inline ml-1">{{ $t('agents') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'proxies' }" active-class="active" class="nav-link">
                <i class="fa fa-align-right fa-fw"></i>
                <span class="d-none d-md-inline ml-1">{{ $t('proxies') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'configurations' }" active-class="active" class="nav-link">
                <i class="fa fa-list-alt fa-fw"></i>
                <span class="d-none d-md-inline ml-1">{{ $t('configurations') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'queries' }" active-class="active" class="nav-link">
                <i class="fa fa-search fa-fw"></i>
                <span class="d-none d-md-inline ml-1">{{ $t('queries') }}</span>
              </router-link>
            </li>


            <li class="nav-item mr-1">
              <router-link :to="{ name: 'crawlers' }" active-class="active" class="nav-link">
                <i class="fa fa-globe-europe fa-fw"></i>
                <span class="d-none d-md-inline ml-1">{{ $t('crawlers') }}</span>
              </router-link>
            </li>

            <li class="nav-item ml-md-auto">
              <!-- Button right -->
            </li>
          </ul>
        </div>
      </div>
      <!-- END Navigation -->

      <!-- Page Content -->
      <div class="bg-light">
        <div class="content content-full">
          <!-- App -->
          <child/>
        </div>
      </div>
      <!-- END Page Content -->
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    <footer class="footer-static bg-white" id="page-footer">
      <div class="content py-4" v-if="user">
        <!-- Footer Navigation -->
        <div class="row items-push font-size-sm border-bottom pt-4">
          <div class="col-6 col-md-4">
            <h3 class="h5 font-w700">{{ $t('profile')}}</h3>
            <ul class="list list-simple-mini">
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-user-circle text-primary-light mr-1"></i> {{ user.name }}
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-envelope-open-text text-primary-light mr-1"></i> {{ user.email }}
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-wrench text-primary-light mr-1"></i> {{ $t('settings')}}
                </a>
              </li>

            </ul>
          </div>
          <div class="col-6 col-md-4">
            <h3 class="h5 font-w700">{{ $t('contact')}}</h3>
            <ul class="list list-simple-mini">
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-phone text-primary-light mr-1"></i>Tel.: +41 71 224 23 40
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-fax text-primary-light mr-1"></i>Fax: +41 71 224 22 98
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-globe text-primary-light mr-1"></i>http://www.siaw.unisg.ch
                </a>
              </li>

            </ul>
          </div>
          <div class="col-md-4">
            <h3 class="h5 font-w700">University of St. Gallen</h3>
            <ul class="list list-simple-mini">
              <li>SEPS-HSG/SIAW</li>
              <li>Bodanstrasse 8</li>
              <li>9000 St. Gallen, Switzerland</li>
            </ul>
          </div>
        </div>
        <!-- END Footer Navigation -->

        <!-- Footer Copyright -->
        <div class="row font-size-sm pt-4">
          <div class="col-sm-6 order-sm-2 mb-1 mb-sm-0 text-center text-sm-right">
            SEPS-HSG/SIAW <i class="fa fa-heart text-danger"></i> <a class="font-w600"
                                                             href="http://siaw.unisg.ch"
                                                             target="_blank">2020</a>
          </div>
          <div class="col-sm-6 order-sm-1 text-center text-sm-left">
            <a class="font-w600" href="https://1.envato.market/r6y" target="_blank">primemover</a> &copy; <span
            data-toggle="year-copy"></span>
          </div>
        </div>
        <!-- END Footer Copyright -->
      </div>
    </footer>
    <!-- END Footer -->
  </div>

  <!-- END Page Container -->
</template>

<script>

  import Navbar from '~/components/Navbar'
  import {mapGetters} from "vuex";

  export default {
    name: 'DashmixLayout',
    components: {
      Navbar
    },
    computed: mapGetters({
      user: 'auth/user'
    }),
  }
</script>
