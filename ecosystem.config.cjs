module.exports = {
  apps: [
    // HTTP production dilayani Nginx/Caddy + PHP-FPM, bukan `artisan serve`.
    // PM2 hanya mengelola proses Laravel yang berjalan terus-menerus.

    // ─── Queue Worker ─────────────────────────────────────────────
    {
      name: 'xd-radius-queue',
      script: 'php',
      args: 'artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-jobs=1000 --max-time=3600',
      cwd: '/root/main-app/xd-radius',
      autorestart: true,
      watch: false,
      max_restarts: 10,
      restart_delay: 3000,
      kill_timeout: 90000,
      max_memory_restart: '256M',
      time: true,
      env: {
        APP_ENV: 'production',
        APP_DEBUG: 'false',
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
      kill_timeout: 90000,
      max_memory_restart: '256M',
      time: true,
      env: {
        APP_ENV: 'production',
        APP_DEBUG: 'false',
      },
    },
  ],
};
