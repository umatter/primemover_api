function page (path) {
  return () => import(/* webpackChunkName: '' */ `~/pages/${path}`).then(m => m.default || m)
}

export default [
  { path: '/', name: 'welcome', component: page('welcome.vue') },

  { path: '/login', name: 'login', component: page('auth/login.vue') },
  { path: '/register', name: 'register', component: page('auth/register.vue') },
  { path: '/password/reset', name: 'password.request', component: page('auth/password/email.vue') },
  { path: '/password/reset/:token', name: 'password.reset', component: page('auth/password/reset.vue') },
  { path: '/email/verify/:id', name: 'verification.verify', component: page('auth/verification/verify.vue') },
  { path: '/email/resend', name: 'verification.resend', component: page('auth/verification/resend.vue') },

  { path: '/home', name: 'home', component: page('home.vue') },
  { path: '/list', name: 'list', component: page('list.vue') },
  { path: '/configurations', name: 'configurations', component: page('configurations.vue') },
  { path: '/agents', name: 'agents', component: page('agents.vue') },
  { path: '/crawlers', name: 'crawlers', component: page('crawlers.vue') },
  { path: '/queries', name: 'queries', component: page('queries.vue') },
  { path: '/proxies', name: 'proxies', component: page('proxies.vue') },
  { path: '/posts', name: 'posts', component: page('posts.vue') },
  { path: '/new', name: 'new', component: page('new.vue') },

  { path: '/crawler/add', name: 'crawler/add', component: page('crawler.add.vue') },
  { path: '/configuration/add', name: 'configuration/add', component: page('configuration.add.vue') },
  { path: '/agent/add', name: 'agent/add', component: page('agent.add.vue') },
  { path: '/query/add', name: 'query/add', component: page('query.add.vue') },
  { path: '/proxy/add', name: 'proxy/add', component: page('proxy.add.vue') },
  { path: '/post/add', name: 'post/add', component: page('post.add.vue') },

  { path: '/settings',
    component: page('settings/index.vue'),
    children: [
      { path: '', redirect: { name: 'settings.profile' } },
      { path: 'profile', name: 'settings.profile', component: page('settings/profile.vue') },
      { path: 'password', name: 'settings.password', component: page('settings/password.vue') }
    ] },

  { path: '*', component: page('errors/404.vue') }
]
