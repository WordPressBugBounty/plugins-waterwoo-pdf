<?php defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { // If uninstall not called from WordPress exit
	exit();
}

/**
 * Manages Watermark PDF for WooCommerce uninstallation
 * The goal is to remove ALL plugin related data in db
 *
 * @since 2.2
 */
class WWPDF_Free_Uninstall {

	/**
	 * Constructor: manages uninstall for multisite
	 *
	 * @since 0.5
	 */
	function __construct() {

		// Check if it is a multisite uninstall - if so, run the uninstall function for each blog id
		if ( is_multisite() ) {
			global $wpdb;
			foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" ) as $blog_id ) {
				switch_to_blog( $blog_id );
				$this->uninstall();
			}
			restore_current_blog();
		}
		else {
			$this->uninstall();
		}
	}

	/**
	 * Removes all plugin data
	 * only when the relevant option is active
	 *
	 * @since 0.5
	 */
	function uninstall() {

		global $current_user;
		$user_id = $current_user->ID;

		delete_user_meta( $user_id, 'wwpdf_ignore_notice' );
		for ( $i = 2; $i <= 14; $i++ ) {
			delete_user_meta( $user_id, 'wwpdf_ignore_notice' . $i );
		}

		if ( 'yes' !== get_option( 'wwpdf_delete_checkbox' ) ) {
			return;
		}

		foreach ( [
			'wwpdf_global',
			'wwpdf_files',
			'wwpdf_font_premium',
			'wwpdf_footer_input_premium',
			'wwpdf_footer_color_premium',
			'wwpdf_footer_size_premium',
			'wwpdf_footer_finetune_Y',
			'wwpdf_footer_finetune_Y_premium',
			'wwpdf_disable_printing',
			'wwpdf_disable_copy',
			'wwpdf_disable_mods',
			'wwpdf_disable_annot',
			'wwpdf_password',
			'wwpdf_delete_checkbox',
			// BYE BYE!
		] as $option ) {
				delete_option( $option );
		}

	}

}
new WWPDF_Free_Uninstall();