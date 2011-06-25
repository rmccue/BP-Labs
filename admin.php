<?php
/**
 * @package BP_Labs
 * @subpackage Administration
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) )
	exit;

/**
 * Admin option stuff.
 *
 * @since 1.1
 */
class BPLabs_Admin {
	/**
	 * Constructor.
	 *
	 * @since 1.1
	 */
	function __construct() {
		if ( function_exists( 'bp_core_admin_hook' ) )  // TODO: Update this when BuddyPress 1.3 is out
			$admin_hook = bp_core_admin_hook();
		elseif ( is_multisite() && ( !defined( 'BP_ENABLE_MULTIBLOG' ) || !BP_ENABLE_MULTIBLOG ) )
			$admin_hook = 'network_admin_menu';
		else
			$admin_hook = 'admin_menu';

		add_action( $admin_hook, array( $this, 'setup_menu' ) );
	}

	/**
	 * Sets up the admin menu
	 *
	 * @since 1.1
	 */
	function setup_menu() {
		add_action( 'load-buddypress_page_bplabs', array( $this, 'init' ) );
		add_submenu_page( 'bp-general-settings', __( 'BP Labs', 'bpl' ), __( 'Labs', 'bpl' ), 'manage_options', 'bplabs', array( $this, 'admin_page' ) );
	}

	/**
	 * Initialise common elements for all pages of the admin screen.
	 * Add metaboxes and contextual help to admin screen.
	 *
	 * @since 1.1
	 */
	function init() {
		add_screen_option( 'layout_columns', array( 'max' => 2 ) );

		// Main tab
		add_meta_box( 'bpl-likethis', __( 'Love BP Labs?', 'bpl' ), array( $this, '_like_this_plugin' ), 'buddypress_page_bplabs_settings_metabox', 'side', 'default' );
		add_meta_box( 'bpl-paypal', __( 'Give Kudos', 'bpl' ), array( $this, '_paypal' ), 'buddypress_page_bplabs_settings_metabox', 'side', 'default' );
		add_meta_box( 'bpl-latest', __( 'Latest News', 'bpl' ), array( $this, '_metabox_latest_news' ), 'buddypress_page_bplabs_settings_metabox', 'side', 'default' );

		// Support tab
		add_meta_box( 'bpl-helpushelpyou', __( 'Help Us Help You', 'bpl' ),     array( $this, '_helpushelpyou'), 'buddypress_page_bplabs_support_metabox', 'side', 'default' );
		add_meta_box( 'bpl-paypal', __( 'Give Kudos', 'bpl' ), array( $this, '_paypal' ), 'buddypress_page_bplabs_support_metabox', 'side', 'default' );
		add_meta_box( 'bpl-latest', __( 'Latest News', 'bpl' ), array( $this, '_metabox_latest_news' ), 'buddypress_page_bplabs_support_metabox', 'side', 'default' );
	}

