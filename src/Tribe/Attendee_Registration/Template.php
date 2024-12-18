<?php
/**
 * Class Tribe__Tickets__Attendee_Registration__Template
 */
class Tribe__Tickets__Attendee_Registration__Template extends Tribe__Templates {

	/**
	 * Initialize the template class
	 */
	public function hook() {

		// Spoof the context.
		add_filter( 'the_posts', [ $this, 'setup_context' ], -10, 2 );

		// Set and remove the required body classes.
		add_action( 'wp', [ $this, 'set_body_classes' ] );

		/*
		 * Choose the theme template to use. It has to have a higher priority than the
		 * TEC filters (at 10) to ensure they do not usurp our rewrite here.
		 */
		add_filter( 'singular_template', [ $this, 'set_page_template' ], 15 );

		add_action( 'tribe_events_editor_assets_should_enqueue_frontend', [ $this, 'should_enqueue_frontend' ] );
		add_action( 'tribe_events_views_v2_assets_should_enqueue_frontend', [ $this, 'should_enqueue_frontend' ] );

		/*
		 * Set the content of the page. Again, it has to have a higher priority than the
		 * TEC filters (at 10) to ensure they do not usurp our rewrite here.
		 */
		add_action( 'loop_start', [ $this, 'set_page_content' ], 15 );

		// Modify the link for the edit post link.
		add_filter( 'edit_post_link', [ $this, 'set_edit_post_link' ] );

		// Switcheroo for Genesis using the excerpt as we're saying we're on an archive.
		add_filter( 'genesis_pre_get_option_content_archive', [ $this, 'override_genesis_archive' ], 10, 2 );
		// Also keep content limit from truncating the form
		add_filter( 'genesis_pre_get_option_content_archive_limit', [ $this, 'override_genesis_limit' ], 10, 2 );

		// Modify the page title.
		add_filter( 'document_title_parts', [ $this, 'modify_page_title' ], 1000 );
		add_filter( 'get_the_archive_title', [ $this, 'modify_archive_title' ], 1000 );
	}

	/**
	 * Setup the context
	 *
	 * @since 4.9
	 * @since 5.9.1 changed page parameters and added page to the cache.
	 * @since 5.17.0 Update the check for the custom AR page.
	 * @since 5.18.0 Added second parameter to the function to pass the query object. Bail if we're not on the main query.
	 *
	 * @param WP_Post[] $posts Post data objects.
	 *
	 * @return array
	 */
	public function setup_context( $posts, WP_Query $query ) {
		if ( ! $query->is_main_query() ) {
			// Only run for the main query and not any other possible sub queries!
			return $posts;
		}

		// Bail if we're not on the attendee info page.
		if ( ! $this->is_on_ar_page() ) {
			return $posts;
		}

		/*
		 * Early bail:
		 * We are on the AR page, and we have the shortcode in the content,
		 * so we don't want to spoof this page.
		 */
		if ( $this->is_on_custom_ar_page() ) {
			return $posts;
		}

		// Since we are spoofing the actual content of the page providing a fake post object, we need to remove the shortlink to prevent warnings.
		add_filter( 'pre_get_shortlink', '__return_empty_string' );

		// Empty posts.
		$posts = null;

		// Create a fake virtual page.
		$spoofed_page = $this->spoofed_page();
		$posts[]      = $spoofed_page;
		$wp_post      = new WP_Post( $spoofed_page );

		// Don't tell wp_query we're anything in particular - then we don't run into issues with defaults.
		$query->found_posts       = 1;
		$query->is_404            = false;
		$query->is_archive        = false;
		$query->is_category       = false;
		$query->is_home           = false;
		$query->is_page           = false;
		$query->is_singular       = true;
		$query->max_num_pages     = 1;
		$query->post              = $spoofed_page;
		$query->post_count        = 1;
		$query->posts             = [ $wp_post ];
		$query->queried_object    = $wp_post;
		$query->queried_object_id = $spoofed_page->ID;

		return $posts;
	}

