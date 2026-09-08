import { defineConfig } from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
  lang: 'en-US',
  srcDir: 'docs',

  title: 'Roolith PHP Framework',
  description: 'PHP micro-framework. Very minimalistic and less overhead.',

  themeConfig: {
    // https://vitepress.dev/reference/default-theme-config
    nav: [
      { text: 'Home', link: '/' },
      { text: 'Guide', link: '/getting-started' }
    ],

    sidebar: [
      {
        text: 'Getting Started',
        items: [
          { text: 'Getting Started', link: '/getting-started' },
          { text: 'Architecture', link: '/architecture' },
          { text: 'Configuration', link: '/configuration' },
          { text: 'Generator', link: '/generator' },
          { text: 'CLI', link: '/cli' },
          { text: 'Frontend Workflow', link: '/frontend-workflow' },
          { text: 'Docker', link: '/docker' },
          { text: 'CMS Installer', link: '/cms-installer' },
          { text: 'Testing', link: '/testing' }
        ]
      },
      {
        text: 'The Basics',
        items: [
          { text: 'Routing', link: '/routing' },
          { text: 'Controllers', link: '/controllers' },
          { text: 'Views', link: '/views' },
          { text: 'Models', link: '/models' },
          { text: 'Database', link: '/database' },
          { text: 'Migration', link: '/migration' },
          { text: 'Seeder', link: '/seeder' },
          { text: 'Request', link: '/request' },
          { text: 'Response', link: '/response' },
          { text: 'Error Handling', link: '/error-handling' },
          { text: 'Logging', link: '/logging' },
          { text: 'Validation', link: '/validation' }
        ]
      },
      {
        text: 'Digging Deeper',
        items: [
          { text: 'Array Helpers', link: '/array-helpers' },
          { text: 'Collections', link: '/collections' },
          { text: 'Support Helpers', link: '/support-helpers' },
          { text: 'Localization', link: '/localization' },
          { text: 'Storage', link: '/storage' },
          { text: 'Session', link: '/session' },
          { text: 'Cache', link: '/cache' },
          { text: 'Events', link: '/events' },
          { text: 'Security', link: '/security' }
        ]
      },
      {
        text: 'Recipes',
        items: [
          { text: 'Middleware', link: '/middleware' },
          { text: 'Extending a Model', link: '/extending-a-model' },
          { text: 'Lazy Load Models', link: '/lazy-load-models' },
          { text: 'File Upload', link: '/file-upload' },
          { text: 'Sending Email', link: '/sending-email' },
          { text: 'Using Dot ENV', link: '/using-dot-env' },
          { text: 'Date Helpers', link: '/date-helpers' },
          { text: 'Custom View Engine', link: '/custom-view-engine' },
          { text: 'Custom ORM (Doctrine)', link: '/custom-orm' },
          { text: 'Custom ORM (Cycle)', link: '/cycle-orm' }
        ]
      }
    ],

    socialLinks: [
      { icon: 'github', link: 'https://github.com/im4aLL/roolith-framework' }
    ],

    search: {
      provider: 'local'
    },

    editLink: {
      pattern: 'https://github.com/im4aLL/roolith-framework/edit/next/documentation/docs/:path',
      text: 'Edit this page on GitHub'
    },

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © Roolith PHP Framework'
    }
  }
})
