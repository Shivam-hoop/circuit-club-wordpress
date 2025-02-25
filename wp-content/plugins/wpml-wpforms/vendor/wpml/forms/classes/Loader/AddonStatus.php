<?php

namespace WPML\Forms\Loader;

interface AddonStatus {

	/**
	 * Checks if Add-On is active.
	 * 
	 * @return bool
	 */
	public function isActive();
}
