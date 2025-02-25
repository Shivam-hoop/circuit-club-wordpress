<?php

namespace WPML\Forms\Hooks\WpForms;

use WPML_Package;

class Factory {

	/**
	 * @param int $package
	 *
	 * @return WPML_Package
	 */
	public function getWpmlPackage( int $package ): WPML_Package {
		return new WPML_Package( $package );
	}

	/**
	 * @param array $formData
	 *
	 * @return AteFieldAdjuster
	 */
	public function getAteFieldAdjuster( array $formData ): AteFieldAdjuster {
		return new AteFieldAdjuster( $formData );
	}

	public function getAteFieldReorder( array $formData ): AteFieldReorder {
		return new AteFieldReorder( $formData, $this->getAteFieldAdjuster( $formData ) );
	}
}
