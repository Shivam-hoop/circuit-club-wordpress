<?php

namespace WPML\Forms\Loader;

class WpFormsStatus implements AddonStatus {

	/**
	 * Checks if Add-On is active.
	 *
	 * @return bool
	 */
	public function isActive() {
		return (bool) did_action( 'wpforms_loaded' );
	}
}
