<template>
  <!-- Page Container -->
  <div id="page-container" class="page-header-dark main-content-boxed">
    <!-- Header -->
    <navbar />
    <!-- END Header -->

    <!-- Main Container -->
    <main id="main-container" class="bg-white">
      <!-- Navigation -->
      <div v-if="user" class="bg-white">
        <div class="content py-3">
          <ul class="nav nav-pills justify-content-center justify-content-md-start">
            <li class="nav-item mr-1">
              <router-link :to="{ name: 'home' }" active-class="active" class="nav-link">
                <i class="fa fa-home fa-fw" />
                <span class="d-none d-md-inline ml-1">{{ $t('home') }}</span>
              </router-link>
            </li>

            asdf
            <li class="nav-item mr-1">
              <router-link :to="{ name: 'experiments' }" active-class="active" class="nav-link">
                <i class="fa fa-id-card-alt fa-fw" />
                <span class="d-none d-md-inline ml-1">{{ $t('experiments') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'agents' }" active-class="active" class="nav-link">
                <i class="fa fa-id-card-alt fa-fw" />
                <span class="d-none d-md-inline ml-1">{{ $t('agents') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'proxies' }" active-class="active" class="nav-link">
                <i class="fa fa-align-right fa-fw" />
                <span class="d-none d-md-inline ml-1">{{ $t('proxies') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'configurations' }" active-class="active" class="nav-link">
                <i class="fa fa-list-alt fa-fw" />
                <span class="d-none d-md-inline ml-1">{{ $t('configurations') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'jobs' }" active-class="active" class="nav-link">
                <i class="fa fa-search fa-fw" />
                <span class="d-none d-md-inline ml-1">{{ $t('jobs') }}</span>
              </router-link>
            </li>

            <li class="nav-item mr-1">
              <router-link :to="{ name: 'crawlers' }" active-class="active" class="nav-link">
                <i class="fa fa-globe-europe fa-fw" />
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
          <child />
        </div>
      </div>
      <!-- END Page Content -->
    </main>
    <!-- END Main Container -->

    <!-- Footer -->
    <footer id="page-footer" class="footer-static bg-white">
      <div v-if="user" class="content py-4">
        <!-- Footer Navigation -->
        <div class="row items-push font-size-sm border-bottom pt-4">
          <div class="col-6 col-md-4">
            <h3 class="h5 font-w700">
              {{ $t('profile') }}
            </h3>
            <ul class="list list-simple-mini">
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-user-circle text-primary-light mr-1" /> {{ user.name }}
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-envelope-open-text text-primary-light mr-1" /> {{ user.email }}
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-wrench text-primary-light mr-1" /> {{ $t('settings') }}
                </a>
              </li>
            </ul>
          </div>
          <div class="col-6 col-md-4">
            <h3 class="h5 font-w700">
              {{ $t('contact') }}
            </h3>
            <ul class="list list-simple-mini">
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-phone text-primary-light mr-1" />Tel.: +41 71 224 23 40
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-fax text-primary-light mr-1" />Fax: +41 71 224 22 98
                </a>
              </li>
              <li>
                <a class="font-w600" href="javascript:void(0)">
                  <i class="fa fa-fw fa-globe text-primary-light mr-1" />http://www.siaw.unisg.ch
                </a>
              </li>
            </ul>
          </div>
          <div class="col-md-4">
            <h3 class="h5 font-w700">
              University of St. Gallen
            </h3>
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
            SEPS-HSG/SIAW <i class="fa fa-heart text-danger" /> <a class="font-w600"
                                                                   href="http://siaw.unisg.ch"
                                                                   target="_blank"
            >2020</a>
          </div>
          <div class="col-sm-6 order-sm-1 text-center text-sm-left">
            <a class="font-w600" href="https://1.envato.market/r6y" target="_blank">primemover</a> &copy; <span
              data-toggle="year-copy"
            />
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
import { mapGetters } from 'vuex'

export default {
  name: 'DashmixLayout',
  components: {
    Navbar
  },
  computed: mapGetters({
    user: 'auth/user'
  })
}
</script>
