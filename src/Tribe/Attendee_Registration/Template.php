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

		/*
		 * Choose the theme template to use. It has to have a higher priority than the
		 * TEC filters (at 10) to ensure they do not usurp our rewrite here.
		 */
		add_filter( 'template_include', array( $this, 'set_page_template' ), 15 );

		/*
		 * Set the content of the page. Again, it has to have a higher priority than the
		 * TEC filters (at 10) to ensure they do not usurp our rewrite here.
		 */
		add_action( 'loop_start', array( $this, 'set_page_content' ), 15 );

		// Modify the link for the edit post link
		add_filter( 'edit_post_link', array( $this, 'set_edit_post_link' ) );

		// Switcheroo for Genesis using the excerpt as we're saying we're on an archive
		add_filter( 'genesis_pre_get_option_content_archive', array( $this, 'override_genesis_archive' ), 10, 2 );
		// Also keep content limit from truncating the form
		add_filter( 'genesis_pre_get_option_content_archive_limit', array( $this, 'override_genesis_limit' ), 10, 2 );

		// Modify the page title
		add_filter( 'document_title_parts', array( $this, 'modify_page_title' ), 1000 );
		add_filter( 'get_the_archive_title', array( $this, 'modify_archive_title' ), 1000 );
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
		if ( ! $this->is_on_ar_page() ) {
			return $posts;
		}

		// Empty posts
		$posts = null;
		// Create a fake virtual page
		$posts[] = $this->spoofed_page();

		// Don't tell wp_query we're anything in particular - then we don't run into issues with defaults.
		$wp_query->is_page     = false;
		$wp_query->is_singular = false;
		$wp_query->is_home     = false;
		$wp_query->is_archive  = false;
		$wp_query->is_category = false;
		$wp_query->is_404      = false;
		$wp_query->found_posts = 1;
		$wp_query->posts_per_page = 1;

		return $posts;

	}

	/**
	 * convenience wrapper for tribe( 'tickets.attendee_registration' )->is_on_page()
	 *
	 * @since 4.10.2
	 *
	 * @return boolean
	 */
	public function is_on_ar_page() {
		return tribe( 'tickets.attendee_registration' )->is_on_page();
	}

	/**
	 * Set the theme page template we're going to use for the attendee-registration page
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function set_page_template( $template ) {

		// Bail if we're not on the attendee info page
		if ( ! $this->is_on_ar_page() ) {
			return $template;
		}

		// Use the template option set in the admin
		$template = tribe_get_option( 'ticket-attendee-info-template' );

		if ( empty( $template ) ) {
			// we should only get here if the value hasn't been set yet
			$template = 'default';
		} elseif ( 'same' === $template ) {
			//note this could be an empty string...because.
			$template = tribe_get_option( 'tribeEventsTemplate', 'default' );
		}

		if ( in_array( $template, array( '', 'default' ), true ) ) {
			// A bit of logic for themes without a page.php
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
	 * Add and remove body classes.
	 *
	 * @since 4.9
	 *
	 * @return void
	 */
	public function set_body_classes() {
		// Bail if we're not on the attendee info page
		if ( ! $this->is_on_ar_page() ) {
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
		if ( ! $this->is_on_ar_page() ) {
			return;
		}

		if ( $this->is_main_loop( $query ) ) {
			// on the_content, load our attendee info page
			add_filter( 'the_content', array( tribe( 'tickets.attendee_registration.view' ), 'display_attendee_registration_page' ) );
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
		// Bail if we're not on the attendee info page
		if ( ! $this->is_on_ar_page() ) {
			return null;
		}

		return 'full';
	}

	/**
	 * Hooks into the genesis excerpt filter and forces it "off" on the AR page
	 *
	 * @since TBD - Return null if not on ar page and true if on ar page.
	 *
	 * @param string|null $unused_null Unused variable
	 * @param string $setting
	 *
	 * @return null|boolean
	 */
	public function override_genesis_limit( $unused_null, $setting ) {
		// Bail if we're not on the attendee info page
		if ( ! $this->is_on_ar_page() ) {
			return null;
		}

		// Return true on AR to get no content.
		return '';
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

		if ( $this->is_on_ar_page() ) {
			$title['title'] = $this->get_page_title();
		}

		// Return the title
		return $title;
	}

	/**
	 * Modify the archive title - for themes that somehow defeat our earlier hook.
	 *
	 * @since 4.10.2
	 * @param string $title
	 *
	 * @return string
	 */
	public function modify_archive_title( $title ) {
		if ( $this->is_on_ar_page() ) {
			$title = $this->get_page_title();
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
		 * @param string the "Attendee Registration" title
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
		if ( ! $this->is_on_ar_page() ) {
			return $link;
		}

		return '';
	}
}
