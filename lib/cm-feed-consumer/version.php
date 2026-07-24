<?php
/**
 * Entry point for bundled use. Guards against duplicate loading across modules.
 *
 * @package ClarityMomentum\FeedConsumer
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'CM_FEED_CONSUMER_VERSION' ) ) {
	define( 'CM_FEED_CONSUMER_VERSION', '1.0.0' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- bundled library constant
	require_once __DIR__ . '/class-cm-feed-consumer.php';
	require_once __DIR__ . '/class-cm-feed-notice.php';
	require_once __DIR__ . '/class-cm-feed-scheduler.php';
}
