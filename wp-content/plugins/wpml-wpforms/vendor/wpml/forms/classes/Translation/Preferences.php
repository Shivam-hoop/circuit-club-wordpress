<?php

namespace WPML\Forms\Translation;

class Preferences {

	/**
	 * Translation preferences.
	 *
	 * @var array $preferences
	 */
	private $preferences;

	/**
	 * Preferences constructor.
	 *
	 * @param string $form_name Form type name.
	 * @param array  $xmlArray XML config file contents.
	 */
	public function __construct( $form_name, $xmlArray ) {
		$this->preferences = $this->parseXmlArray( $form_name, $xmlArray );
	}

	/**
	 * Gets translation preferences.
	 *
	 * @return array
	 */
	public function get() {
		return $this->preferences;
	}

	/**
	 * Gets translation preferences by key name.
	 *
	 * @param string $key Array element key name.
	 *
	 * @return mixed|null
	 */
	public function getByKey( $key ) {
		return array_key_exists( $key, $this->preferences ) ? $this->preferences[ $key ] : null;
	}

	/**
	 * Parses XML config file contents.
	 *
	 * @param string $form_name Form type name.
	 * @param array  $xmlArray XML config file contents.
	 *
	 * @return array
	 */
	private function parseXmlArray( $form_name, array $xmlArray ) {

		if ( ! array_key_exists( 'forms', $xmlArray )
			|| ! array_key_exists( 'form', $xmlArray['forms'] ) ) {
			return [];
		}

		$config = [];
		$forms  = array_key_exists( 0, $xmlArray['forms']['form'] ) ? $xmlArray['forms']['form'] : array( $xmlArray['forms']['form'] );

		foreach ( $forms as $form ) {

			if ( array_key_exists( 'attr', $form ) && array_key_exists( 'name', $form['attr'] )
			&& $form['attr']['name'] === $form_name ) {

				if ( array_key_exists( 'settings', $form ) && array_key_exists( 'property', $form['settings'] ) ) {

					$settings = array_key_exists( 0, $form['settings']['property'] ) ? $form['settings']['property'] : array( $form['settings']['property'] );

					foreach ( $settings as $property ) {

						if ( array_key_exists( 'value', $property ) ) {

							if ( ! array_key_exists( 'attr', $property ) ) {
								$config['settings'][ $property['value'] ] = [ 'type' => 'LINE' ];
							} else {
								$config['settings'][ $property['value'] ] = $property['attr'];
							}
						}
					}
				}

				if ( array_key_exists( 'field', $form ) && array_key_exists( 'property', $form['field'] ) ) {

					$properties = array_key_exists( 0, $form['field']['property'] ) ? $form['field']['property'] : array( $form['field']['property'] );

					foreach ( $properties as $property ) {

						if ( array_key_exists( 'value', $property ) ) {

							if ( ! array_key_exists( 'attr', $property ) ) {
								$config['field'][ $property['value'] ] = [ 'type' => 'LINE' ];
							} else {
								$config['field'][ $property['value'] ] = $property['attr'];
							}
						}
					}
				}
			}
		}

		return $config;
	}
}
