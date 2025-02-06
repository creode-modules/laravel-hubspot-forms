import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  title: "Laravel HubSpot Forms",
  description: "Easy intgegration with HubSpot for you Laravel application",
  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Get Started', link: '/introduction' },
      { text: 'API Docs', link: '/api/introduction' }
    ],
    sidebar: [
      {
        text: 'Introduction',
        items: [
          { text: 'What is it?', link: '/introduction/what-is-it' },
          { text: 'Quick Start', link: '/introduction/quick-start' }
        ]
      },
      {
        text: 'Embedded Forms',
        items: [
          { text: 'Usage', link: '/embedded-forms/usage' },
        ]
      },
      {
        text: 'API Wrapper',
        items: [
          { text: 'Introduction', link: '/api/introduction' },
          { text: 'Configuration', link: '/api/configuration' },
          { text: 'Methods', link: '/api/methods' },
          { text: 'Examples', link: '/api/examples' },
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/creode-modules/laravel-hubspot-forms' }
    ],

    markdown:{
      toc:{
        // only include h2
        includeLevel: [2]
      }
    }
  }
})
