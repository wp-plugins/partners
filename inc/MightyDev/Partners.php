<?php

namespace MightyDev\WordPress;

class Partners
{
	public $options = array();

	public $members_tb = null;

	public $db_version = '0.1.0';

	public $option_name = 'mdpartners_options';

	protected $email_options = array();

	protected $plugin_file;

	public function __construct( $plugin_file )
	{
		$this->plugin_file = $plugin_file;

		global $wpdb;

		$this->members_tb = $wpdb->prefix . 'mdpartners';

		// /partners/register/
		// /partners/register/success/
		// /partners/login/
		// /partners/logout/


		$this->options = $this->get_options( array(
			'reset_password_url' => '/partners/reset-password/',
			'reset_password_success_url' => '/partners/reset-password/success/',
			'forgot_password_url' => '/partners/forgot-password/',
			'forgot_password_success_url' => '/partners/forgot-password/success/',
			'forgot_password_subject' => 'Your Password Reset Link',
			'forgot_password_body' => file_get_contents( __DIR__ . '/../forgot-password-body.txt' ),
			'login_url' => '/partners/login/',
			'login_success_url' => '/partners/',
			'logout_url' => '/partners/logout/',
			'reg_success_url' => '/partners/register/success/',
			'reg_welcome_from_name' => get_bloginfo( 'name' ),
			'reg_welcome_from_email' => get_bloginfo( 'admin_email' ),
			'reg_welcome_subject' => 'Registration Request',
			'reg_welcome_body' => "Thank you for requesting access. Your request has been queued for review.\n\n" . get_option( 'blogname' ) . "\n" . home_url( '/' ),
		) );

		$this->email_options = array(
			'{password_reset_url}' => site_url( $this->options['reset_password_url'] ),
			'{site_name}' => get_option( 'blogname' ),
			'{site_url}' => home_url( '/' ),
		);
	}

	// todo: move into a base abstract class
	public function get_options( $default_options )
	{
		$options = get_option( $this->option_name, array() );

		// todo save default options that are not already set

		return array_merge( $default_options, $options );
	}

