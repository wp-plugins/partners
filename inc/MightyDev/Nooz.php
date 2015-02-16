<?php

namespace MightyDev\WordPress;

//include __DIR__ . '/../wpalchemy/Page.php';

//include __DIR__ . '/../wpalchemy/Notice.php';

class Nooz
{
	private $post_type = array (
		'release' => 'nooz_release',
		'coverage' => 'nooz_coverage',
	);

	private $option_name = 'nooz_options';

	public $settings = array();
#
	public function __construct()
	{
		$this->post_type = (object) $this->post_type;

		$this->default_settings = array (
			'release_slug' => 'news/press-releases',
			'ending' => 'on',
			'date_format' => 'F j<\s\u\p>S</\s\u\p>, Y', // %F %j<sup>%S</sup> %Y
			'shortcode_count' => 5,
			'shortcode_display' => 'list',
			'target' => '_blank',
		);

		$this->settings = get_option( $this->option_name, array() );

		if ( ! isset( $this->settings['release_slug'] ) ) {
			$this->settings['release_slug'] = $this->default_settings['release_slug'];
			update_option( $this->option_name, $this->settings );
		}

		// todo: date_format should always have a default

		$this->settings = array_merge( $this->default_settings, $this->settings );
	}
#
	public function init()
	{




		$this->setupSettingsPage();
		// on settings update flush the rewrite rules
		add_action( 'admin_init', array ( $this, 'flush_rewrite_rules' ) );
		add_action( 'updated_option', array( $this, 'option_update') );

		//$this->init_shortcodes();
	}

	// perhaps each init should be a class .. NoozShortcode, NoozContentFilter, NoozAdminMenu, NoozCustomPostType, NoozDefaultPages
#
	public function init_cpt()
	{
		add_action( 'init', array( $this, 'create_cpt' ) );
	}
#
	public function init_admin_menus()
	{
		add_action( 'admin_menu', array ( $this, 'create_admin_menus' ), 999 );
	}
#
	public function init_content_filter()
	{
		add_filter( 'the_content', array( $this, 'filter_content' ) );
	}
#
	public function init_default_pages( \WPAlchemy\Factory $factory )
	{
		$option = 'nooz_default_pages';
		$option_val = get_option( $option );
		if ( false === $option_val ) {
			if ( isset( $_GET[$option] ) ) {
				update_option( $option, $_GET[$option] );
				if ( 1 == $_GET[$option] ) {
					add_action( 'admin_init', array ( $this, 'create_default_pages' ) );
				}
			} else {
				$url = admin_url( 'edit.php?post_status=draft&post_type=page&' . $option . '=' );
				$message = sprintf( __( 'Create default press pages? Yes, <a href="%s">create pages</a>. No, <a href="%s">dismiss</a>.', 'partners' ), $url . 1, $url . 0 );
				$factory->createNotice( $message, 'update-nag', 'edit_pages' );
			}
		}

		return $option_val;
	}
#
	public function create_default_pages()
	{
		$format = "<h2>%s</h2>\n[nooz-release]\n<p><a href=\"/news/press-releases/\">%s</a></p>\n<h2>%s</h2>\n[nooz-coverage]\n<p><a href=\"/news/coverage\">%s</a></p>";
		$args = array ( __( 'Press Releases', 'partners' ), __( 'More press releases ...', 'partners' ), __( 'Press Coverage', 'partners' ), __( 'More press coverage ...', 'partners' ) );
		$post_id = wp_insert_post( array (
			'post_content' => vsprintf( $format, $args ),
			'post_title' => __( 'News', 'partners' ),
			'post_name' => 'news',
			'post_type' => 'page',
		) );
		wp_insert_post( array (
			'post_content' => '[nooz-release count="*"]',
			'post_title' => __( 'Press Releases', 'partners' ),
			'post_name' => 'press-releases',
			'post_type' => 'page',
			'post_parent' => $post_id,
		) );
		wp_insert_post( array (
			'post_content' => '[nooz-coverage count="*"]',
			'post_title' => __( 'Press Coverage', 'partners' ),
			'post_name' => 'press-coverage',
			'post_type' => 'page',
			'post_parent' => $post_id,
		) );
	}
#
	public function flush_rewrite_rules()
	{
		if ( true == get_option( 'nooz_options_changed' ) ) {
			flush_rewrite_rules();
			update_option( 'nooz_options_changed', false );
		}
	}
#
	public function option_update( $option )
	{
		if ( $this->option_name == $option ) {
			update_option( 'nooz_options_changed', true );
		}
	}
#
	public function filter_content( $content )
	{
		global $post;
		if ( $this->post_type->release == $post->post_type ) {
			$meta = get_post_meta( $post->ID, '_nooz_release', true );
			$pre_content = null;
			if ( isset( $this->settings['location'] ) ) {
				$content = '<p class="nooz-location-datetime">' . $this->settings['location'] . ' &mdash; ' . get_the_time( ! empty( $this->settings['date_format'] ) ? $this->settings['date_format'] : $this->default_settings['date_format'] ) . '</p>' . $content;
			}
			if ( isset( $meta['subheadline'] ) ) {
				$content = '<h2 class="nooz-subheadline">' . $meta['subheadline'] . '</h2>' . $content;
			}
			if ( isset( $this->settings['boilerplate'] ) ) {
				$content .= '<div class="nooz-boilerplate">' . trim( wpautop( $this->settings['boilerplate'] ) ) . '</div>';
			}
			if ( 'off' != $this->settings['ending'] ) {
				$content .= '<p class="nooz-ending">###</p>';
			}
		}
		return $content;
	}
#
	public function init_shortcodes()
	{
		add_shortcode( 'nooz', array( $this, 'shortcode' ) );
		add_shortcode( 'nooz-release', array( $this, 'shortcode' ) );
		add_shortcode( 'nooz-coverage', array( $this, 'shortcode' ) );
	}

