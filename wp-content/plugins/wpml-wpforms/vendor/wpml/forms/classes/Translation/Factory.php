<?php

namespace WPML\Forms\Translation;

class Factory {

	/**
	 * Holds translation preferences object.
	 *
	 * @var Preferences
	 */
	private $translationPreferences;

	/**
	 * WPML_Forms_Translation_Package_Factory constructor.
	 *
	 * @param Preferences $translationPreferences Translation preferences object.
	 */
	public function __construct( Preferences $translationPreferences ) {
		$this->translationPreferences = $translationPreferences;
	}

	/**
	 * Creates translation package helper.
	 *
	 * @param int    $formId Form ID.
	 * @param string $kind Translation package kind.
	 *
	 * @return Package
	 */
	public function getPackage( $formId, $kind ) {

		return new Package(
			$formId,
			$kind,
			new Properties(
				$this->translationPreferences->getByKey( 'settings' )
			),
			new Properties(
				$this->translationPreferences->getByKey( 'field' )
			)
		);
	}
}