	public function create_tables()
	{
		global $wpdb;

		// todo: works in newer versions of wordpress (4.1)
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $this->members_tb (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			first_name varchar(100) DEFAULT NULL,
			last_name varchar(100) DEFAULT NULL,
			email varchar(255) DEFAULT '' NOT NULL,
			password varchar(64) DEFAULT '' NOT NULL,
			status varchar(50) DEFAULT 'pending' NOT NULL,
			confirmed datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

		add_option( 'mdpartners_db_version', $this->db_version );
	}

	// todo: perhaps is becomes standalone ..
	public function init_default_pages( \WPAlchemy\Factory $factory )
	{
		$option = 'mdpartners_default_pages';
		$option_val = get_option( $option );
		if ( false === $option_val ) {
			if ( isset( $_GET[$option] ) ) {
				update_option( $option, $_GET[$option] );
				if ( 'dismiss' != $_GET[$option] ) {
					add_action( 'admin_init', array ( $this, 'create_default_pages' ) );
				}
			} else {
				add_action( 'admin_enqueue_scripts', 'add_thickbox' );
				$publish_url = admin_url( 'edit.php?post_status=publish&post_type=page&' . $option . '=publish' );
				$dismiss_url = admin_url( 'edit.php?post_type=page&' . $option . '=dismiss' );
				$info_url = plugins_url( 'inc/default-pages.php?TB_iframe=true&width=300&height=300', $this->plugin_file );
				$message = sprintf( __( 'Create default partners pages? <a title="Create default partners pages?" href="%s" class="thickbox">More info</a>. Yes, <a href="%s">publish pages</a>. No, <a href="%s">dismiss</a>.', 'partners' ), $info_url, $publish_url, $dismiss_url );
				$factory->createNotice( $message, 'update-nag', 'edit_pages' );
			}
		}
		return $option_val;
	}

	public function create_default_pages()
	{
		$status = 'publish';
		$post_id = wp_insert_post( array (
			'post_content' => __( "You are logged [partners_is_authenticated]in[/partners_is_authenticated][partners_is_not_authenticated]out[/partners_is_not_authenticated].", 'partners' ),
			'post_title' => __( 'Partners', 'partners' ),
			'post_name' => 'partners',
			'post_type' => 'page',
			'post_status' => $status,
		) );
		wp_insert_post( array (
			'post_content' => '[partners_login_form]',
			'post_title' => __( 'Login', 'partners' ),
			'post_name' => 'login',
			'post_type' => 'page',
			'post_parent' => $post_id,
			'post_status' => $status,
		) );
		wp_insert_post( array (
			'post_content' => __( "Your current session has been discarded, you are now logged out.", 'partners' ),
			'post_title' => __( 'Logout', 'partners' ),
			'post_name' => 'logout',
			'post_type' => 'page',
			'post_parent' => $post_id,
			'post_status' => $status,
		) );
		$reg_post_id = wp_insert_post( array (
			'post_content' => '[partners_registration_form]',
			'post_title' => __( 'Register', 'partners' ),
			'post_name' => 'register',
			'post_type' => 'page',
			'post_parent' => $post_id,
			'post_status' => $status,
		) );
		wp_insert_post( array (
			'post_content' => __( "Thank you for your interest. You will be contacted regarding next steps.", 'partners' ),
			'post_title' => __( 'Registration Complete', 'partners' ),
			'post_name' => 'success',
			'post_type' => 'page',
			'post_parent' => $reg_post_id,
			'post_status' => $status,
		) );
		$forgot_post_id = wp_insert_post( array (
			'post_content' => __( "Use this form if you've forgotten your password.", 'partners' ) . "\n\n[partners_forgot_password_form]",
			'post_title' => __( 'Forgot Password', 'partners' ),
			'post_name' => 'forgot-password',
			'post_type' => 'page',
			'post_parent' => $post_id,
			'post_status' => $status,
		) );
		wp_insert_post( array (
			'post_content' => __( "You will receive an email with instructions on resetting your password.", 'partners' ),
			'post_title' => __( 'Password Change Requested', 'partners' ),
			'post_name' => 'success',
			'post_type' => 'page',
			'post_parent' => $forgot_post_id,
			'post_status' => $status,
		) );
		$reset_post_id = wp_insert_post( array (
			'post_content' => __( "Use this form to change your existing password.", 'partners' ) . "\n\n[partners_reset_password_form]",
			'post_title' => __( 'Reset Password', 'partners' ),
			'post_name' => 'reset-password',
			'post_type' => 'page',
			'post_parent' => $post_id,
			'post_status' => $status,
		) );
		wp_insert_post( array (
			'post_content' => __( "Your password was successfully reset.", 'partners' ),
			'post_title' => __( 'Password Changed', 'partners' ),
			'post_name' => 'success',
			'post_type' => 'page',
			'post_parent' => $reset_post_id,
			'post_status' => $status,
		) );
	}

	public function logout()
	{
		if ( FALSE !== stristr( $_SERVER['REQUEST_URI'], $this->options['logout_url'] ) ) {
			$this->delete_cookie( 'mighty_partners_login' );
		}
	}

	public $forgot_password_form_name = 'mighty_partners_forgot_password_form';

	// partners_reset_password_form, partners_forgot_password_form
	public function form_shortcode( $atts, $content = null, $tag = null )
	{
		extract(shortcode_atts(array(
			'key' => null,
			'class' => null,
		), $atts));

		$form = $this->form_data[$tag];

		//var_dump($form);

		ob_start();
		include_once __DIR__ . '/../' . str_replace( array( 'partners_', '_' ), array( '', '-', ), $tag ) . '.php';
		$html = ob_get_clean();

		return '<form class="mighty-form" method="POST">' . wp_nonce_field( $tag ) . $html . '</form>';
	}

	public $form_data;

	public function reset_password_form_submit()
	{
		$this->form_data['partners_reset_password_form'] = $this->form_process( 'partners_reset_password_form', array( 'password', 'password_confirm' ) );
		$this->form = $this->form_data['partners_reset_password_form'];
		if ( ! isset( $_GET['t'] ) ) {
			array_push( $this->form->errors, 'Unable to reset password' );
			$this->form->success = false;
		}
		if ( isset( $_GET['t'] ) && false === ( $email = get_transient( $_GET['t'] ) ) ) {
			array_push( $this->form->errors, 'Password reset expired' );
			$this->form->success = false;
		}
		if ( $this->form->success && $this->form->fields['password']->value != $this->form->fields['password_confirm']->value ) {
			array_push( $this->form->errors, 'Passwords do not match' );
			$this->form->success = false;
		}
		if ( $this->form->success ) {
			$member = $this->get_member( $email );
			if ( ! is_null( $member ) && $member->id ) {
				$this->update_member( $member->id, array( 'password' => wp_hash_password( $this->form->fields['password']->value ) ) );
				$this->redirect( $this->options['reset_password_success_url'] );
			} else {
				array_push( $this->form->errors, 'Unable to reset password' );
				$this->form->success = false;
			}
		}
	}

	public function forgot_password_form_submit()
	{
		$this->form_data['partners_forgot_password_form'] = $this->form_process( 'partners_forgot_password_form', array( 'email' ) );
		$this->form = $this->form_data['partners_forgot_password_form'];
		if ( $this->form->success ) {
			$member = $this->get_member( $this->form->fields['email']->value );
			if ( ! is_null( $member ) && $member->id ) {

				$token = md5( time() . mt_rand() );

				set_transient( $token, $member->email, HOUR_IN_SECONDS );

				//$nonce = wp_create_nonce( 'partners_reset_password_' . $member->id );

				$this->email_options['{password_reset_url}'] = site_url( $this->options['reset_password_url'] . "?t=" . $token );

				$headers = "From: " . $this->options['reg_welcome_from_name'] . " <" . $this->options['reg_welcome_from_email'] . ">\r\n";

				$body = $this->options['forgot_password_body'];
				foreach( $this->email_options as $name => $value ) {
					$body = str_replace( $name, $value, $body );
				}

				$body = str_replace( "\n\n", "\n", $body );

				wp_mail( $member->email, $this->options['forgot_password_subject'], $body . "\r\n", $headers );
				$this->redirect( $this->options['forgot_password_success_url'] );
			} else {
				array_push( $this->form->errors, 'Unable to reset password' );
				$this->form->success = false;
			}
		}
	}

	public function login_form_shortcode( $atts, $content = null )
	{
		extract(shortcode_atts(array(
			'key' => null,
			'class' => null,
		), $atts));

		$form = $this->form_data[$this->login_form_name];

		//var_dump($form);

		$html = '<form class="mighty-form" method="POST">';

		$html .= wp_nonce_field( $this->login_form_name );

		ob_start();
		include_once __DIR__ . '/../login-form.php';
		$html .= ob_get_clean();

		$html .= '</form>';

		return $html;
	}

	public $login_form_name = 'mighty_partners_login_form';

	public $form;

	public function login_form_submit()
	{
		$this->form_data[$this->login_form_name] = $this->form_process( $this->login_form_name, array( 'email', 'password' ) );
		$this->form = $this->form_data[$this->login_form_name];

		if ( $this->form->success ) {

			$member = $this->get_member( $this->form->fields['email']->value );

			if ( ! is_null( $member ) && wp_check_password( $this->form->fields['password']->value, $member->password ) ) {
				// cookie is tied to a session
				$this->set_cookie( 'mighty_partners_login', array( 'email' => $this->form->fields['email']->value ) );
				$this->redirect( $this->options['login_success_url'] );
			} else {
				$this->form_add_error( 'Login failed' );
			}
		}
	}

	public function is_authenticated()
	{
		$data = $this->get_cookie( 'mighty_partners_login' );

		// todo: better authentication
		// http://stackoverflow.com/questions/8672377/php-properly-setting-cookies-for-login
		if ( ! empty( $data['email'] ) ) {
			return true;
		}

		return false;
	}

	public function is_auth_shortcode( $atts, $content = null )
	{
		if ( $this->is_authenticated() ) {
			return do_shortcode( trim( $content ) );
		}
		return '';
	}

	public function is_not_auth_shortcode( $atts, $content = null )
	{
		if ( ! $this->is_authenticated() ) {
			return do_shortcode( trim( $content ) );
		}
		return '';
	}

	protected function delete_cookie( $cookie_name )
	{
		setcookie( $cookie_name, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true );
		unset( $_COOKIE[ $cookie_name ] );
	}

	protected function set_cookie( $cookie_name, $data = array() )
	{
		$serialized_data = serialize( $data );
		$encoded_data = base64_encode( $serialized_data );
		setcookie( $cookie_name, $encoded_data, time()+(30*DAY_IN_SECONDS), '/', COOKIE_DOMAIN, is_ssl(), true );
		// manually set cookie for the current request
		$_COOKIE[$cookie_name] = $encoded_data;
	}

	protected function get_cookie( $cookie_name )
	{
		$data = array();
		if ( ! empty( $_COOKIE[$cookie_name] ) ) {
			$decoded_data = base64_decode( $_COOKIE[$cookie_name] );
			$data = unserialize( $decoded_data );
		}
		return $data;
	}

	public $reg_form;

	public function reg_form_shortcode( $atts, $content = null )
	{
		extract(shortcode_atts(array(
			'key' => null,
			'class' => null,
		), $atts));

		$form = $this->reg_form;

		$html = '<form class="mighty-form" method="POST">';

		$html .= wp_nonce_field( 'partner_reg_form' );

		ob_start();
		include_once __DIR__ . '/../registration-form.php';
		$html .= ob_get_clean();

		$html .= '</form>';

		return $html;
	}

	public $reg_form_fields = array( 'first_name', 'last_name', 'email' );

	protected function form_add_error( $error )
	{
		array_push( $this->form->errors, $error );
		$this->form->success = false;
	}

	// field object
	// name, value, error, required, placeholder, default_value

	protected function form_process( $form_name = null, $fields = array() )
	{
		// todo: ability to specify required and optional fields

		$form = new \stdClass;
		$form->fields = array();
		$form->errors = array();
		$form->success = false;
		$form->submitted = false;
		foreach( $fields as $field_name ) {
			$form->fields[$field_name] = new \stdClass;
			$form->fields[$field_name]->name = $field_name;
			$form->fields[$field_name]->value = null;
			$form->fields[$field_name]->error = false;
		}
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], $form_name ) ) {
			$form->submitted = true;
			foreach( $fields as $field_name ) {
				if ( ! empty( $_POST[$field_name] ) ) {
					$field_value = sanitize_text_field( $_POST[$field_name] );
					$form->fields[$field_name]->value = $field_value;

					if ( 'email' == $field_name && ! is_email( $field_value ) ) {
						$form->fields[$field_name]->error = 'Invalid email address';
						array_push( $form->errors, 'Invalid email address' );
					}
				} else {
					$form->fields[$field_name]->error = 'Field required';
					array_push( $form->errors, 'Field "' . str_replace( array( '-', '_' ), ' ', $field_name ) . '" is required' );
				}
			}
			if ( ! count( $form->errors ) ) {
				$form->success = true;
			}
		}
		return $form;
	}

	protected function reg_form_process( $form_name = null )
	{
		$form = new \stdClass;
		$form->fields = array();
		$form->errors = array();
		$form->success = false;
		$form->submitted = false;
		foreach( $this->reg_form_fields as $field_name ) {
			$form->fields[$field_name] = new \stdClass;
			$form->fields[$field_name]->name = $field_name;
			$form->fields[$field_name]->value = null;
			$form->fields[$field_name]->error = false;
		}

		// parse HTML for form field names to expect

		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'partner_reg_form' ) ) {
			$form->submitted = true;
			foreach( $this->reg_form_fields as $field_name ) {
				if ( ! empty( $_POST[$field_name] ) ) {
					$field_value = sanitize_text_field( $_POST[$field_name] );
					$form->fields[$field_name]->value = $field_value;

					if ( 'email' == $field_name && ! is_email( $field_value ) ) {
						$form->fields[$field_name]->error = 'Invalid email address';
						array_push( $form->errors, 'Invalid email address' );
					}
				} else {
					$form->fields[$field_name]->error = 'Field required';
					array_push( $form->errors, 'Field "' . str_replace( array( '-', '_' ), ' ', $field_name ) . '" is required' );
				}
			}
			if ( ! count( $form->errors ) ) {
				$form->success = true;
			}
		}

		return $form;
	}

	protected function update_member( $id, $data )
	{
		global $wpdb;
		return $wpdb->update( $this->members_tb, $data, array( 'id' => $id ) );
	}

	protected function get_member( $email )
	{
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->members_tb . ' WHERE `email` = %s', array( $email ) ) );
	}

	protected function insert_member( $first_name, $last_name, $email )
	{
		global $wpdb;
		$wpdb->hide_errors();
		$sql = 'INSERT INTO '.$this->members_tb.' (`first_name`, `last_name`, `email`, `status`, `created`) VALUES (%s, %s, %s, %s, %s)';
		$values = array( $first_name, $last_name, $email, 'pending', date('Y-m-d H:i:s') );
		$query = $wpdb->prepare( $sql, $values );
		if ( ! $wpdb->query( $query ) ) {
			$error = mysql_error( $wpdb->dbh );
			if ( stristr( $error, 'duplicate entry' ) ) {
				return 'Email address is already registered';
			}
			return 'Unable to complete registration, please try again';
		}
		return null;
	}

	// todo: move this into a base class
	protected function redirect( $location, $code = '302' )
	{
		wp_redirect( $location, $code );
		exit;
	}

	public function reg_form_submit()
	{
		$this->reg_form = $this->reg_form_process();

		if ( $this->reg_form->success ) {

			$email = $this->reg_form->fields['email']->value;

			$response = $this->insert_member( $this->reg_form->fields['first_name']->value, $this->reg_form->fields['last_name']->value, $email );

			if ( is_null( $response ) ) {
				$member = $this->get_member( $email );

				//$password = wp_generate_password();

				//$this->update_member( $member->id, array( 'password' => wp_hash_password( $password ) ) );

				// todo: send user password with welcome email

				$headers = "From: ". $this->options['reg_welcome_from_name'] ." <" . $this->options['reg_welcome_from_email'] . ">\r\n";

				wp_mail( $member->email, $this->options['reg_welcome_subject'], $this->options['reg_welcome_body'], $headers );

				$this->redirect( $this->options['reg_success_url'] );
			} else {
				array_push( $this->reg_form->errors, $response );
				$this->reg_form->success = false;
			}
		}
	}

	// todo: pass $factory to function
	public function init_settings()
	{
		$page = new \WPAlchemy\Settings\Page(array(
			'title' => 'Settings',
			'option_name' => $this->option_name,
			'page_slug' => 'mdpartners',
		));

		// todo: decouple menu creation from page display
		$page->addSubmenuPage('mdpartners', 'Settings', 'Settings', 'manage_options', 'mdpartners_settings');

		//$shortcode_section = $page->addSection( 'shortcode', 'Shortcode', 'Default shortcode settings, common between press releases as coverage.' );

		//$shortcode_section->addNumberField( 'shortcode_count', 'Display Count', 'The number of press releases and coverage to display.', array( 'default_value' => $this->default_settings['shortcode_count'] ) );

		//$shortcode_section->addSelectField( 'shortcode_display', 'Display Type', 'How to display press releases and coverage.', array ( array ( 'list', 'List' ), array ( 'group', 'Group' ) ) );


		$release_section = $page->addSection( 'reg_form', 'Registration Form' );

		$release_section->addTextField( 'reg_success_url', 'Registration Success URL', 'The success page after the registration form submission.', array( 'default_value' => $this->options['reg_success_url'] ) );


		$release_section = $page->addSection( 'reg_confirmation', 'Registration Auto Responder' );

		$release_section->addTextField( 'reg_welcome_from_name', 'Sender Name (optional)', 'The from name of the auto response email', array( 'default_value' => $this->options['reg_welcome_from_name'] ) );

		$release_section->addTextField( 'reg_welcome_from_email', 'Sender Email Address', 'The from email address of the auto response email', array( 'default_value' => $this->options['reg_welcome_from_email'] ) );

		$release_section->addTextField( 'reg_welcome_subject', 'Subject Line', null, array( 'class' => 'large-text', 'default_value' => $this->options['reg_welcome_subject'] ) );

		$release_section->addTextAreaField( 'reg_welcome_body', 'Body', null, array( 'default_value' => $this->options['reg_welcome_body'] ) );


		$release_section = $page->addSection( 'login_logout', 'Login/Logout' );

		$release_section->addTextField( 'login_url', 'Login URL', 'The url where the login form exists.', array( 'default_value' => $this->options['login_url'] ) );

		$release_section->addTextField( 'login_success_url', 'Login Success URL', 'The success page after the login form submission.', array( 'default_value' => $this->options['login_success_url'] ) );

		$release_section->addTextField( 'logout_url', 'Logout URL', 'The page where a member is logged out', array( 'default_value' => $this->options['logout_url'] ) );


		$release_section = $page->addSection( 'forgot_password_form', 'Forgot Password Form', 'This form allows the member to request a password reset.' );

		$release_section->addTextField( 'forgot_password_url', 'Forgot Password URL', 'The url where the forgot password form exists.', array( 'default_value' => $this->options['forgot_password_url'] ) );

		$release_section->addTextField( 'forgot_password_success_url', 'Forgot Password Success URL', 'The success page after the forgot password form submission.', array( 'default_value' => $this->options['forgot_password_success_url'] ) );


		$release_section = $page->addSection( 'reset_password_form', 'Reset Password Form', 'This form allows the member to change their password after they have requested a password reset.' );

		$release_section->addTextField( 'reset_password_url', 'Reset Password URL', 'The url where the reset password form exists.', array( 'default_value' => $this->options['reset_password_url'] ) );

		$release_section->addTextField( 'reset_password_success_url', 'Reset Password Success URL', 'The success page after the reset password form submission.', array( 'default_value' => $this->options['reset_password_success_url'] ) );

		// todo: add notification section .. reg_notify_to_email, reg_notify_subject, reg_notify_body

		//$release_section->addTextField( 'reg_success_url', 'Registration Success URL', 'The success page after the registration form submission.', array( 'placeholder' => 'Registration Form Thank You Page' ) );

		//$release_section->addTextField( 'date_format', 'Date Format', 'The <a href="http://php.net/manual/en/function.date.php" target="_blank">date format</a> to use. The date will be automatically generated after the location.', array( 'default_value' => $this->default_settings['date_format'] ) );

		//$release_section->addOnOffField( 'ending', 'Ending', 'Add ending mark <strong>###</strong>, common on press releases.', array ( 'default_value' => $this->default_settings['ending'] ) );


		//$coverage_section->addTextField( 'target', 'Link Target', 'Default link target for press coverage links.', array('default_value' => $this->default_settings['target'] ) );
	}
}
