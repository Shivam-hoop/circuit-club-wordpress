<?php
namespace WPML\Forms\WpForms;

class SmartTag {
	const SMART_TAG = '{form_name}';
	/**
	 * @param array|string $data Notification/Confirmation data.
	 * @param array        $formData Form data.
	 * @param array        $properties properties in data to replace smart tag in. Default [ 'message' ].
	 *
	 * @return array|string
	 */
	public static function process( $data, $formData, $properties = [ 'message' ] ) {
		$formName = wpforms_process_smart_tags(
			self::SMART_TAG, $formData
		);

		if ( is_array( $data ) ) {

			foreach ( $properties as $prop ) {
				if ( isset( $data[ $prop ] ) ) {

					$data[ $prop ] = self::replace( $formName, $data[ $prop ] );
				}
			}
		} else {

			$data = self::replace( $formName, $data );
		}

		return $data;
	}

	/**
	 * @param string $replace string to put in place.
	 * @param string $text text to replace in.
	 *
	 * @return string
	 */
	private static function replace( $replace, $text ) {
		return str_replace( self::SMART_TAG, $replace, $text );
	}
}