	/**
	 * Outputs admin page HTML
	 *
	 * @global int $screen_layout_columns Number of columns shown on this admin page
	 * @since 1.1
	 */
	function admin_page() {
		global $screen_layout_columns;

		if ( empty( $_GET['tab'] ) )
			$tab = 'settings';
		else
			$tab = 'support';

		$url      = network_admin_url( 'admin.php?page=bplabs' );
		$settings = array();
	?>

		<style type="text/css">
		#bpl-helpushelpyou ul {
			list-style: disc;
			padding-left: 2em;
		}
		#bpl-paypal .inside {
			text-align: center;
		}
		</style>

		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>

			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_attr( $url ); ?>"                  class="nav-tab <?php if ( 'settings' == $tab ) : ?>nav-tab-active<?php endif; ?>"><?php _e( 'BP Labs', 'bpl' );     ?></a>
				<a href="<?php echo esc_attr( $url . '&tab=support' ); ?>" class="nav-tab <?php if ( 'support' == $tab  ) : ?>nav-tab-active<?php endif; ?>"><?php _e( 'Get Support', 'bpl' ); ?></a>
			</h2>

			<div id="poststuff" class="metabox-holder<?php echo 2 == $screen_layout_columns ? ' has-right-sidebar' : ''; ?>">
				<div id="side-info-column" class="inner-sidebar">
					<?php
					if ( 'support' == $tab )
						do_meta_boxes( 'buddypress_page_bplabs_support_metabox', 'side', $settings );
					else
						do_meta_boxes( 'buddypress_page_bplabs_settings_metabox', 'side', $settings );
					?>
				</div>

				<div id="post-body" class="has-sidebar">
					<div id="post-body-content" class="has-sidebar-content">
						<?php
						if ( 'support' == $tab )
							$this->_admin_page_support();
						else
							$this->_admin_page_settings();
						?>
					</div><!-- #post-body-content -->
				</div><!-- #post-body -->

			</div><!-- #poststuff -->
		</div><!-- .wrap -->

	<?php
	}

	/**
	 * Support tab content for the admin page
	 *
	 * @since 1.1
	 */
	protected function _admin_page_support() {
	?>

		<p><?php printf( __( "All of BP Labs' experiments are in <a href='%s'>beta</a>, and come with no guarantees. They work best with the latest versions of WordPress and BuddyPress.", 'bpl' ), 'http://en.wikipedia.org/wiki/Software_release_life_cycle#Beta' ); ?></p>
		<p><?php printf( __( 'If you have problems with this plugin or find a bug, please contact me by leaving a message on the <a href="%s">support forums</a>.', 'bpl' ), 'http://buddypress.org/community/groups/bp-labs/' ); ?></p>

	<?php
	}

	/**
	 * Main tab's content for the admin page
	 *
	 * @since 1.1
	 */
	protected function _admin_page_settings() {
		echo 'settings';
	}

	/**
	 * Latest news metabox
	 *
	 * @param array $settings Plugin settings (from DB)
	 * @since 1.1
	 */
	function _metabox_latest_news( $settings) {
		$rss = fetch_feed( 'http://feeds.feedburner.com/BYOTOS' );
		if ( !is_wp_error( $rss ) ) {
			$content = '<ul>';
			$items = $rss->get_items( 0, $rss->get_item_quantity( 3 ) );

			foreach ( $items as $item )
				$content .= '<li><p><a href="' . esc_url( $item->get_permalink(), null, 'display' ) . '">' . apply_filters( 'bpl_metabox_latest_news', stripslashes( $item->get_title() ) ) . '</a></p></li>';

			echo $content;

		} else {
			echo '<ul><li class="rss">' . __( 'No news found at the moment', 'bpl' ) . '</li></ul>';
		}
	}

	/**
	 * "Help Us Help You" metabox
	 *
	 * @global wpdb $wpdb WordPress database object
	 * @global string $wp_version WordPress version number
	 * @global WP_Rewrite $wp_rewrite WordPress Rewrite object for creating pretty URLs
	 * @global object $wp_rewrite
	 * @param array $settings Plugin settings (from DB)
	 * @since 1.1
	 */
	function _helpushelpyou( $settings ) {
		global $wpdb, $wp_rewrite, $wp_version;

		$active_plugins = array();
		$all_plugins = apply_filters( 'all_plugins', get_plugins() );

		foreach ( $all_plugins as $filename => $plugin ) {
			if ( 'BP Labs' != $plugin['Name'] && 'BuddyPress' != $plugin['Name'] && is_plugin_active( $filename ) )
				$active_plugins[] = $plugin['Name'] . ': ' . $plugin['Version'];
		}
		natcasesort( $active_plugins );

		if ( !$active_plugins )
			$active_plugins[] = __( 'No other plugins are active', 'bpl' );

		if ( is_multisite() ) {
			if ( is_subdomain_install() )
				$is_multisite = __( 'subdomain', 'bpl' );
			else
				$is_multisite = __( 'subdirectory', 'bpl' );

		} else {
			$is_multisite = __( 'no', 'bpl' );
		}

		if ( 1 == constant( 'BP_ROOT_BLOG' ) )
			$is_bp_root_blog = __( 'standard', 'bpl' );
		else
			$is_bp_root_blog = __( 'non-standard', 'bpl' );

		$is_bp_default_child_theme = __( 'no', 'bpl' );
		$theme = current_theme_info();

		if ( 'BuddyPress Default' == $theme->parent_theme )
			$is_bp_default_child_theme = __( 'yes', 'bpl' );

		if ( 'BuddyPress Default' == $theme->name )
			$is_bp_default_child_theme = __( 'n/a', 'bpl' );

	  if ( empty( $wp_rewrite->permalink_structure ) )
			$custom_permalinks = __( 'default', 'bpl' );
		else
			if ( strpos( $wp_rewrite->permalink_structure, 'index.php' ) )
				$custom_permalinks = __( 'almost', 'bpl' );
			else
				$custom_permalinks = __( 'custom', 'bpl' );
	?>
		<p><?php _e( "If you have trouble, a little information about your site goes a long way.", 'bpl' ); ?></p>

		<h4><?php _e( 'Versions', 'bpl' ) ?></h4>
		<ul>
			<li><?php printf( __( 'BP Labs: %s', 'bpl' ), BP_LABS_VERSION ); ?></li>
			<li><?php printf( __( 'BP_ROOT_BLOG: %s', 'bpl' ), $is_bp_root_blog ); ?></li>
			<li><?php printf( __( 'BuddyPress: %s', 'bpl' ), BP_VERSION ); ?></li>
			<li><?php printf( __( 'MySQL: %s', 'bpl' ), $wpdb->db_version() ); ?></li>
			<li><?php printf( __( 'Permalinks: %s', 'bpl' ), $custom_permalinks ); ?></li>
			<li><?php printf( __( 'PHP: %s', 'bpl' ), phpversion() ); ?></li>
			<li><?php printf( __( 'WordPress: %s', 'bpl' ), $wp_version ); ?></li>
			<li><?php printf( __( 'WordPress multisite: %s', 'bpl' ), $is_multisite ); ?></li>
		</ul>

		<h4><?php _e( 'Theme', 'bpl' ) ?></h4>
		<ul>
			<li><?php printf( __( 'BP-Default child theme: %s', 'bpl' ), $is_bp_default_child_theme ); ?></li>
			<li><?php printf( __( 'Current theme: %s', 'bpl' ), $theme->name ); ?></li>
		</ul>

		<h4><?php _e( 'Active Plugins', 'bpl' ); ?></h4>
		<ul>
			<?php foreach ( $active_plugins as $plugin ) : ?>
				<li><?php echo $plugin; ?></li>
			<?php endforeach; ?>
		</ul>
	<?php
	}

	/**
	 * Social media sharing metabox
	 *
	 * @since 2.0
	 * @param array $settings Plugin settings (from DB)
	 */
	function _like_this_plugin( $settings ) {
	?>

		<p><?php _e( 'Why not do any or all of the following:', 'bpl' ) ?></p>
		<ul>
			<li><p><a href="http://wordpress.org/extend/plugins/bp-labs/"><?php _e( 'Give it a five star rating on WordPress.org', 'bpl' ) ?></a>.</p></li>
			<li><p><a href="http://buddypress.org/community/groups/bp-labs/reviews/"><?php _e( 'Write a review on BuddyPress.org', 'bpl' ) ?></a>.</p></li>
			<li><p><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&amp;business=P3K7Z7NHWZ5CL&amp;lc=GB&amp;item_name=B%2eY%2eO%2eT%2eO%2eS%20%2d%20BuddyPress%20plugins&amp;currency_code=GBP&amp;bn=PP%2dDonationsBF%3abtn_donate_LG%2egif%3aNonHosted"><?php _e( 'Thank me by donating towards future development', 'bpl' ) ?></a>.</p></li>
		</ul>

	<?php
	}

	/**
	 * Paypal donate button metabox
	 */ 
	function _paypal() {
	?>

		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
			<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online.">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
		</form>

	<?php
	}
}
new BPLabs_Admin();

// And some filters.
add_filter( 'bpl_metabox_latest_news', 'wp_kses_data', 1 );  // From an external source
add_filter( 'bpl_metabox_latest_news', 'wptexturize'     );
add_filter( 'bpl_metabox_latest_news', 'convert_chars'   );
?>