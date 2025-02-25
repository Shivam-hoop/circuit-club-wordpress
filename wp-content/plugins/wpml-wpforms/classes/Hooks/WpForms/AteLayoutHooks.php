<?php

namespace WPML\Forms\Hooks\WpForms;

use stdClass;
use WPML\FP\Obj;
use WPML\FP\Relation;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class AteLayoutHooks {

	/**
	 * @var Factory
	 */
	private $factory;

	/**
	 * @var array
	 */
	private $formsData;

	public function __construct( Factory $factory ) {
		$this->factory   = $factory;
		$this->formsData = [];
	}
	public function addHooks() {
		Hooks::onFilter( 'wpml_tm_adjust_translation_fields', 10, 2 )
			->then( spreadArgs( [ $this, 'adjustFieldsForAte' ] ) );
		Hooks::onFilter( 'wpml_tm_adjust_translation_job', 10, 2 )
			->then( spreadArgs( [ $this, 'reorderFieldsForAte' ] ) );
	}

	/**
	 * @param array    $fields
	 * @param stdClass $job
	 *
	 * @return array
	 */
	public function adjustFieldsForAte( array $fields, stdClass $job ) : array {
		if ( ! $this->isWpFormsPackage( $job ) ) {
			return $fields;
		}

		$formId   = $this->getFormId( $job );
		$formData = $this->getFormData( $formId );

		$adjustFieldForAte = function( $field ) use ( $formData ) {
			return $this->factory->getAteFieldAdjuster( $formData )->handle( $field );
		};

		return wpml_collect( $fields )
			->map( $adjustFieldForAte )
			->all();
	}

	public function reorderFieldsForAte( array $fields, \stdClass $job ) : array {
		if ( ! $this->isWpFormsPackage( $job ) ) {
			return $fields;
		}

		$formId   = $this->getFormId( $job );
		$formData = $this->getFormData( $formId );

		return $this->factory->getAteFieldReorder( $formData )->handle( $fields );
	}

	/**
	 * @param stdClass $job
	 *
	 * @return bool
	 */
	private function isWpFormsPackage( stdClass $job ) : bool {
		return Relation::propEq( 'element_type_prefix', 'package', $job )
			&& Relation::propEq( 'original_post_type', 'package_wpforms', $job );
	}

	/**
	 * @param stdClass $job
	 *
	 * @return int
	 */
	private function getFormId( stdClass $job ) : int {
		$packageId = (int) Obj::prop( 'original_doc_id', $job );

		return (int) Obj::prop( 'name', $this->factory->getWpmlPackage( $packageId ) );
	}

	/**
	 * @param int $formId
	 *
	 * @return array
	 */
	private function getFormData( int $formId ) : array {
		if ( ! Obj::prop( $formId, $this->formsData ) ) {
			$content = get_post_field( 'post_content', $formId, 'raw' );

			$this->formsData[ $formId ] = json_decode( $content, true );
		}

		return $this->formsData[ $formId ];
	}
}
