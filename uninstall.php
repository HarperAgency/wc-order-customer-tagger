<?php
/**
 * Uninstall hook — runs when the plugin is deleted from WordPress admin.
 *
 * Drops all harper_tagger_* tables and removes plugin options.
 * Only executes during a real WP uninstall request.
 */
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Bootstrap autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/includes/Db/Schema.php';
}

HarperAgency\WCTagger\Db\Schema::uninstall();
