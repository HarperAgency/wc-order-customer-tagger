<?php
/**
 * PHPUnit bootstrap for Harper Order & Customer Tagger unit tests.
 * Does NOT load WordPress or WooCommerce — the unit tests provide their own stubs.
 *
 * RuleEngine classes (Condition, Rule, Engine) are pure PHP and need NO stubs.
 * ContextBuilder stubs are provided below for completeness, but ContextBuilder
 * is not exercised in the pure-unit test suite.
 */
declare(strict_types=1);

// WordPress guard constant
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

require_once dirname(__DIR__) . '/vendor/autoload.php';

// ── Minimal WordPress / WooCommerce function stubs ────────────────────────────

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string { return $text; }
}
if (!function_exists('add_action')) {
    function add_action(string $hook, mixed $callback, int $priority = 10, int $args = 1): void {}
}
if (!function_exists('add_filter')) {
    function add_filter(string $hook, mixed $callback, int $priority = 10, int $args = 1): void {}
}
if (!function_exists('get_the_terms')) {
    function get_the_terms(int $id, string $taxonomy): array { return []; }
}
if (!function_exists('wp_get_object_terms')) {
    function wp_get_object_terms(int $id, string $taxonomy, array $args = []): array { return []; }
}
if (!function_exists('is_array')) {
    // built-in; never actually missing — here for documentation only
}

// ── WordPress DB stub (for PHPUnit mock generation) ──────────────────────────

if (!class_exists('wpdb')) {
    class wpdb
    {
        public string $prefix    = 'wp_';
        public int    $insert_id = 0;

        public function get_charset_collate(): string { return ''; }
        public function prepare(string $query, mixed ...$args): string { return $query; }
        public function get_results(string $query, string $output = 'OBJECT'): array { return []; }
        public function get_row(string $query, string $output = 'OBJECT'): mixed { return null; }
        public function get_var(string $query): mixed { return null; }
        public function insert(string $table, array $data): int|false { return 1; }
        public function update(string $table, array $data, array $where): int|false { return 1; }
        public function delete(string $table, array $where): int|false { return 1; }
        public function query(string $query): int|bool { return true; }
    }
}

// ── WordPress option / transient stubs ────────────────────────────────────────

if (!function_exists('get_option')) {
    function get_option(string $key, mixed $default = false): mixed { return $default; }
}
if (!function_exists('update_option')) {
    function update_option(string $key, mixed $value): bool { return true; }
}
if (!function_exists('delete_option')) {
    function delete_option(string $key): bool { return true; }
}

// ── Minimal WooCommerce class stubs ───────────────────────────────────────────

if (!class_exists('WC_Order')) {
    class WC_Order
    {
        public function get_total(): float       { return 0.0; }
        public function get_items(string $type = 'line_item'): array { return []; }
        public function get_payment_method(): string    { return ''; }
        public function get_shipping_country(): string  { return ''; }
        public function get_billing_country(): string   { return ''; }
    }
}

if (!class_exists('WC_Customer')) {
    class WC_Customer
    {
        public function get_id(): int            { return 0; }
        public function get_total_spent(): float { return 0.0; }
        public function get_order_count(): int   { return 0; }
    }
}
