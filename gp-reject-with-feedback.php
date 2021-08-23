<?php
/**
 * Plugin Name: GlotPress - Reject Translation With Feedback
 * Description: Reject GlotPress translations and feedback are organized into topics in a bbPress forum.
 * Version: 1.0.0
 */
if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

/**
* Check if GlotPress has been installed and activated
*/
function gp_reject_with_feedback_check_requirements() {
	if ( is_plugin_active( 'glotpress/glotpress.php' ) ) {
		return true;
	} else {
		add_action( 'admin_notices', 'gp_reject_with_feedback_missing_gp_notice' );
		return false;
	}
}


/**
* Show notice indicating that GlotPress is required
*/
function gp_reject_with_feedback_missing_gp_notice() {
	$class   = 'notice notice-error';
	$message = __( 'This plugin requires Glotpress to be installed and active.', 'activate_gp_reject_with_feedback' );

	printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
}

/**
 * Activate Plugin
 *
 */
function activate_gp_reject_with_feedback() {
	require_once plugin_dir_path( __FILE__ ) . 'class-gp-reject-with-feedback.php';
	if ( gp_reject_with_feedback_check_requirements() ) {
		$plugin = new GP_Reject_With_Feedback();
	}
}

activate_gp_reject_with_feedback();
