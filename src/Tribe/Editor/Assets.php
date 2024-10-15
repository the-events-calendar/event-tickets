<?php
/**
 * Events Gutenberg Assets
 *
 * @since 4.9
 */
class Tribe__Tickets__Editor__Assets {
	/**
	 * Registers and Enqueues the assets
	 *
	 * @since 4.9
	 */
	public function register() {
		$plugin = Tribe__Tickets__Main::instance();

		// A minimal set of Babel transpilers for commonly used JavaScript features.
		tribe_asset(
			$plugin,
			'tec-tickets-vendor-babel',
			'app/vendor-babel.js'
		);

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-vendor',
			'app/vendor.js',
			[
				'tec-tickets-vendor-babel',
				'react',
				'react-dom',
				'thickbox',
				'wp-components',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
				'wp-block-editor'
			],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'localize'     => [],
				'conditionals' => tribe_callback( 'tickets.editor', 'current_post_supports_tickets' ),
				'priority'     => 200,
			]
		);

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-main',
			'app/main.js',
			/**
			 * @todo revise this dependencies
			 */
			[],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'localize'     => [],
				'conditionals' => tribe_callback( 'tickets.editor', 'current_post_supports_tickets' ),
				'priority'     => 201,
			]
		);

		tribe_asset(
			$plugin,
			'tribe-tickets-gutenberg-main-styles',
			'app/main.css',
			[ 'tribe-common-full-style' ],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'localize'     => [],
				'conditionals' => tribe_callback( 'tickets.editor', 'current_post_supports_tickets' ),
				'priority'     => 15,
			]
		);

		tribe_asset(
			$plugin,
			'tec-tickets-blocks-category-icon-styles',
			'tickets-admin-blocks.css',
			[],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'localize'     => [],
				'conditionals' => tribe_callback( 'tickets.editor', 'current_post_supports_tickets' ),
				'priority'     => 16,
			]
		);
	}
}
