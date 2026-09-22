<?php
namespace {

/**
 * WordPress and WP-CLI symbols used only to make PHPStan aware of the
 * conditional command integration. This file is never loaded by the plugin.
 *
 * @phpstan-ignore-file
 */

class WP_Query {
	/** @var array<int,WP_Post> */
	public $posts = array();

	/** @param array<string,mixed> $args */
	public function __construct( array $args = array() ) {}

	public function have_posts(): bool {
		return false;
	}
}

class WP_Post {
	/** @var int */
	public $ID = 0;

	/** @var string */
	public $post_title = '';

	/** @var string */
	public $post_status = '';
}

class WP_CLI {
	public static function success( string $message ): void {}

	public static function error( string $message ): void {}

	/** @param callable|string $callable */
	public static function add_command( string $name, $callable ): void {}
}

class wpdb {
	/** @var string */
	public $options = '';

	public function esc_like( string $text ): string {
		return $text;
	}

	/** @param mixed ...$args */
	public function prepare( string $query, ...$args ): string {
		return $query;
	}

	/** @return int|false */
	public function query( string $query ) {
		return 0;
	}
}

}

namespace WP_CLI\Utils {

/**
 * @param array<int,array<string,string>> $items
 * @param array<int,string> $fields
 */
function format_items( string $format, array $items, array $fields ): void {}

}
