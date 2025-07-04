<?php
/**
 * Template caching functionality for ticket price templates.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cost_Cache
 */

namespace TEC\Tickets\Cost_Cache;

/**
 * Class Template_Cache
 *
 * Handles caching of rendered ticket price templates.
 *
 * @since TBD
 *
 * @package TEC\Tickets\Cost_Cache
 */
class Template_Cache {

	/**
	 * Meta key prefix for cached templates.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	const META_KEY_PREFIX = '_tec_cached_template_';

	/**
	 * List of templates that should be cached.
	 *
	 * @since TBD
	 *
	 * @var array
	 */
	protected $cacheable_templates = [
		'blocks/tickets/extra-price',
		'blocks/tickets/footer-total',
		'v2/commerce/ticket/price',
		'v2/commerce/ticket/regular-price',
		'v2/commerce/ticket/sale-price',
		'v2/commerce/checkout/cart/item/price',
		'registration/summary/ticket/price',
	];

	/**
	 * Get cached template output.
	 *
	 * @since TBD
	 *
	 * @param string|array $template_path The template path or name array.
	 * @param array        $variables     The template variables.
	 * @param int          $event_id      The event ID.
	 *
	 * @return string|false The cached output or false if not cached.
	 */
	public function get( $template_path, array $variables, $event_id ) {
		if ( ! $this->should_cache_template( $template_path ) ) {
			return false;
		}

		if ( ! is_numeric( $event_id ) ) {
			return false;
		}

		$cache_key = $this->get_cache_key( $template_path, $variables );
		$meta_key  = self::META_KEY_PREFIX . $cache_key;

		// Check if the meta exists.
		$meta_exists = metadata_exists( 'post', $event_id, $meta_key );

		if ( ! $meta_exists ) {
			return false;
		}

		return get_post_meta( $event_id, $meta_key, true );
	}

	/**
	 * Set cached template output.
	 *
	 * @since TBD
	 *
	 * @param string|array $template_path The template path or name array.
	 * @param array        $variables     The template variables.
	 * @param int          $event_id      The event ID.
	 * @param string       $output        The rendered output to cache.
	 *
	 * @return bool Whether the cache was set.
	 */
	public function set( $template_path, array $variables, $event_id, $output ) {
		if ( ! $this->should_cache_template( $template_path ) ) {
			return false;
		}

		if ( ! is_numeric( $event_id ) ) {
			return false;
		}

		$cache_key = $this->get_cache_key( $template_path, $variables );
		$meta_key  = self::META_KEY_PREFIX . $cache_key;

		return (bool) update_post_meta( $event_id, $meta_key, $output );
	}

	/**
	 * Clear cached templates for an event.
	 *
	 * @since TBD
	 *
	 * @param int $event_id The event ID.
	 */
	public function clear( $event_id ) {
		if ( ! is_numeric( $event_id ) ) {
			return;
		}

		// Get all meta keys for this event that match our prefix.
		$meta_keys = get_post_meta( $event_id );
		
		if ( ! empty( $meta_keys ) ) {
			foreach ( $meta_keys as $key => $value ) {
				if ( 0 === strpos( $key, self::META_KEY_PREFIX ) ) {
					delete_post_meta( $event_id, $key );
				}
			}
		}
	}

	/**
	 * Clear all template caches.
	 *
	 * @since TBD
	 */
	public function clear_all() {
		// Get all events that might have cached templates.
		$events = tribe_events()
			->where( 'meta_like', self::META_KEY_PREFIX )
			->fields( 'ids' )
			->per_page( -1 )
			->found();

		// Clear cache for each event.
		foreach ( $events as $event_id ) {
			$this->clear( $event_id );
		}

		do_action( 'tec_tickets_template_cache_cleared_all' );
	}

	/**
	 * Check if a template should be cached.
	 *
	 * @since TBD
	 *
	 * @param string|array $template_path The template path or name array.
	 *
	 * @return bool Whether the template should be cached.
	 */
	protected function should_cache_template( $template_path ) {
		// Normalize the template path.
		$template_path = $this->normalize_template_path( $template_path );

		/**
		 * Filter the list of cacheable templates.
		 *
		 * @since TBD
		 *
		 * @param array  $cacheable_templates List of template paths that should be cached.
		 * @param string $template_path       The current template path.
		 */
		$cacheable_templates = apply_filters( 'tec_tickets_cacheable_templates', $this->cacheable_templates, $template_path );

		return in_array( $template_path, $cacheable_templates, true );
	}

