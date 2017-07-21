<?php /*

**************************************************************************

Plugin Name:  Dashboard: Latest Spam
Plugin URI:   http://www.viper007bond.com/wordpress-plugins/dashboard-latest-spam/
Description:  Displays the latest spam on your WordPress 2.5+ dashboard so you can make sure there were no false positives. Uses <a href="http://wordpress.org/extend/plugins/defensio-anti-spam/">Defensio</a> to hide obvious spam if you have it installed.
Version:      1.0.1
Author:       Viper007Bond
Author URI:   http://www.viper007bond.com/

**************************************************************************/

class DashboardLatestSpam {

	// Class initialization
	function DashboardLatestSpam() {
		// Load up the localization file if we're using WordPress in a different language
		// Place it in this plugin's folder and name it "dashspam-[value in wp-config].mo"
		load_plugin_textdomain( 'dashspam', '/wp-content/plugins/dashboard-latest-spam' );

		// Add the widget to the dashboard
		add_action( 'wp_dashboard_setup',  array(&$this, 'register_widget') );
		add_filter( 'wp_dashboard_widgets', array(&$this, 'add_widget') );
	}


	// Register this widget -- we use a hook/function to make the widget a dashboard-only widget
	function register_widget() {
		if ( !$widget_options = get_option( 'dashboard_widget_options' ) )
			$widget_options = array();

		if ( !isset($widget_options[$widget_id]['usedefensio']) )
			$widget_options[$widget_id]['usedefensio'] = 1;

		if ( function_exists('defensio_generate_spaminess_filter') && 1 == $widget_options[$widget_id]['usedefensio'] )
			$all_link = 'edit-comments.php?page=defensio-anti-spam/defensio.php';
		else
			$all_link = 'edit-comments.php?comment_status=spam';

		wp_register_sidebar_widget( 'dashboard_latest_spam', __( 'Latest Spam', 'dashspam' ), array(&$this, 'widget'), array( 'all_link' => $all_link, 'width' => 'half', 'height' => 'double' ) );
		wp_register_widget_control( 'dashboard_latest_spam', __( 'Latest Spam', 'dashspam' ), array(&$this, 'widget_control'), array(), array( 'widget_id' => 'dashboard_latest_spam' ) );
	}


	// Modifies the array of dashboard widgets and adds this plugin's
	function add_widget( $widgets ) {
		global $wp_registered_widgets;

		if ( !isset($wp_registered_widgets['dashboard_latest_spam']) || !current_user_can( 'manage_options' ) ) return $widgets;

		array_splice( $widgets, 0, 0, 'dashboard_latest_spam' );
		return $widgets;
	}


	// Output the widget contents
	function widget( $args ) {
		global $comment, $wpdb;
		extract( $args, EXTR_SKIP );

		echo $before_widget;

		echo $before_title;
		echo $widget_name;
		echo $after_title;

		if ( !$widget_options = get_option( 'dashboard_widget_options' ) )
			$widget_options = array();

		if ( !isset($widget_options[$widget_id]) )
			$widget_options[$widget_id] = array();

		if ( !isset($widget_options[$widget_id]['items']) )
			$widget_options[$widget_id]['items'] = 10;

		if ( !isset($widget_options[$widget_id]['usedefensio']) )
			$widget_options[$widget_id]['usedefensio'] = 1;


		if ( function_exists('defensio_generate_spaminess_filter') && 1 == $widget_options[$widget_id]['usedefensio'] )
			$comments = $wpdb->get_results( "SELECT *, IFNULL(spaminess, 1) as spaminess FROM $wpdb->comments LEFT JOIN $wpdb->prefix"."defensio ON $wpdb->comments".".comment_ID = $wpdb->prefix"."defensio.comment_ID WHERE comment_approved = 'spam' " . defensio_generate_spaminess_filter() . " ORDER BY " . defensio_order_2_sql(get_option(defensio_user_unique_option_key('order'))) . " LIMIT " . $widget_options[$widget_id]['items'] );
		else
			list( $comments ) = _wp_get_comment_list( 'spam', FALSE, 0, $widget_options[$widget_id]['items'] );


		if ( $comments ) {
			echo "				<ul id='dashboard-spam-comments-list'>\n";

			foreach ( $comments as $comment ) {
				$comment_post_url = get_permalink( $comment->comment_post_ID );
				$comment_post_title = get_the_title( $comment->comment_post_ID );
				$comment_post_link = "<a href='$comment_post_url'>$comment_post_title</a>";
				$comment_link = '<a class="comment-link" href="' . get_comment_link() . '">#</a>';
				$comment_meta = sprintf( __( 'From <strong>%1$s</strong> on %2$s %3$s' ), get_comment_author(), $comment_post_link, $comment_link );
?>
					<li class='comment-meta'>
						&#8220;<?php comment_excerpt(); ?>&#8221;<br />
						<?php echo $comment_meta; ?>
					</li>
<?php
			}

			echo "				</ul>\n";
		} else {
			if ( function_exists('defensio_generate_spaminess_filter') && 1 == $widget_options[$widget_id]['usedefensio'] )
				echo '				<p>' . __( "Congratulations! You don't have any non-obvious spam in your database!", 'dashspam' ) . "</p>\n";
			else
				echo '				<p>' . __( "Congratulations! You don't have any spam in your database!", 'dashspam' ) . "</p>\n";
		}

		echo $after_widget;
	}


