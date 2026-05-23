module.exports = {
  apps: [
    // ─── Web Server ───────────────────────────────────────────────
    {
      name: 'xd-radius-app',
      script: 'php',
      args: 'artisan serve --host=0.0.0.0 --port=8000',
      cwd: '/root/main-app/xd-radius',
      autorestart: true,
      watch: false,
      max_restarts: 10,
      restart_delay: 3000,
      env: {
        APP_ENV: 'production',
      },
    },

    // ─── Queue Worker ─────────────────────────────────────────────
    {
      name: 'xd-radius-queue',
      script: 'php',
      args: 'artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-jobs=1000',
      cwd: '/root/main-app/xd-radius',
      autorestart: true,
      watch: false,
      max_restarts: 10,
      restart_delay: 3000,
      env: {
        APP_ENV: 'production',
      },
    },

    // ─── Scheduler ────────────────────────────────────────────────
    {
      name: 'xd-radius-scheduler',
      script: 'php',
      args: 'artisan schedule:work',
      cwd: '/root/main-app/xd-radius',
      autorestart: true,
      watch: false,
      max_restarts: 10,
      restart_delay: 3000,
      env: {
        APP_ENV: 'production',
      },
    },
  ],
};
