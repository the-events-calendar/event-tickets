<?php
/**
 * Class Tribe__Tickets__Attendee_Registration__Template
 */
class Tribe__Tickets__Attendee_Registration__Template extends Tribe__Templates {

	/*
	 * List of themes which we may want to include fixes
	 */
	public $themes_with_compatibility_fixes = array(
		'twentynineteen',
		'twentyseventeen',
		'twentysixteen',
		'twentyfifteen',
	);

	/**
	 * Initialize the template class
	 */
	public function hook() {

		// Spoof the context
		add_filter( 'the_posts', array( $this, 'setup_context' ), -10 );

		// Set and remove the required body classes
		add_action( 'wp', array( $this, 'set_body_classes' ) );

		// Choose the wordpress theme template to use
		add_filter( 'template_include', array( $this, 'set_page_template' ) );

		// Set the content of the page
		add_action( 'loop_start', array( $this, 'set_page_content' ) );

		// Modify the link for the edit post link
		add_filter( 'edit_post_link', array( $this, 'set_edit_post_link' ) );

		//switcheroo for tempaltes that force us to use the excerpt as we're saying we're on an archive
		add_filter( 'the_excerpt', array( $this, 'set_page_excerpt' ) );

		// Modify the page title
		add_filter( 'document_title_parts', array( $this, 'modify_page_title' ), 1000 );
	}

	/**
	 * Setup the context
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function setup_context( $posts ) {
		global $wp, $wp_query;

		// Bail if we're not on the attendee info page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $posts;
		}

		// Empty posts
		$posts = null;
		// Create a fake virtual page
		$posts[] = $this->spoofed_page();

		// Set it as an archive page so it doesn't give the edit link
		// nor it loads the comments template
		$wp_query->is_page     = false;
		$wp_query->is_singular = false;
		$wp_query->is_home     = false;
		$wp_query->is_archive  = true;
		$wp_query->is_category = false;
		$wp_query->is_404      = false;
		$wp_query->found_posts = 1;
		$wp_query->posts_per_page = 1;

		return $posts;

	}

	/**
	 * Set the theme page template as the
	 * template we're gonna use for the attendee-registration
	 * page
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function set_page_template( $template ) {

		// Bail if we're not on the attendee info page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $template;
		}

		// return the page template
		$template = get_page_template();
		if ( ! empty( $template ) ) {
			return $template;
		}

		// Fallback for themes that are missing page.php
		return get_template_directory() . '/index.php';
	}

	/**
	 * Add and remove body classes.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function set_body_classes() {
		// Bail if we're not on the attendee info page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return;
		}

		// Remove classes that we don't want/need
		add_filter( 'body_class', array( $this, 'remove_body_classes' ) );

		// Add classes that we actually want/need
		add_filter( 'body_class', array( $this, 'add_body_classes' ) );

		// add the theme name to the body class when needed
		if ( $this->theme_has_compatibility_fix() ) {
			add_filter( 'body_class', array( $this, 'theme_body_class' ) );
		}
	}

	/**
	 * Remove body classes.
	 *
	 * @since 4.9
	 * @param array $classes List of classes to filter
	 *
	 * @return array $classes
	 */
	public function remove_body_classes( $classes ) {

		// body classes to remove
		$remove = array( 'singular', 'home', 'blog', 'page-template-page-php', 'has-sidebar' );

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
	 * @param array $classes List of classes to filter
	 *
	 * @return array $classes
	 */
	public function add_body_classes( $classes ) {

		$classes[] = 'page-tribe-attendee-registration';
		$classes[] = 'page-one-column';

		return $classes;
	}

	/**
	 * Add the theme to the body class, in order to
	 * add compatibility for official themes.
	 *
	 * @since 4.9
	 * @param array $classes List of classes to filter
	 *
	 * @return array $classes
	 */
	public function theme_body_class( $classes ) {

		$child_theme  = get_option( 'stylesheet' );
		$parent_theme = get_option( 'template' );

		// if the 2 options are the same, then there is no child theme
		if ( $child_theme == $parent_theme ) {
			$child_theme = false;
		}

		if ( $child_theme ) {
			$theme_classes = "tribe-theme-parent-$parent_theme tribe-theme-child-$child_theme";
		} else {
			$theme_classes = "tribe-theme-$parent_theme";
		}

		$classes[] = $theme_classes;

		return $classes;
	}


	/**
	 * Checks if theme needs a compatibility fix
	 *
	 * @since 4.9
	 * @param string $theme Name of template from WP_Theme->Template, defaults to current active template
	 *
	 * @return mixed
	 */
	public function theme_has_compatibility_fix( $theme = null ) {
		// Defaults to current active theme
		if ( null === $theme ) {
			$theme = get_stylesheet();
		}

		// Return if the current theme is part of the ones we've compatibility for
		return in_array( $theme, $this->themes_with_compatibility_fixes );
	}

	/**
	 * This is where the magic happens where we run some ninja code that hooks
	 * the query to resolve to an events template.
	 *
	 * @since 4.9
	 * @param WP_Query $query
	 */
	public function set_page_content( $query ) {
		// Bail if we're not on the attendee info page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return;
		}

		if ( $this->is_main_loop( $query ) ) {
			// on the_content, load our attendee info page
			add_filter( 'the_content', array( tribe( 'tickets.attendee_registration.view' ), 'display_attendee_registration_page' ) );
		}
	}

	public function set_page_excerpt( $post_excerpt ) {

		// Bail if we're not on the attendee info page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $post_excerpt;
		}

		// else, be sure we return the content - not the excerpt
		return the_content();
	}

	/**
	 * Modify the document title
	 *
	 * @since 4.9
	 * @param string $title
	 *
	 * @return array
	 */
	public function modify_page_title( $title ) {

		// When in the loop, no need to override titles.
		if ( in_the_loop() ) {
			return $title;
		}

		if ( tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			$title['title'] = $this->get_page_title();
		}

		// Return the title
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
		/**
		 * `tribe_tickets_attendee_registration_page_title`
		 * Filters the attendee registration page title
		 *
		 * @param array $post_types Array of post types
		 */
		return apply_filters( 'tribe_tickets_attendee_registration_page_title', esc_html__( 'Attendee Registration', 'event-tickets' ) );
	}

	/**
	 * Create a fake page we'll use to hijack our attendee info page
	 *
	 * @since 4.9
	 *
	 * @return obj
	 */
	public function spoofed_page() {

		$spoofed_page = array(
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
		);

		return ( object ) $spoofed_page;
	}

	/**
	 * Hijack the edit post link for our fake page
	 *
	 * @since 4.9
	 *
	 * @return mixed
	 */
	public function set_edit_post_link( $link ) {

		// Bail if we're not on the attendee info page
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return $link;
		}

		return '';
	}
}
