<?php
/**
 * Plugin Name: WPForms Multilingual
 * Plugin URI: https://wpml.org/documentation/related-projects/creating-multilingual-forms-using-wpforms-and-wpml/?utm_source=plugin&utm_medium=gui&utm_campaign=wpforms
 * Description: Add multilingual support for WPForms | <a href="https://wpml.org/documentation/related-projects/creating-multilingual-forms-using-wpforms-and-wpml/?utm_source=plugin&utm_medium=gui&utm_campaign=wpforms">Documentation</a>
 * Author: OnTheGoSystems
 * Author URI: https://www.onthegosystems.com/
 * Version: 0.4.0
 * Plugin Slug: wpml-wpforms
 */

define( 'WPML_WP_FORMS_VERSION', '0.4.0' );
define( 'WPML_WP_FORMS_FILE', __FILE__ );
define( 'WPML_WP_FORMS_PATH', dirname( WPML_WP_FORMS_FILE ) );
define( 'WPML_WP_FORMS_VENDOR_PATH', WPML_WP_FORMS_PATH . '/vendor' );

/**
 * 1. Initialize the code shared in `wpml/forms`.
 */
require_once WPML_WP_FORMS_VENDOR_PATH . '/wpml/forms/loader.php';
wpml_forms_initialize(
	WPML_WP_FORMS_VENDOR_PATH . '/wpml/forms',
	untrailingslashit( plugin_dir_url( WPML_WP_FORMS_FILE ) ) . '/vendor/wpml/forms'
);

function wpml_wpforms_activation_hook() {
	update_option( wpml_forms_bulk_registration_option_name( WPML_WP_FORMS_FILE ), true );
}

register_activation_hook( WPML_WP_FORMS_FILE, 'wpml_wpforms_activation_hook' );

/**
 * 2. Initialize WPML WP Forms' code.
 */
add_action( 'plugins_loaded', function() {

	$hasWPMLRequirements = function() {
		if ( ! class_exists( 'WPML_Core_Version_Check' ) ) {
			require_once WPML_WP_FORMS_VENDOR_PATH . '/wpml-shared/wpml-lib-dependencies/src/dependencies/class-wpml-core-version-check.php';
		}

		return WPML_Core_Version_Check::is_ok( WPML_WP_FORMS_PATH . '/wpml-dependencies.json' );
	};

	if ( $hasWPMLRequirements() ) {
		require_once WPML_WP_FORMS_VENDOR_PATH . '/autoload.php';

		$forms = new \WPML\Forms(
			WPML_WP_FORMS_FILE,
			\WPML\Forms\Loader\WpForms::class,
			new \WPML\Forms\Loader\WpFormsStatus()
		);
		$forms->addHooks();
	}
} );
