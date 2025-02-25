<?php

namespace WPML\Forms\Hooks\WpForms;

use WPML\FP\Fns;
use WPML\FP\Lst;
use WPML\FP\Obj;
use WPML\FP\Str;
use function WPML\FP\pipe;

class AteFieldReorder {

	/**
	 * @var array
	 */
	private $fieldsOrder;

	/**
	 * @var AteFieldAdjuster
	 */
	private $adjuster;

	public function __construct( array $formData, AteFieldAdjuster $adjuster ) {
		$this->adjuster    = $adjuster;
		$this->fieldsOrder = array_flip( array_keys( $formData['fields'] ) );
	}

	public function handle( array $fields ) : array {
		$getFieldId = Obj::path( [ 'attributes', 'id' ] );

		$isSettingField   = pipe( $getFieldId, [ $this->adjuster, 'isSettingField' ] );
		$isGeneralSetting = pipe( $getFieldId, Str::startsWith( AteFieldAdjuster::NAME_PART_SETTING ) );

		list( $settingFields, $formFields ) = Lst::partition( $isSettingField, $fields );

		list( $generalSettings, $confirmationsAndNotifications ) = Lst::partition( $isGeneralSetting, $settingFields );

		$orderedFormFields = $this->orderFormFields( $formFields );
		return Lst::concat(
			$orderedFormFields,
			$generalSettings,
			$confirmationsAndNotifications
		);
	}

	private function orderFormFields( array $fields ) : array {
		$getStringName = Obj::path( [ 'attributes', 'id' ] );

		$removeSortHelper = Obj::without( '__sort_helper' );
		$addSortHelper    = function ( array $field ) use ( $getStringName ) {
			$item  = $getStringName( $field );
			$id    = (int) ( Str::match( '/(\d+)/', $item )[0] ?? 0 );
			$parts = explode( '-', $item );

			return Obj::assoc(
				'__sort_helper',
				[
					'id'           => $id,
					'parts_length' => count( $parts ),
				],
				$field
			);
		};

		$getFieldId     = Obj::path( [ '__sort_helper', 'id' ] );
		$getPartsLength = Obj::path( [ '__sort_helper', 'parts_length' ] );
		$sorter         = function ( $a, $b ) use ( $getStringName, $getFieldId, $getPartsLength ) {
			if ( $getFieldId( $a ) === $getFieldId( $b ) ) {
				$aPartsLength = $getPartsLength( $a );
				$bPartsLength = $getPartsLength( $b );

				if ( $aPartsLength === $bPartsLength ) {
					// Label should come first.
					if ( Str::startsWith( 'label', $getStringName( $a ) ) ) {
						return -1;
					} elseif ( Str::startsWith( 'label', $getStringName( $b ) ) ) {
						return +1; // description-6 & label-6.
					}

					return 0;
				}

				// Nested strings should come last.
				return $aPartsLength - $bPartsLength;
			}

			// Keep the form order for strings from different fields.
			return $this->fieldsOrder[ $getFieldId( $a ) ] - $this->fieldsOrder[ $getFieldId( $b ) ];
		};

		return wpml_collect( $fields )
			->map( $addSortHelper )
			->sort( $sorter )
			->map( $removeSortHelper )
			->all();
	}
}
