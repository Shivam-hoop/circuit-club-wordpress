<?php

namespace WPML\Forms\Helpers\WpForms;

use WPML\Convert\Ids;
use WPML\FP\Obj;

class DynamicChoices {

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	public static function convertRawValue( array $field ) : array {
		if (
			Field::hasChoices( $field )
			&& Field::isDynamic( $field )
		) {
			$elementType        = Obj::prop( 'dynamic_post_type', $field ) ?? Obj::prop( 'dynamic_taxonomy', $field );
			$field['value_raw'] = $elementType ? Ids::convert( $field['dynamic_items'], $elementType, true ) : $field['dynamic_items'];
		}

		return $field;
	}
}
