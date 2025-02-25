<?php

namespace WPML\Forms\Helpers\WpForms;

use WPML\FP\Obj;

class Field {

	/**
	 * @param array $field
	 *
	 * @return bool
	 */
	public static function hasChoices( array $field ) : bool {
		return in_array( $field['type'], [ 'radio', 'checkbox', 'select' ], true );
	}

	/**
	 * @param array $field
	 *
	 * @return bool
	 */
	public static function isDynamic( array $field ) : bool {
		return (bool) Obj::prop( 'dynamic', $field );
	}
}
