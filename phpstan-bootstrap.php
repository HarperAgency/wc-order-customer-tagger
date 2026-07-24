<?php
/**
 * PHPStan bootstrap for CM Order & Customer Tagger.
 *
 * Provides WordPress function and constant stubs only. WooCommerce class
 * definitions are supplied via the woocommerce-stubs stubFiles entry in
 * phpstan.neon so they are NOT defined here.
 *
 * @package ClarityMomentum\WCTagger
 */
declare(strict_types=1);

// WordPress guard constant.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

// WordPress DB result-type constants.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

require_once __DIR__ . '/vendor/autoload.php';

// ── Minimal WordPress function stubs ─────────────────────────────────────────

if ( ! function_exists( '__' ) ) {
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( string $url ): string {
		return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( string $text, string $domain = 'default' ): void {
		echo htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( string $hook, mixed $callback, int $priority = 10, int $args = 1 ): void {}
}
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( string $hook, mixed $callback, int $priority = 10, int $args = 1 ): void {}
}
if ( ! function_exists( 'get_the_terms' ) ) {
	function get_the_terms( int $id, string $taxonomy ): array {
		return array();
	}
}
if ( ! function_exists( 'wp_get_object_terms' ) ) {
	function wp_get_object_terms( int $id, string $taxonomy, array $args = array() ): array {
		return array();
	}
}

// ── WordPress DB stub ─────────────────────────────────────────────────────────

if ( ! class_exists( 'wpdb' ) ) {
	class wpdb {
		public string $prefix    = 'wp_';
		public int    $insert_id = 0;

		public function get_charset_collate(): string { return ''; }
		public function prepare( string $query, mixed ...$args ): string { return $query; }
		public function get_results( string $query, string $output = 'OBJECT' ): array { return array(); }
		public function get_row( string $query, string $output = 'OBJECT' ): mixed { return null; }
		public function get_var( string $query ): mixed { return null; }
		public function insert( string $table, array $data ): int|false { return 1; }
		public function update( string $table, array $data, array $where ): int|false { return 1; }
		public function delete( string $table, array $where ): int|false { return 1; }
		public function query( string $query ): int|bool { return true; }
	}
}

// ── WordPress option / AJAX / asset stubs ────────────────────────────────────

if ( ! function_exists( 'wp_create_nonce' ) ) {
	function wp_create_nonce( string $action ): string { return 'test_nonce'; }
}
if ( ! function_exists( 'check_ajax_referer' ) ) {
	function check_ajax_referer( string $action, mixed $queryArg = false, bool $die = true ): int { return 1; }
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( string $cap ): bool { return true; }
}
if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( mixed $data = null, int $statusCode = 200 ): void {}
}
if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( mixed $data = null, int $statusCode = 200 ): void {}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( string $path = '' ): string { return 'http://example.com/wp-admin/' . $path; }
}
if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( string $handle, string $src = '', array $deps = array(), mixed $ver = false, bool $inFooter = false ): void {}
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( string $handle, string $src = '', array $deps = array(), mixed $ver = false, string $media = 'all' ): void {}
}
if ( ! function_exists( 'wp_localize_script' ) ) {
	function wp_localize_script( string $handle, string $name, array $data ): bool { return true; }
}
if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( string $text, string $domain = 'default' ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}
if ( ! function_exists( 'get_option' ) ) {
	function get_option( string $key, mixed $default = false ): mixed { return $default; }
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( string $key, mixed $value ): bool { return true; }
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( string $key ): bool { return true; }
}
if ( ! function_exists( 'get_userdata' ) ) {
	function get_userdata( int $userId ): object|false {
		return (object) array( 'user_registered' => '2020-01-01 00:00:00' );
	}
}
if ( ! function_exists( 'absint' ) ) {
	function absint( mixed $maybeint ): int { return abs( (int) $maybeint ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( mixed $data, int $options = 0, int $depth = 512 ): string|false {
		return json_encode( $data, $options, $depth );
	}
}
if ( ! function_exists( 'selected' ) ) {
	function selected( mixed $selected, mixed $current = true, bool $echo = true ): string {
		$result = $selected == $current ? ' selected="selected"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}