	// Outputs the options view of the widget as well as saves the options on form submit
	function widget_control( $args ) {
		extract( $args );
		if ( !$widget_id )
			return false;

		if ( !$widget_options = get_option( 'dashboard_widget_options' ) )
			$widget_options = array();

		if ( !isset($widget_options[$widget_id]) )
			$widget_options[$widget_id] = array();

		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && isset($_POST['widget-latest-spam']) ) {
			$widget_options[$widget_id] = stripslashes_deep( $_POST['widget-latest-spam'] );

			if ( !isset($widget_options[$widget_id]['items']) )
				$widget_options[$widget_id]['items'] = 10;

			$widget_options[$widget_id]['items'] = (int) $widget_options[$widget_id]['items'];

			if ( !isset($widget_options[$widget_id]['usedefensio']) )
				$widget_options[$widget_id]['usedefensio'] = 0;

			update_option( 'dashboard_widget_options', $widget_options );
		}

		if ( !isset($widget_options[$widget_id]['items']) )
			$widget_options[$widget_id]['items'] = 10;

		if ( !isset($widget_options[$widget_id]['usedefensio']) )
			$widget_options[$widget_id]['usedefensio'] = 1;

?>
	<p>
		<label for="latest-spam-count"><?php _e('How many spam comments would you like to display?', 'dashspam' ); ?>
			<select id="latest-spam-count" name="widget-latest-spam[items]">
				<?php
					for ( $i = 5; $i <= 50; $i = $i + 5 )
						echo "<option value='$i'" . ( $widget_options[$widget_id]['items'] == $i ? " selected='selected'" : '' ) . ">$i</option>";
				?>
			</select>
		</label>
	</p>
<?php if ( function_exists('defensio_generate_spaminess_filter') ) : ?>
	<p>
		<label for="latest-spam-usedefensio">
			<input id="latest-spam-usedefensio" name="widget-latest-spam[usedefensio]" type="checkbox" value="1"<?php if ( 1 == $widget_options[$widget_id]['usedefensio'] ) echo ' checked="checked"'; ?> />
			<?php _e('Use Defensio to hide obvious spam?', 'dashspam' ); ?>
		</label>
	</p>
<?php
		endif;
	}
}

// Start this plugin once all other plugins are fully loaded
add_action( 'plugins_loaded', create_function( '', 'global $DashboardLatestSpam; $DashboardLatestSpam = new DashboardLatestSpam();' ) );


// Cause a fatal error on activation on purpose if the user's WordPress version is too old
if ( !file_exists(ABSPATH . 'wp-admin/includes/dashboard.php') )
	exit( sprintf( __('The latest spam dashboard widget requires WordPress 2.5.0 or newer. <a href="%s" target="_blank">Please update!</a>', 'dashspam'), 'http://codex.wordpress.org/Upgrading_WordPress' ) );

?>