	/**
	 * Normalize a template path.
	 *
	 * @since TBD
	 *
	 * @param string|array $template_path The template path.
	 *
	 * @return string The normalized path.
	 */
	protected function normalize_template_path( $template_path ) {
		// Handle array input (template name might be an array).
		if ( is_array( $template_path ) ) {
			// Join array elements with slash.
			$template_path = implode( '/', array_filter( $template_path ) );
		}

		// Ensure we have a string.
		$template_path = (string) $template_path;

		// Remove .php extension if present.
		$template_path = str_replace( '.php', '', $template_path );

		// Remove leading slashes.
		$template_path = ltrim( $template_path, '/' );

		// Remove common prefixes.
		$prefixes = [ 'tribe/tickets/', 'tickets/', 'views/' ];
		foreach ( $prefixes as $prefix ) {
			if ( 0 === strpos( $template_path, $prefix ) ) {
				$template_path = substr( $template_path, strlen( $prefix ) );
				break;
			}
		}

		return $template_path;
	}

	/**
	 * Generate a cache key based on template and variables.
	 *
	 * @since TBD
	 *
	 * @param string|array $template_path The template path or name array.
	 * @param array        $variables     The template variables.
	 *
	 * @return string The cache key.
	 */
	protected function get_cache_key( $template_path, array $variables ) {
		$template_path = $this->normalize_template_path( $template_path );

		// Extract key variables that affect output.
		$key_vars = $this->extract_key_variables( $template_path, $variables );

		// Create a deterministic key.
		$cache_data = [
			'template' => $template_path,
			'vars'     => $key_vars,
		];

		return md5( wp_json_encode( $cache_data ) );
	}

	/**
	 * Extract key variables that affect template output.
	 *
	 * @since TBD
	 *
	 * @param string $template_path The template path.
	 * @param array  $variables     All template variables.
	 *
	 * @return array Key variables that affect output.
	 */
	protected function extract_key_variables( $template_path, array $variables ) {
		$key_vars = [];

		// Common variables that affect most templates.
		$common_keys = [ 'ticket_id', 'post_id', 'provider_class' ];

		// Template-specific variable extraction.
		switch ( $template_path ) {
			case 'blocks/tickets/extra-price':
			case 'v2/commerce/ticket/price':
				if ( isset( $variables['ticket'] ) && is_object( $variables['ticket'] ) ) {
					$ticket                    = $variables['ticket'];
					$key_vars['ticket_id']     = $ticket->ID;
					$key_vars['price']         = $ticket->price;
					$key_vars['regular_price'] = $ticket->regular_price ?? null;
					$key_vars['on_sale']       = ! empty( $ticket->on_sale );
					$key_vars['price_suffix']  = $ticket->price_suffix ?? '';
				}
				break;

			case 'v2/commerce/checkout/cart/item/price':
				if ( isset( $variables['item'] ) ) {
					$item = $variables['item'];
					if ( isset( $item['ticket_id'] ) ) {
						$key_vars['ticket_id'] = $item['ticket_id'];
					}
					if ( isset( $item['quantity'] ) ) {
						$key_vars['quantity'] = $item['quantity'];
					}
					if ( isset( $item['price'] ) ) {
						$key_vars['price'] = $item['price'];
					}
				}
				break;

			case 'blocks/tickets/footer-total':
				if ( isset( $variables['tickets'] ) && is_array( $variables['tickets'] ) ) {
					// For footer total, we need all ticket IDs and quantities.
					$key_vars['tickets'] = [];
					foreach ( $variables['tickets'] as $ticket ) {
						if ( is_object( $ticket ) && isset( $ticket->ID ) ) {
							$key_vars['tickets'][] = [
								'id'    => $ticket->ID,
								'price' => $ticket->price,
							];
						}
					}
				}
				break;
		}

		// Add common variables if they exist.
		foreach ( $common_keys as $key ) {
			if ( isset( $variables[ $key ] ) ) {
				$key_vars[ $key ] = $variables[ $key ];
			}
		}

		// Add currency settings if they affect output.
		if ( isset( $variables['currency_symbol'] ) ) {
			$key_vars['currency_symbol'] = $variables['currency_symbol'];
		}
		if ( isset( $variables['currency_position'] ) ) {
			$key_vars['currency_position'] = $variables['currency_position'];
		}

		/**
		 * Filter the key variables used for cache key generation.
		 *
		 * @since TBD
		 *
		 * @param array  $key_vars      The extracted key variables.
		 * @param string $template_path The template path.
		 * @param array  $variables     All template variables.
		 */
		return apply_filters( 'tec_tickets_template_cache_key_vars', $key_vars, $template_path, $variables );
	}

	/**
	 * Check if template caching is enabled.
	 *
	 * @since TBD
	 *
	 * @return bool Whether template caching is enabled.
	 */
	public function is_enabled() {
		/**
		 * Filter whether template caching is enabled.
		 *
		 * @since TBD
		 *
		 * @param bool $enabled Whether template caching is enabled. Default true.
		 */
		return apply_filters( 'tec_tickets_enable_template_cache', true );
	}
}
