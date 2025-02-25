<?php
/**
 * Load the shared WPML Forms library, on demand.
 *
 * =================
 * Usage
 * =================
 * $vendor_path = [ path to the root of your relative vendor directory housing this repository, no training slash ]
 * $vendor_url = [ URL of the root of your relative vendor directory housing this repository, no trailing slash ]
 * require_once( $vendor_path . '/wpml/wpml-forms/loader.php' );
 * wpml_forms_initialize( $vendor_path . '/wpml/wpml-forms', $vendor_url . '/wpml/wpml-forms' );
 *
 * =================
 * Restrictions
 * =================
 * - Assets are registered at init:1: doing it earlier will cause problems with core assets registered at init:0
 * - Their handles are stored in constants that you can use as dependencies, on assets registered after init:-100.
 *
 * @package wpml/wpml-forms
 */

/**
 * WPML Forms version - increase after every major update.
 */
$wpml_forms_version = '1.0.4';

/**
 * =================
 * ||   WARNING   ||
 * =================
 *
 * DO NOT EDIT below this line.
 */

if ( ! function_exists( 'wp_normalize_path' ) ) {
	function wp_normalize_path( $path ) {
		return $path;
	}
}

global $wpml_forms_versions;

if ( ! isset( $wpml_forms_versions ) ) {
	$wpml_forms_versions = [];
}

if ( ! isset( $wpml_forms_versions[ $wpml_forms_version ] ) ) {
	// Initialize the path to this version.
	$wpml_forms_versions[ $wpml_forms_version ] = array(
		'path' => wp_normalize_path( dirname( __FILE__ ) ),
	);
}

if ( ! function_exists( 'wpml_forms_initialize' ) ) {

	/**
	 * @param string $vendor_path Path to the root of your relative vendor directory housing this repository (no trailing slash).
	 * @param string $vendor_url URL of the root of your relative vendor directory housing this repository, no trailing slash.
	 */
	function wpml_forms_initialize( $vendor_path, $vendor_url ) {
		global $wpml_forms_versions;

		// Make sure we compare with the canonical path.
		if ( is_link( $vendor_path ) ) {
			$vendor_path = readlink( $vendor_path );
		}

		$vendor_path = wp_normalize_path( $vendor_path );
		$vendor_path = untrailingslashit( $vendor_path );
		$vendor_url  = untrailingslashit( $vendor_url );

		// Save the url in the version with a matching path.
		foreach ( $wpml_forms_versions as $version => $data ) {
			if ( $wpml_forms_versions[ $version ]['path'] === $vendor_path ) {
				$wpml_forms_versions[ $version ]['url'] = $vendor_url;
				break;
			}
		}
	}
}

if ( ! function_exists( 'wpml_forms_latest' ) ) {
	function wpml_forms_latest() {
		global $wpml_forms_versions;

		// Find the latest version.
		$latest = '';
		foreach ( $wpml_forms_versions as $version => $data ) {
			if ( version_compare( $version, $latest, '>' ) ) {
				$latest = $version;
			}
		}

		return $latest;
	}
}

if ( ! function_exists( 'wpml_forms_path' ) ) {
	function wpml_forms_path() {
		global $wpml_forms_versions;

		$latest = wpml_forms_latest();

		if ( $latest ) {
			return $wpml_forms_versions[ $latest ]['path'];
		}

		return null;
	}
}

if ( ! function_exists( 'wpml_forms_plugins_loaded' ) ) {
	/**
	 * Function hooked to the `plugins_loaded` action as early as possible.
	 */
	function wpml_forms_plugins_loaded() {
		spl_autoload_register( 'wpml_forms_autoloader' );
	}
}

if ( function_exists( 'add_action' ) ) {
	add_action( 'plugins_loaded', 'wpml_forms_plugins_loaded', -PHP_INT_MAX );
}

if ( ! function_exists( 'wpml_forms_autoloader' ) ) {
	function wpml_forms_autoloader( $className ) {

		$prefix = 'WPML\\Forms';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $className, $len ) !== 0 ) {
			return;
		}

		if ( $prefix === $className ) {
			$relativeClass = '\\Forms';
		} else {
			$relativeClass = substr( $className, $len );
		}

		$baseDir   = wpml_forms_path() . '/classes';
		$classPath = $baseDir . str_replace( '\\', '/', $relativeClass ) . '.php';

		if ( file_exists( $classPath ) ) {
			require_once $classPath;

			return true;
		}
	}
}

if ( ! function_exists( 'wpml_forms_bulk_registration_option_name' ) ) {
	function wpml_forms_bulk_registration_option_name( $plugin_file_path ) {
		return sprintf( 'wpml_forms_bulk_registration-%s', basename( dirname( $plugin_file_path ) ) );
	}
}
