<?php
// Command-line alternative to the web cron URL.
// Hostinger → Advanced → Cron Jobs → Custom: /usr/bin/php /home/uXXXX/domains/thegiftboxx.com/cron/run.php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}
require dirname(__DIR__) . '/app/bootstrap.php';
if (!app_installed()) {
    exit("Not installed yet.\n");
}
echo run_cron();
