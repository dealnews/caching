<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * Function for helping debug tests since modern PHP Unit
 * does not allow var_dump to send output to STDOUT.
 */
function _debug() {
    $bt = debug_backtrace();
    fwrite(STDERR, "\nSTART DEBUG\n");
    foreach ($bt as $pos => $t) {
        if (!isset($t['file'])) {
            break;
        }
        fwrite(STDERR, "#{$pos} {$t['file']} on line {$t['line']}\n");
    }
    fwrite(STDERR, "###########\n");
    $args = func_get_args();
    foreach ($args as $arg) {
        fwrite(STDERR, trim(var_export($arg, true))."\n");
    }
    fwrite(STDERR, "###########\n");
    fwrite(STDERR, "END DEBUG\n\n");
}

// Check if we are running inside a docker container already
// If so, set the env vars correctly and don't run setup/teardown
if ('' == shell_exec('which docker')) {
    $memcache_host = 'memcached-sandbox';
    $redis_host = 'redis-sandbox';
} else {
    $memcache_host = '127.0.0.1';
    $redis_host = '127.0.0.1';

    $start_sandbox = true;

    $opts = getopt('', ['group:']);
    if (!empty($opts['group'])) {
        $groups = explode(',', $opts['group']);
        if (!in_array('functional', $groups)) {
            $start_sandbox = false;
        }
    }

    if ($start_sandbox) {
        // Start daemons for testing.
        passthru(__DIR__.'/setup.sh');

        register_shutdown_function(function () {
            if (empty(getenv('KEEPCONTAINERS'))) {
                passthru(__DIR__.'/teardown.sh');
            }
        });
    }
}

putenv("CACHING_MEMCACHE_TEST_SERVERS={$memcache_host}:11211");
putenv("CACHING_REDIS_TEST_SERVERS={$redis_host}");