	public function setupSettingsPage()
	{
		$page = new \WPAlchemy\Settings\Page(array(
			'title' => 'Settings',
			'option_name' => 'nooz_options',
			'page_slug' => 'nooz',

		));

		// todo: decouple menu creation from page display
		$page->addSubmenuPage('partners', 'Settings', 'Settings', 'manage_options', 'nooz');

		$shortcode_section = $page->addSection( 'shortcode', 'Shortcode', 'Default shortcode settings, common between press releases as coverage.' );

		$shortcode_section->addNumberField( 'shortcode_count', 'Display Count', 'The number of press releases and coverage to display.', array( 'default_value' => $this->default_settings['shortcode_count'] ) );

		$shortcode_section->addSelectField( 'shortcode_display', 'Display Type', 'How to display press releases and coverage.', array ( array ( 'list', 'List' ), array ( 'group', 'Group' ) ) );

		$release_section = $page->addSection( 'release', 'Press Release', 'Settings for press releases' );

		$release_section->addTextField( 'release_slug', 'URL Rewrite', 'The URL structure for press releases. "{slug}" is the auto-generated part of the URL created when adding a <a href="post-new.php?post_type='. $this->post_type->release .'">new press release</a>.', array( 'before_field' => site_url() . '/', 'default_value' => $this->default_settings['release_slug'], 'after_field' => '/{slug}/' ) );

		$release_section->addTextField( 'location', 'Location', 'The location precedes the press release and helps to orient the reader.', array( 'placeholder' => 'San Francisco, California' ) );

		$release_section->addTextField( 'date_format', 'Date Format', 'The <a href="http://php.net/manual/en/function.date.php" target="_blank">date format</a> to use. The date will be automatically generated after the location.', array( 'default_value' => $this->default_settings['date_format'] ) );

		$release_section->addTextAreaField( 'boilerplate', 'Boilerplate', 'The boilerplate is a few sentences at the end of your press release that describes your organization. This should be used consistently on press materials and written strategically, to properly reflect your organization.' );

		$release_section->addOnOffField( 'ending', 'Ending', 'Add ending mark <strong>###</strong>, common on press releases.', array ( 'default_value' => $this->default_settings['ending'] ) );

		$coverage_section = $page->addSection( 'coverage', 'Press Coverage', 'Settings for press coverage');

		$coverage_section->addTextField( 'target', 'Link Target', 'Default link target for press coverage links.', array('default_value' => $this->default_settings['target'] ) );
	}
#
	public function create_admin_menus()
	{
		global $submenu;

		add_menu_page( 'Press', 'Press', 'edit_posts', 'partners', null, 'dashicons-megaphone' );

		add_submenu_page( 'partners', 'Add New', sprintf( '<span class="md-submenu-indent">%s</span>', 'Add New' ), 'edit_posts', 'post-new.php?post_type=' . $this->post_type->release );

		// reposition "Add New" submenu item after "All Releases" submenu item
		array_splice( $submenu['partners'], 1, 0, array( array_pop( $submenu['partners'] ) ) );

		add_submenu_page( 'partners', 'Add New', sprintf( '<span class="md-submenu-indent">%s</span>', 'Add New' ), 'edit_posts', 'post-new.php?post_type=' . $this->post_type->coverage );

		\WPAlchemy\Settings\setMenuPosition( 'nooz', '99.0100' );
	}
#
	public function create_cpt()
	{
		$labels = array(
			'name'               => _x( 'Press Releases', 'post type general name', 'partners' ),
			'singular_name'      => _x( 'Press Release', 'post type singular name', 'partners' ),
			'add_new'            => _x( 'Add New', 'press release', 'partners' ),
			'add_new_item'       => __( 'Add New Press Release', 'partners' ),
			'new_item'           => __( 'New Page', 'partners' ),
			'edit_item'          => __( 'Edit Press Release', 'partners' ),
			'view_item'          => __( 'View Press Release', 'partners' ),
			'all_items'          => __( 'All Releases', 'partners' ),
			'not_found'          => __( 'No press releases found.', 'partners' ),
			'not_found_in_trash' => __( 'No press releases found in Trash.', 'partners' )
		);
		$args = array(
			'labels'             => $labels,
			'public'             => true,
			// show_ui=true (default) because CPT are not editable if show_ui=false
			// https://core.trac.wordpress.org/browser/tags/4.0.1/src/wp-admin/post-new.php#L14
			// https://core.trac.wordpress.org/browser/trunk/src/wp-admin/post-new.php#L14
			'show_in_menu'       => 'partners',
			'show_in_admin_bar'  => true,
			'rewrite'            => array( 'slug' => $this->settings['release_slug'], 'with_front' => false ),
			'supports'           => array( 'title', 'editor', 'author', 'revisions' )
		);
		register_post_type( 'nooz_release', $args );

		$labels = array(
			'name'               => _x( 'Press Coverage', 'press coverage', 'partners' ),
			'singular_name'      => _x( 'Press Coverage', 'press coverage', 'partners' ),
			'add_new'            => _x( 'Add New', 'press coverage', 'partners' ),
			'add_new_item'       => __( 'Add New Press Coverage', 'partners' ),
			'new_item'           => __( 'New Press Coverage', 'partners' ),
			'edit_item'          => __( 'Edit Coverage', 'partners' ),
			'view_item'          => __( 'View Coverage', 'partners' ),
			'all_items'          => __( 'All Coverage', 'partners' ),
			'not_found'          => __( 'No press coverage found.', 'partners' ),
			'not_found_in_trash' => __( 'No press coverage found in Trash.', 'partners' )
		);
		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'show_ui'            => true, // required because public == false
			'show_in_menu'       => 'partners',
			'show_in_admin_bar'  => true,
			'rewrite'            => false,
			'supports'           => array( 'title', 'revisions' )
		);
		register_post_type( 'nooz_coverage', $args );
	}
