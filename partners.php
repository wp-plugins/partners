<?php

/*
Plugin Name: Partners
Plugin URI: http://mightydev.com/entrance/
Description: Creates a fenced membership area with private content.
Author: Mighty Digital
Author URI: http://mightydigital.com
Version: 0.2.0
*/

//partners_member_login
//partners_member_registration

//partners_member_authenticated
//partners_member_not_authenticated

require_once __DIR__ . '/inc/autoload.php';

$factory = new \WPAlchemy\Factory;

$partners = new \MightyDev\WordPress\Partners( __FILE__ );

$partners->init_settings();

$partners->init_default_pages( $factory );

register_activation_hook( __FILE__, array( $partners, 'create_tables' ) );

function md_members_tb()
{
	global $partners;
	return $partners->members_tb;
}

//$entrance->init();

### shortcodes

add_shortcode( 'partners_login_form', array( $partners, 'login_form_shortcode' ) );
add_action( 'init', array( $partners, 'login_form_submit' ) );

add_shortcode( 'partners_registration_form', array( $partners, 'reg_form_shortcode' ) );
add_action( 'init', array( $partners, 'reg_form_submit' ) );

add_shortcode( 'partners_forgot_password_form', array( $partners, 'form_shortcode' ) );
add_action( 'init', array( $partners, 'forgot_password_form_submit' ) );

add_shortcode( 'partners_reset_password_form', array( $partners, 'form_shortcode' ) );
add_action( 'init', array( $partners, 'reset_password_form_submit' ) );

add_action( 'init', array( $partners, 'logout' ) );

add_shortcode('partners_is_authenticated', array( $partners, 'is_auth_shortcode' ) );

add_shortcode('partners_is_not_authenticated', array( $partners, 'is_not_auth_shortcode' ) );

### menus

add_action('admin_menu', 'mdpartners_menu');

function mdpartners_menu()
{
	$menu_slug  = 'mdpartners';

	add_menu_page('Partners', 'Partners', 'edit_pages', 'mdpartners', 'mdpartners_view', 'dashicons-businessman', '99.001');

	add_submenu_page('mdpartners', 'Partners', 'All Members', 'edit_pages', 'mdpartners', 'mdpartners_view');

	add_submenu_page('mdpartners', 'Edit Approved Email', 'Approved Email', 'edit_pages', 'mdpartners_approved_email', 'mdpartners_approved_email_handler');

	add_submenu_page('mdpartners', 'Edit Denied Email', 'Denied Email', 'edit_pages', 'mdpartners_denied_email', 'mdpartners_denied_email_handler');
}

### members view

function mdpartners_view()
{
	$wp_list_table = new VM_Partner_Table();

	$wp_list_table->prepare_items();

	?>
		<div class="wrap">

			<div id="icon-options-general" class="icon32"><br/></div>

			<h2>Members</h2>

			<?php $wp_list_table->views(); ?>

			<form id="partners-table" method="post">
				<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
				<?php $wp_list_table->display(); ?>
			</form>

		</div>
	<?php

	//http://plugins.svn.wordpress.org/custom-list-table-example/trunk/list-table-example.php
}

### approved email

function mdpartners_approved_email_handler()
{
	$option_name = 'mdpartners_approved_email';

	try
	{
		// verify nonce
		if (!empty($_POST))
		{
			if (!wp_verify_nonce($_POST[$option_name.'_nonce'],$option_name)) throw new Exception('Error NONCE unverified');

			update_option($option_name, $_POST[$option_name]);

			$status = array
			(
				'error' => 0,
				'message' => 'Settings saved.'
			);
		}
	}
	catch (Exception $e)
	{
		$status = array
		(
			'error' => 1,
			'message' => $e->getMessage()
		);
	}

	$option = get_option($option_name);

	$nonce = '<input type="hidden" name="'. $option_name .'_nonce" value="' . wp_create_nonce($option_name) . '" />';

	include_once __DIR__ . '/inc/approved-email.php';
}

### denied email

function mdpartners_denied_email_handler()
{
	$option_name = 'mdpartners_denied_email';

	try
	{
		// verify nonce
		if (!empty($_POST))
		{
			if (!wp_verify_nonce($_POST[$option_name.'_nonce'],$option_name)) throw new Exception('Error NONCE unverified');

			update_option($option_name, $_POST[$option_name]);

			$status = array
			(
				'error' => 0,
				'message' => 'Settings saved.'
			);
		}
	}
	catch (Exception $e)
	{
		$status = array
		(
			'error' => 1,
			'message' => $e->getMessage()
		);
	}

	$option = get_option($option_name);

	$nonce = '<input type="hidden" name="'. $option_name .'_nonce" value="' . wp_create_nonce($option_name) . '" />';

	include_once __DIR__ . '/inc/denied-email.php';
}

