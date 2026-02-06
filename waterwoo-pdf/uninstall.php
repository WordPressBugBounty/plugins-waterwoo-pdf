<?php defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { // If uninstall not called from WordPress exit
	exit();
}

/**
 * Manages PDF Ink Lite uninstallation
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

		if ( '1' !== get_option( 'dlm_stamper_lnt' ) && ! is_plugin_active( 'download-monitor/download-monitor.php' ) ) {
			return;
		}

		foreach ( [
			'pdfink_attribution',

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
			'wwpdf_files_v4',

			'dlm_stamper_global',
			'dlm_stamper_files',
			'dlm_stamper_stamp',
			'dlm_stamper_font',
			'dlm_stamper_size',
			'dlm_stamper_color',
			'dlm_stamper_finetune_Y',
			'dlm_stamper_dis_printing',
			'dlm_stamper_dis_copy',
			'dlm_stamper_dis_mods',
			'dlm_stamper_dis_annot',
			'dlm_stamper_pwd',
			'dlm_stamper_lnt',
			// BYE BYE!
		] as $option ) {
			delete_option( $option );
		}

		if ( function_exists( 'edd_delete_option ' ) ) {
			foreach (
				[
					'eddimark_global',
					'eddimark_files',
					'eddimark_f_input',
					'eddimark_f_size',
					'eddimark_f_rotate',
					'eddimark_f_color',
					'eddimark_f_finetune_X',
					'eddimark_f_finetune_Y',
					'eddimark_margin_top_bottom',
					'eddimark_margin_left_right',
					'eddimark_encrypt',
					'eddimark_failure',
					'eddimark_disable_print',
					'eddimark_disable_copy',
					'eddimark_disable_mods',
					'eddimark_disable_annot',
					'eddimark_pw',
					'eddimark_lnt',
				] as $option
			) {
				edd_delete_option( $option );
			}
		}

	}

}
new WWPDF_Free_Uninstall();