#
	public function create_release_metabox( \WPAlchemy\Factory $factory )
	{
		$options = array(
			'types' => array( $this->post_type->release ),
			'lock' => 'after_post_title',
			'hide_title' => true
		);

		$factory->createMetaBox( '_nooz_release', 'Subheadline', __DIR__ . '/../subheadline-meta.php', $options );
	}
#
	public function create_coverage_metabox( \WPAlchemy\Factory $factory )
	{
		$options = array(
			'types' => array( 'nooz_coverage' )
		);

		// todo: consider renaming _nooz to _nooz_coverage .. needs backward-compatibility consideration
		$factory->createMetaBox( '_nooz', 'Details', __DIR__ . '/../coverage-meta.php', $options );
	}

	public function shortcode( $atts, $content = null, $tag = null )
	{
		// todo: use "release_css_class" and "coverage_css_class" settings

		$default_atts = array
		(
			'count' => $this->settings['shortcode_count'],
			'type' => $tag, // release, coverage
			'display' => $this->settings['shortcode_display'], // list, group
			'target' => '',
			'class' => '',
		);

		extract( shortcode_atts( $default_atts, $atts ) );

		$type = 'nooz_release';

		if ( stristr( $type, 'coverage' ) ) {
			$type = 'nooz_coverage';
		}

		if ('*' == $count) {
			$count = -1;
		}

		$my_posts = get_posts( array( 'post_type' => $type, 'posts_per_page' => $count ) );

		$html = '';

		if ( ! empty( $my_posts ) ) {
			$previous_year = $year = 0;
			$open = false;

			$html_ul = sprintf('<ul class="nooz-list %s %s">', str_replace('_', '-', $type), $class);

			if ( 'list' == $display ) {
				$html .= $html_ul;
			}

			foreach( $my_posts as $my_post ) {
				$year = mysql2date( 'Y', $my_post->post_date );

				$month = mysql2date( 'n', $my_post->post_date );

				$day = mysql2date( 'j', $my_post->post_date );



				if ( 'nooz_coverage' == $type ) {
					$meta = get_post_meta( $my_post->ID, '_nooz', TRUE );
					$link = $meta['link'];
					$link_target = $this->settings['target'];

					$external_link = $meta['link'];
					$external_link_target = $this->settings['target'];
				} else {
					$link = get_permalink( $my_post->ID );
					$link_target = '';
				}

				$external_link_class = '';

				if ( preg_match( '/^http/i', $link ) && ! stristr( $link, 'violin-memory.com') ) {
					$external_link_class = ' class="redirect-link external"';
				}

				if( 'group' == $display && $year != $previous_year ) {
					if ( true == $open) {
						$html .= '</ul>';
					}

					$html .= '<h3 class="nooz-group">' . $year . '</h3>' . $html_ul;
					$open = true;
				}

				$previous_year = $year;

				$html .= sprintf( '<li><time datetime="%s">%s</time>', get_the_time( 'Y-m-d', $my_post->ID ), get_the_time( 'M j, Y', $my_post->ID ) );

				$html .= '<a href="' . $link . '" target="' . ( $target ? $target : $link_target )  . '">' . get_the_title( $my_post->ID ) . '</a></li>';
			}

			$html .= '</ul>';
		}

		return $html;
	}
}
