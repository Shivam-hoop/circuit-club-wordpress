<?php

namespace WPML\Forms\Addons\WpForms;

use WPML\Forms\Hooks\Base;

class SurveyAndPolls extends Base {

	/** Adds hooks. */
	public function addHooks() {
		
		add_filter( 'wpforms_surveys_reporting_fields_get_survey_field_data', [ $this, 'apply_form_result_translation' ], 10, 2 );
		add_filter( 'wpforms_surveys_polls_display_results_choices', [ $this, 'apply_form_result_choices_translation' ], 10, 3 );
	}

	/**
	 * Applies translations to form for displaying poll results.
	 * 
	 * @param array $data    Form data and settings.
	 * @param int   $form_id ID of the Form .
	 *
	 * @return array
	 */
	public function apply_form_result_translation( $data, $form_id ) {

		$package = $this->newPackage( $form_id );

		if ( $this->notEmpty( 'question', $data ) ) {
			
			$data['question'] = $package->translateString( $data['question'], strval( $this->getId( $data ) ), 'label' );
		}

		if ( $this->notEmpty( 'answers', $data ) ) {
			$i = 0;
			foreach ( $data['answers'] as &$answer ) {
				$string_name     = $this->getLabelOptionName( $this->getId( $data ) );
				$answer['value'] = $package->translateString( $answer['value'], strval( $answer['choice_id'] ), $string_name );

				if ( $this->notEmpty( 'chart', $data ) ) {
					
					$data['chart']['labels'][ $i ] = $answer['value'];
				}

				$i ++;
			}
		}

		return $data;
	}

	/**
	 * Applies translations to form for displaying poll results.
	 * 
	 * @param array $choices  The current field choices.
	 * @param int   $field_id ID of the current field.
	 * @param int   $form_id  ID of the Form.
	 *
	 * @return array
	 */
	public function apply_form_result_choices_translation( $choices, $field_id, $form_id ) {

		$package = $this->newPackage( $form_id );

		foreach ( $choices as $key => &$choice ) {

			$string_name     = $this->getLabelOptionName( $field_id );
			$choice['label'] = $package->translateString( $choice['label'], strval( $key ),  $string_name );
		}

		return $choices;
	}

	/**
	 * @param string|int $fieldId
	 *
	 * @return string
	 */
	private function getLabelOptionName( $fieldId ) {
		return 'label-' . $fieldId . '-option';
	}

}