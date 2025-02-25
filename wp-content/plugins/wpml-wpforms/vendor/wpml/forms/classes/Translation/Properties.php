<?php

namespace WPML\Forms\Translation;

class Properties {

	/**
	 * Holds translation properties.
	 *
	 * @var array $properties
	 */
	private $properties = [];

	/**
	 * Properties constructor.
	 *
	 * @param array $translationPreferences Translation preferences.
	 */
	public function __construct( array $translationPreferences ) {
		$this->add( $translationPreferences );
	}

	/**
	 * Gets translatable properties.
	 *
	 * @return array
	 */
	public function get() {
		return $this->properties;
	}

	/**
	 * Adds translatable properties to configuration.
	 *
	 * @param array $properties Translatable properties.
	 */
	public function add( array $properties ) {
		$to_add = array_diff_key( $properties, $this->properties );
		if ( $to_add ) {
			$this->properties = array_merge( $this->properties, $to_add );
			ksort( $this->properties );
		}
	}

	/**
	 * Checks if there are properties for translation.
	 *
	 * @param array $properties Form properties to check against.
	 *
	 * @return array
	 */
	public function forTranslation( array $properties ) {
		return array_intersect_key( $this->properties, $properties );
	}

	/**
	 * Gets property value from form data.
	 *
	 * @param mixed  $data Form data.
	 * @param string $propertyName Property name.
	 *
	 * @return mixed|null
	 */
	public function getValue( $data, $propertyName ) {

		return is_array( $data )
				&& array_key_exists( $propertyName, $data )
				&& ( is_string( $data[ $propertyName ] ) || is_array( $data[ $propertyName ] ) )
				&& ! empty( $data[ $propertyName ] ) ? $data[ $propertyName ] : null;
	}
}