	/**
	 * Returns whether or not the user is on a custom attendee registration page.
	 *
	 * @since 5.17.0
	 *
	 * @return bool
	 */
	public function is_on_custom_ar_page() {
		global $wp_query, $post;

		$ar_page_slug = tribe( 'tickets.attendee_registration' )->get_slug();

		// Check for custom AR page by page slug.
		$on_custom_page = ! empty( $wp_query->query_vars['pagename'] )
			&& $ar_page_slug === $wp_query->query_vars['pagename'];

		if ( ! $on_custom_page ) {
			return false;
		}

		$uses_shortcode = ! empty( $post->post_content )
			&& has_shortcode( $post->post_content, 'tribe_attendee_registration' );

		if ( $uses_shortcode ) {
			return true;
		}

		// If the post doesn't have the shortcode, check if the queried object does.
		$queried_object = get_queried_object();
		return ! empty( $queried_object->post_content )
			&& has_shortcode( $queried_object->post_content, 'tribe_attendee_registration' );
	}

	/**
	 * Convenience wrapper for tribe( 'tickets.attendee_registration' )->is_on_page() usage.
	 *
	 * @since 4.10.2
	 *
	 * @return boolean
	 */
	public function is_on_ar_page() {
		try {
			return tribe( 'tickets.attendee_registration' )->is_on_page();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Convenience wrapper for tribe( 'tickets.attendee_registration' )->is_using_shortcode() usage.
	 *
	 * @since 5.1.0
	 *
	 * @return bool Whether the Attendee Registration shortcode is being used.
	 */
	public function is_using_shortcode() {
		try {
			return tribe( 'tickets.attendee_registration' )->is_using_shortcode();
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Set the theme page template we're going to use for the attendee-registration page
	 *
	 * @since 4.9
	 * @since 5.17.0 Added check for custom AR page to return the pages template.
	 * @since 5.18.0 Changed the hook this is being fired to from `template_include` to `singular_template`. Made it compatible with block themes.
	 *
	 * @param string $template The AR template.
	 * @return void
	 */
	public function set_page_template( string $template ) {

		// Bail if we're not on the attendee info page.
		if ( ! $this->is_on_ar_page() ) {
			return $template;
		}

		if ( $this->is_on_custom_ar_page() ) {
			$template = get_page_template();
			return $template;
		}

		// Use the template option set in the admin.
		$template = tribe_get_option( 'ticket-attendee-info-template' );

		if ( empty( $template ) ) {
			// We should only get here if the value hasn't been set yet.
			$template = 'default';
		} elseif ( 'same' === $template ) {
			// Note this could be an empty string...because.
			$template = tribe_get_option( 'tribeEventsTemplate', 'default' );
		}

		if ( wp_is_block_theme() ) {
			// Archive events appears in the list of template for FSE themes but its not valid. So we ignore it to prevent customers from breaking their AR page.
			$template      = $template && ! in_array( trim( $template ), [ '', 'default', 'archive-events' ], true ) ? $template : 'page';
			$template_slug = str_replace( [ get_template_directory(), DIRECTORY_SEPARATOR ], '', $template );
			$template      = get_template_directory() . DIRECTORY_SEPARATOR . $template_slug . '.html';

			/**
			 * The params being passed to locate_block_template may not work for all the templates of all the themes out there.
			 *
			 * It is supposed to default to the templates/page.html if we fail to found the one specified though so keeping a working AR page.
			 */
			return locate_block_template( $template, 'page', [ $template_slug, 'page' ] );
		}

		if ( in_array( $template, [ '', 'default' ], true ) ) {
			// A bit of logic for themes without a page.php.
			$template = 'page.php';

			if ( ! locate_template( $template ) ) {
				$pages = array_keys( wp_get_theme()->get_page_templates() );

				if ( ! empty( $pages ) ) {
					$template = $pages[0];
				}
			}
		}

		// If template is not found, use default.
		if ( ! locate_template( $template ) ) {
			$template = 'index.php';
		}

		$template = locate_template( $template );

		/**
		 * Use `tribe_tickets_attendee_registration_page_template` to modify the attendee registration page template.
		 *
		 * @since 4.10.1
		 *
		 * @param string $template The current attendee registration page template.
		 */
		$template = apply_filters( 'tribe_tickets_attendee_registration_page_template', $template );

		return $template;
	}

	/**
	 * Ensure we enqueue the frontend styles and scripts from our plugins on the AR page.
	 *
	 * @since 4.11.3
	 *
	 * @param boolean $enqueue A boolean containing if we should enqueue.
	 * @return boolean Whether we should enqueue frontend styles and scripts.
	 */
	public function should_enqueue_frontend( $enqueue ) {
		if ( $this->is_on_ar_page() ) {
			return true;
		}

		return $enqueue;
	}

	/**
	 * Add and remove body classes.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function set_body_classes() {
		// Bail if we're not on the attendee info page.
		if ( ! $this->is_on_ar_page() ) {
			return;
		}

		// Remove classes that we don't want/need.
		add_filter( 'body_class', [ $this, 'remove_body_classes' ] );

		// Add classes that we actually want/need.
		add_filter( 'body_class', [ $this, 'add_body_classes' ] );
	}

	/**
	 * Remove body classes.
	 *
	 * @since 4.9
	 * @param array $classes List of classes to filter.
	 *
	 * @return array $classes Array of classes.
	 */
	public function remove_body_classes( $classes ) {

		// body classes to remove.
		$remove = [ 'singular', 'home', 'blog', 'page-template-page-php', 'has-sidebar' ];

		foreach ( $remove as $index => $class ) {
			$key = array_search( $class, $classes );

			if ( false !== $key ) {
				unset( $classes[ $key ] );
			}
		}

		return $classes;
	}

	/**
	 * Add the required body classes
	 *
	 * @since 4.9
	 * @param array $classes List of classes to filter.
	 *
	 * @return array $classes
	 */
	public function add_body_classes( $classes ) {

		$classes[] = 'page-tribe-attendee-registration';
		$classes[] = 'page-one-column';

		if ( $this->is_using_shortcode()  ) {
			$classes[] = 'page-tribe-attendee-registration--shortcode';
		}

		return $classes;
	}

	/**
	 * Add the theme to the body class, in order to
	 * add compatibility for official themes.
	 *
	 * @since 4.9
	 * @param array $classes List of classes to filter.
	 * @deprecated 4.11.4
	 *
	 * @return array $classes
	 */
	public function theme_body_class( $classes ) {
		return $classes;
	}

	/**
	 * Checks if theme needs a compatibility fix
	 *
	 * @since 4.9
	 * @param string $theme Name of template from WP_Theme->Template, defaults to current active template.
	 * @deprecated 4.11.4
	 *
	 * @return mixed
	 */
	public function theme_has_compatibility_fix( $theme = null ) {
		return false;
	}

	/**
	 * This is where the magic happens where we run some ninja code that hooks
	 * the query to resolve to an events template.
	 *
	 * @since 4.9
	 * @param WP_Query $query The WordPress query.
	 */
	public function set_page_content( $query ) {
		// Bail if we're not on the attendee info page.
		if ( ! $this->is_on_ar_page() ) {
			return;
		}

		if ( $this->is_main_loop( $query ) ) {
			global $post;
			if ( $post instanceof WP_Post && has_shortcode( $post->post_content, 'tribe_attendee_registration' ) ) {
				// Early bail: There's no need to override the content if the post is using the shortcode.
				return;
			}

			// Prevent the TEC v2 page override from preventing our content override.
			add_filter( 'tribe_events_views_v2_should_hijack_page_template', '__return_false' );

			// Load Attendee Registration view for the content.
			add_filter( 'the_content', tribe_callback( 'tickets-plus.attendee-registration.view', 'get_page_content' ) );
		}
	}

	/**
	 * Hooks into the genesis excerpt filter and forces it "off" on the AR page
	 *
	 * @param [string] (null) $unused_null string for value
	 * @param [type] $unused_setting
	 *
	 * @return string|null
	 */
	public function override_genesis_archive( $unused_null, $unused_setting ) {
		// Bail if we're not on the attendee info page.
		if ( ! $this->is_on_ar_page() ) {
			return null;
		}

		return 'full';
	}

	/**
	 * Hooks into the genesis excerpt filter and forces it "off" on the AR page
	 *
	 * @since 4.10.9 - Return null if not on ar page and true if on ar page.
	 *
	 * @param string|null $unused_null Unused variable.
	 * @param string $setting
	 *
	 * @return null|string
	 */
	public function override_genesis_limit( $unused_null, $setting ) {
		// Bail if we're not on the attendee info page.
		if ( ! $this->is_on_ar_page() ) {
			return null;
		}

		// Return empty string on AR to get no content.
		return '';
	}

	/**
	 * Modify the document title
	 *
	 * @since 4.9
	 * @param string $title The page title.
	 *
	 * @return array
	 */
	public function modify_page_title( $title ) {

		// When in the loop, no need to override titles.
		if ( in_the_loop() ) {
			return $title;
		}

		if ( $this->is_on_ar_page() ) {
			$title['title'] = $this->get_page_title();
		}

		// Return the title.
		return $title;
	}

	/**
	 * Modify the archive title - for themes that somehow defeat our earlier hook.
	 *
	 * @since 4.10.2
	 * @param string $title The archive page title.
	 *
	 * @return string
	 */
	public function modify_archive_title( $title ) {
		if ( $this->is_on_ar_page() ) {
			$title = $this->get_page_title();
		}

		// Return the title.
		return $title;
	}

	/**
	 * Return the Attendee Registration page title
	 *
	 * @since 4.9
	 *
	 * @return string
	 */
	public function get_page_title() {
		$title = __( 'Attendee Registration', 'event-tickets' );
		try {
			$page  = tribe( 'tickets.attendee_registration' )->get_attendee_registration_page();
		} catch( Exception $e ) {
			$page = null;
		}

		$title = $page ? $page->post_title : $title;

		/**
		 * `tribe_tickets_attendee_registration_page_title`
		 * Filters the attendee registration page title
		 *
		 * @param string the "Attendee Registration" page title.
		 */
		return apply_filters( 'tribe_tickets_attendee_registration_page_title', $title );
	}

	/**
	 * Create a fake page we'll use to hijack our attendee info page
	 *
	 * @since 4.9
	 *
	 * @return obj
	 */
	public function spoofed_page() {

		$spoofed_page = [
			'ID'                    => -1,
			'post_status'           => 'draft',
			'post_author'           => 1,
			'post_parent'           => 0,
			'post_type'             => 'page',
			'post_date'             => 0,
			'post_date_gmt'         => 0,
			'post_modified'         => 0,
			'post_modified_gmt'     => 0,
			'post_content'          => '',
			'post_title'            => $this->get_page_title(),
			'post_excerpt'          => '',
			'post_content_filtered' => '',
			'post_mime_type'        => '',
			'post_password'         => '',
			'post_name'             => '',
			'guid'                  => '',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'ping_status'           => '',
			'comment_status'        => 'closed',
			'comment_count'         => 0,
			'filter'                => 'raw',
		];

		return (object) $spoofed_page;
	}

	/**
	 * Hijack the edit post link for our fake page
	 *
	 * @since 4.9
	 *
	 * @param string $link The edit post link.
	 *
	 * @return string The edit post link or blank if on the AR page.
	 */
	public function set_edit_post_link( $link ) {

		// Bail if we're not on the attendee info page.
		if ( ! $this->is_on_ar_page() ) {
			return $link;
		}

		return '';
	}
}