if(!class_exists('WP_List_Table')){
   require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

### members table actions

function mdpartners_action_delete($arr)
{
	global $wpdb;

	$wpdb->hide_errors();

	if (is_array($arr))
	{
		foreach ($arr as $id)
		{
			$query = $wpdb->prepare('DELETE FROM '. md_members_tb() .' WHERE `id` = %s', array($id));

			$r = $wpdb->query($query);

			// check for error
			if ($r === FALSE)
			{
				$error = mysql_error($wpdb->dbh);

				throw new Exception('Unable to delete user(s), please try again');
			}
		}
	}

	return TRUE;
}

function mdpartners_action_deny($arr, $silently = false)
{
	global $wpdb;

	$wpdb->hide_errors();

	if (is_array($arr))
	{
		$denied_email = get_option('mdpartners_denied_email');

		foreach ($arr as $id)
		{
			if
			(
				! $silently AND
				! empty($denied_email['send_email']) AND
				! empty($denied_email['from_email']) AND
				! empty($denied_email['subject']) AND
				! empty($denied_email['body'])
			)
			{
				foreach ($denied_email as $n => $v)
				{
					$denied_email[$n] = stripslashes($v);
				}

				$user = $wpdb->get_row($wpdb->prepare('SELECT * FROM '. md_members_tb() .' WHERE `id` = %s',array($id)));

				if (!empty($user->id) AND !empty($user->email))
				{
					$search_arr = array( '[url]' );
					$replace_arr = array( get_bloginfo( 'url' ) );
					$denied_email['body'] = str_replace( $search_arr, $replace_arr, $denied_email['body'] );

					$email_to = $user->email;

					$headers = 'From: '. ($denied_email['from_name']?$denied_email['from_name']:$denied_email['from_email']) .' <'. $denied_email['from_email'] .'>' . "\r\n\\";

					wp_mail($email_to,$denied_email['subject'],$denied_email['body'],$headers);
				}
			}

			$query = $wpdb->prepare
			("
				UPDATE ". md_members_tb() ."
				SET `status` = 'denied'
				WHERE `id` = %s
			", array($id));

			$r = $wpdb->query($query);

			// check for error
			if ($r === FALSE)
			{
				$error = mysql_error($wpdb->dbh);
				throw new Exception('Unable to save, please try again');
			}
		}
	}

	return TRUE;
}

function mdpartners_action_approve($ids)
{
	global $wpdb;

	$wpdb->hide_errors();

	$approved_email = get_option('mdpartners_approved_email');

	if (is_array($ids))
	{
		foreach ($ids as $id)
		{
			$user = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM '. md_members_tb() .' WHERE `id` = %s', array( $id ) ) );
			if ( ! isset( $user->id ) ) {
				throw new Exception('Unable to find user, please try again');
			}
			$random_password = '';
			if ( ! empty( $user->password ) ) {
				$sql = "UPDATE ". md_members_tb() ." SET `status` = 'approved' WHERE `id` = %s";
				$sql_val = array( $id );
			} else {
				// generate random password
				$random_password = wp_generate_password();
				$sql = "UPDATE ". md_members_tb() ." SET `status` = 'approved', `password` = %s WHERE `id` = %s";
				$sql_val = array( wp_hash_password( $random_password ), $id );
			}
			$r = $wpdb->query( $wpdb->prepare( $sql, $sql_val ) );
			// check for error
			if ($r === FALSE) {
				throw new Exception('Unable to save, please try again');
			}
			$is_from_set = ! empty( $approved_email['from_email'] );
			$is_subject_set = ! empty( $approved_email['subject'] );
			$is_body_set = ! empty( $approved_email['body'] );
			// affected row (only if there was a change in status)
			if ($r && $is_from_set && $is_subject_set && $is_body_set) {
				foreach ($approved_email as $n => $v) {
					$approved_email[$n] = stripslashes( $v );
				}
				$search_arr = array( '{password_message}', '{password}', '{site_url}' );
				$password_message = $random_password ? 'A random password has been generated for you: ' . $random_password : '' ;
				$replace_arr = array( $password_message, $random_password , get_bloginfo( 'url' ) );
				$approved_email['body'] = str_replace( $search_arr, $replace_arr, $approved_email['body'] );
				if ( ! empty( $user->id ) AND ! empty( $user->email ) ) {
					$email_to = $user->email;
					$headers = 'From: '. ($approved_email['from_name']?$approved_email['from_name']:$approved_email['from_email']) .' <'.	$approved_email['from_email'] .'>' . "\r\n\\";
					wp_mail($email_to,$approved_email['subject'],$approved_email['body'],$headers);
				}
			}
		}
	}

	return TRUE;
}

### members table

class VM_Partner_Table extends WP_List_Table
{
	private $_counts = null;

	function __construct()
	{
		parent::__construct( array
		(
			'singular'	=> 'mdpartners',
			'plural'	=> 'mdpartners',
		));
	}

	function get_counts()
	{
		if ( ! isset( $this->_counts ) )
		{
			global $wpdb;

			$this->_counts = $wpdb->get_row
			("
				SELECT
					COUNT(*) AS `all`,
					COUNT(IF(`status`='pending',1,NULL)) AS `pending`,
					COUNT(IF(`status`='approved',1,NULL)) AS `approved`,
					COUNT(IF(`status`='denied',1,NULL)) AS `denied`,
					COUNT(IF(`status`='denied',1,NULL)) AS `denied`
				FROM ". md_members_tb() ."
			");
		}

		return $this->_counts;
	}

	function get_current_view()
	{
		return ! empty( $_GET['view'] ) ? $_GET['view'] : 'all' ;
	}

	function is_view( $view )
	{
		$current_view = $this->get_current_view();

		return ( $current_view == $view ) ? true : false ;
	}

	function get_views()
	{
		$views = array();

		$is_all = $this->is_view( 'all' ) ? 'current' : '' ;

		$counts = $this->get_counts();

		$current_view = $this->get_current_view();

		if ( $counts->pending || 'pending' == $current_view )
		{
			$is_pending = $this->is_view( 'pending' ) ? 'current' : '' ;

			array_push( $views, sprintf( '<a class="%s" href="?page=%s&view=%s">%s <span class="count">(%s)</span></a>', $is_pending, $_REQUEST['page'], 'pending', __('Pending'), $counts->pending ) );
		}

		if ( $counts->approved || 'approved' == $current_view  )
		{
			$is_approved = $this->is_view( 'approved' ) ? 'current' : '' ;

			array_push( $views, sprintf( '<a class="%s" href="?page=%s&view=%s">%s <span class="count">(%s)</span></a>', $is_approved, $_REQUEST['page'], 'approved', __('Approved'), $counts->approved ) );
		}

		if ( $counts->denied || 'denied' == $current_view  )
		{
			$is_denied = $this->is_view( 'denied' ) ? 'current' : '' ;

			array_push( $views, sprintf( '<a class="%s" href="?page=%s&view=%s">%s <span class="count">(%s)</span></a>', $is_denied, $_REQUEST['page'], 'denied', __('Denied'), $counts->denied ) );
		}

		if ( count( $views ) )
		{
			// add to the begining of the list
			array_unshift( $views, sprintf( '<a class="%s" href="?page=%s&view=%s">%s <span class="count">(%s)</span></a>', $is_all, $_REQUEST['page'], 'all', __('All'), $counts->all ) );
		}

		return $views;
	}

	function get_columns()
	{
		return array
		(
			'cb' => '<input type="checkbox" />',
			'name' => __('Name'),
			'email' => __('Email'),
			//'title' => __('Title'),
			//'organization' => __('Organization'),
			'status' => __('Status'),
			'created' => __('Date'),
		);
	}

	function get_sortable_columns()
	{
		return array
		(
			'name' => array( 'first_name', false ),
			'email' => array( 'email', false ),
			'organization' => array( 'organization', false ),
			'created' => array( 'created', false ),
		);
	}

	function column_default( $item, $name )
	{
		return $item->$name;
	}

	function column_cb( $item )
	{
		return sprintf( '<input type="checkbox" name="checked[]" value="%s" />', $item->id );
	}

	function column_name( $item )
	{
		$_wpnonce = wp_create_nonce( 'single-' . $this->_args['plural'] );

		$view = $this->get_current_view();

		$url = add_query_arg( array( 'page' => $_REQUEST['page'], 'view'=> $view, '_wpnonce' => $_wpnonce, 'id' => $item->id ), $_SERVER['REQUEST_URI'] );

		$pattern = '<a href="%s">%s</a>';

		$actions = array
		(
			'action-approve' => sprintf( $pattern, esc_url( add_query_arg( array( 'action' => 'approve' ), $url ) ), __( 'Approve' ) ),
			'action-deny' => sprintf( $pattern, esc_url( add_query_arg( array( 'action' => 'deny' ), $url ) ), __( 'Deny' ) ),
			'action-deny-silently' => sprintf( $pattern, esc_url( add_query_arg( array( 'action' => 'deny-silently' ), $url ) ), __( 'Deny Silently' ) ),
			'action-delete' => sprintf( $pattern, esc_url( add_query_arg( array( 'action' => 'delete' ), $url ) ), __( 'Delete' ) ),
		);

		if ( 'approved' == $item->status )
		{
			unset( $actions['action-approve'] );
		}

		if ( 'denied' == $item->status )
		{
			unset( $actions['action-deny'], $actions['action-deny-silently'] );
		}

		return $item->first_name . ' ' . $item->last_name . $this->row_actions( $actions ) ;
	}

	function column_status( $item )
	{
		return ucfirst( $item->status );
	}

	function column_created( $item )
	{
		return date( 'm/d/Y', strtotime( $item->created ) );
	}

	function extra_tablenav( $which )
	{
		if ( 'top' == $which )
		{
			//echo '<div class="export-link" style="float:left; line-height:30px;"><a style="text-decoration:none;" href="'. plugins_url( 'inc/export-csv.php', __FILE__ ) . '">Export CSV Data File</a></div>';
		}
	}

	function no_items()
	{
		echo "No members found.";
	}

	function get_bulk_actions()
	{
		return array(
			'approve' => __( 'Approve' ),
			'deny' => __( 'Deny' ),
			'deny-silently' => __( 'Deny Silently' ),
			'delete' => __( 'Delete' ),
		);
	}

	function process_bulk_action()
	{
		if ( $this->current_action() )
		{
			if ( ! empty( $_POST['checked'] ) )
			{
				$name = 'bulk-' . $this->_args['plural'];
			}
			else
			{
				$name = 'single-' . $this->_args['plural'];

				$_POST['checked'] = array ( $_GET['id'] );
			}

			if ( false === wp_verify_nonce( $_REQUEST['_wpnonce'], $name ) )
			{
				wp_die( 'Unable to perform action "_wpnonce" is incorrect' );
			}

			if ( 'approve' === $this->current_action() )
			{
				mdpartners_action_approve( $_POST['checked'] );
			}

			if( 'deny' === $this->current_action() )
			{
				mdpartners_action_deny( $_POST['checked'] );
			}

			if( 'deny-silently' === $this->current_action() )
			{
				mdpartners_action_deny( $_POST['checked'], true );
			}

			if( 'delete' === $this->current_action() )
			{
				mdpartners_action_delete( $_POST['checked'] );
			}
		}
	}

    function prepare_items()
	{
		global $wpdb;

		$columns = $this->get_columns();

		$hidden = array();

		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$query = "SELECT * FROM " . md_members_tb();

		$query_where = '';

		$view = $this->get_current_view();

		if ( 'all' !== $view )
		{
			$query_where = $wpdb->prepare( ' WHERE `status` = %s', $view );

			$query .= $query_where;
		}

		$orderby = ! empty( $_GET['orderby'] ) ? esc_sql( $_GET['orderby'] ) : 'created' ;

		$order = ! empty( $_GET['order'] ) ? esc_sql( $_GET['order'] ) : 'DESC' ;

		$query .= ' ORDER BY `' . $orderby . '` '. $order ;

		$per_page = 50;

		$current_page = $this->get_pagenum();

		if( ! empty( $current_page ) && ! empty( $per_page ) )
		{
			$offset = ($current_page-1) * $per_page;

			$query .= ' LIMIT ' . (int)$offset . ',' . (int)$per_page ;
		}

		$this->items = $wpdb->get_results($query);

		$sql = 'SELECT COUNT(*) FROM '  . md_members_tb() . $query_where;
		$total_items = $wpdb->get_var( $sql );

		$this->set_pagination_args( array
		(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		) );
    }
}
