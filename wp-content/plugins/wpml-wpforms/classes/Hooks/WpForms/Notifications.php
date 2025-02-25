<?php

namespace WPML\Forms\Hooks\WpForms;

use WPForms_Conditional_Logic_Fields;
use WPML\Forms\Helpers\WpForms\Field;
use WPML\Forms\Hooks\Base;
use WPML\Forms\WpForms\SmartTag;
use WPML\FP\Fns;
use WPML\FP\Obj;
use WPML\FP\Lst;
use function WPML\FP\pipe;

class Notifications extends Base {

	const EMAIL_HTML_CONTEXT = 'email-html';

	/** Adds hooks. */
	public function addHooks() {
		add_filter( 'wpforms_process_before_form_data', [ $this, 'applyConfirmationTranslations' ] );
		add_filter( 'wpforms_emails_send_email_data', [ $this, 'applyEmailTranslations' ], 10, 2 );
		add_action( 'wpforms_email_send_after', [ $this, 'restoreLanguage' ] );
		add_filter( 'wpml_user_language', [ $this, 'getLanguageForEmail' ], 10, 2 );
		add_filter( 'wpforms_entry_email_data', [ $this, 'restoreFieldLabelsToDefaultLanguage' ], 10, 3 );

		add_filter( 'wpforms_html_field_value', [ $this, 'restoreRawValuesForHtmlEmail' ], 10, 4 );
		add_filter( 'wpforms_plaintext_field_value', [ $this, 'restoreRawValuesForNotifications' ], 10, 2 );

		// These are only required in the 'Pro' version.
		if (
			class_exists( WPForms_Conditional_Logic_Fields::class )
			&& has_filter( 'wpforms_entry_email_process', [ WPForms_Conditional_Logic_Fields::instance(), 'process_notification_conditionals' ] )
		) {
			remove_filter( 'wpforms_entry_email_process', [ WPForms_Conditional_Logic_Fields::instance(), 'process_notification_conditionals' ], 10 );
			add_filter( 'wpforms_entry_email_process', [ $this, 'processNotificationConditionals' ], 10, 4 );
		}
	}

	/**
	 * @param string $value
	 * @param array  $field
	 * @param array  $formData
	 * @param string $context
	 *
	 * @return string
	 */
	public function restoreRawValuesForHtmlEmail( string $value, array $field, array $formData, string $context ) : string {
		if ( self::EMAIL_HTML_CONTEXT === $context ) {
			return $this->restoreRawValuesForNotifications( $value, $field );
		}

		return $value;
	}

	/**
	 * @param string $value
	 * @param array  $field
	 *
	 * @return string
	 */
	public function restoreRawValuesForNotifications( string $value, array $field ) : string {
		if (
			in_array( $field['type'], [ 'radio', 'select', 'checkbox' ], true )
			&& ! Field::isDynamic( $field )
		) {
			return Obj::propOr( $value, 'value_raw', $field );
		}

		return $value;
	}

	/**
	 * Restores field labels to default language.
	 *
	 * @param array $fields   The form fields.
	 * @param array $entry    The form entry.
	 * @param array $formData The form data.
	 *
	 * @return array
	 */
	public function restoreFieldLabelsToDefaultLanguage( $fields, $entry, $formData ) {

		$formPost = wpforms()->get( 'form' )->get(
			$formData['id'],
			[ 'content_only' => true ]
		);

		$formPostFields   = $formPost['fields'];
		$translatedFields = $formData['fields'];

		foreach ( $fields as $key => &$field ) {
			$field['name'] = $formPostFields[ $key ]['label'];
			$entryFields   = Obj::propOr( [], 'fields', $entry );
			if ( array_key_exists( $key, $entryFields ) ) {
				$field['value'] = $this->getFieldValue( $field, $entry['fields'][ $key ], $formPostFields[ $key ], $translatedFields[ $key ] );
			}
		}

		return $fields;
	}

	/**
	 * Applies translations to email data.
	 *
	 * @param array              $data Email data.
	 * @param \WPForms_WP_Emails $emails WPForms email object.
	 *
	 * @return mixed
	 */
	public function applyEmailTranslations( $data, $emails ) {
		$package = $this->newPackage( $this->getId( $emails->form_data ) );

		$email = is_array( $data['to'] ) ? reset( $data['to'] ) : $data['to'];
		do_action( 'wpml_switch_language_for_email', $email );

		$dataKeys = [ 'subject', 'message' ];
		$formPost = wpforms()->get( 'form' )->get(
			$emails->form_data['id'],
			[ 'content_only' => true ]
		);

		if ( $this->notEmpty( 'settings', $formPost ) ) {

			$formPost['settings'] = $package->translateFormSettings( $formPost['settings'] );
		}

		$current_notification = $formPost['settings']['notifications'][ $emails->notification_id ];

		$setData = function( $data, $key ) use ( $current_notification ) {
			if ( ! empty( $current_notification[ $key ] ) ) {
				$data[ $key ] = $current_notification[ $key ];
			}

			return $data;
		};

		foreach ( $dataKeys as $key ) {
			$data = $setData( $data, $key );
		}

		$data = SmartTag::process( $data, $formPost, $dataKeys );

		$translated = [ 'notifications' => [ $emails->notification_id => $data ] ];
		foreach ( $emails->fields as &$field ) {
			$field['name'] = $package->translateString( $field['name'], strval( $this->getId( $field ) ), 'label' );
		}

		return $translated['notifications'][ $emails->notification_id ];
	}

	/**
	 * Restores current language.
	 *
	 * @codeCoverageIgnore
	 */
	public function restoreLanguage() {
		do_action( 'wpml_restore_language_from_email' );
	}

	/**
	 * Applies form confirmations translations.
	 *
	 * @param array $formData Form data.
	 *
	 * @return array
	 */
	public function applyConfirmationTranslations( $formData ) {

		$package = $this->newPackage( $this->getId( $formData ) );

		if (
			$this->notEmpty( 'settings', $formData )
			&& $this->notEmpty( 'confirmations', $formData['settings'] )
		) {
			$formData['settings'] = $package->translateFormSettings( $formData['settings'] );

			foreach ( $formData['settings']['confirmations'] as &$confirmation ) {

				$confirmation = SmartTag::process( $confirmation, $formData );
			}
		}

		return $formData;
	}

	/**
	 * Returns language to use for translation based on email.
	 * Will use the language of the post if non site user.
	 *
	 * @param string $language Language detected.
	 * @param string $email The user email.
	 *
	 * @return string
	 */
	public function getLanguageForEmail( $language, $email ) {
		$user = get_user_by( 'email', $email );
		if ( isset( $user->ID ) ) {
			return $language;
		}
		return $this->getCurrentFormLanguage();
	}

	/**
	 * @return string
	 */
	private function getCurrentFormLanguage() {
		return apply_filters( 'wpml_current_language', '' );
	}

	/**
	 * @param array        $field Field in default language.
	 * @param array|string $entryField
	 * @param array        $originalField
	 * @param array        $translatedField
	 * @return string
	 */
	private function getFieldValue( $field, $entryField, $originalField, $translatedField ) {
		$getField         = Obj::path( Fns::__, $field );
		$getOriginalField = Obj::path( Fns::__, $originalField );

		switch ( $field['type'] ) {
			case 'select':
			case 'radio':
			case 'checkbox':
				$choicesMap = $this->getChoiceMap( $originalField, $translatedField );

				if ( is_array( $entryField ) && $choicesMap ) {
					$value = '';
					foreach ( $entryField as $key => $val ) {
						$value .= PHP_EOL . Obj::propOr( $entryField[ $key ], $val, $choicesMap );
					}
					return $value;
				}
				return Obj::propOr( $field['value'], $field['value'], $choicesMap );

			case 'likert_scale':
				$value = '';
				foreach ( $field['value_raw'] as $key => $val ) {
					$value .= PHP_EOL . $getOriginalField( [ 'rows', $key ] ) . ':' . PHP_EOL
						. $getOriginalField( [ 'columns', $val ] );
				}
				return $value;

			case 'payment-multiple':
			case 'payment-select':
				return $getOriginalField( [ 'choices', $field['value_raw'], 'label' ] )
						. ' - ' . $getField( [ 'currency' ] )
						. ' ' . $getField( [ 'amount' ] );

			case 'payment-checkbox':
				$value     = '';
				$choiceIds = explode( ',', $field['value_raw'] );
				foreach ( $choiceIds as $key ) {
					$value .= PHP_EOL . $getOriginalField( [ 'choices', $key, 'label' ] )
						. ' - ' . $getField( [ 'currency' ] )
						. ' ' . $getOriginalField( [ 'choices', $key, 'value' ] );
				}
				return $value;

			default:
				return $field['value'];
		}
	}

	/**
	 * @param array $originalField
	 * @param array $translatedField
	 *
	 * @return array
	 */
	private function getChoiceMap( $originalField, $translatedField ) {
		if ( Obj::prop( 'dynamic_choices', $originalField ) ) {
			return [];
		}

		$getChoices = pipe( Obj::path( [ 'choices' ] ), Lst::pluck( 'label' ) );

		$originalChoices   = $getChoices( $originalField );
		$translatedChoices = $getChoices( $translatedField );

		$choicesMap = [];
		foreach ( $translatedChoices as $key => $choice ) {
			$choicesMap[ $choice ] = $originalChoices[ $key ];
		}

		return $choicesMap;
	}

	/**
	 * Restore labels in form data before processing conditional logic for form entry notifications.
	 *
	 * @param bool  $process   Whether to process the logic or not.
	 * @param array $fields    List of submitted fields.
	 * @param array $form_data Form data and settings.
	 * @param int   $id        Notification ID.
	 *
	 * @return bool
	 */
	public function processNotificationConditionals( $process, $fields, $form_data, $id ) {
		$conditionalLogicFields = WPForms_Conditional_Logic_Fields::instance();

		$form_data = $this->restoreConditionalLabels( $form_data, $fields );

		return $conditionalLogicFields->process_notification_conditionals( $process, $fields, $form_data, $id );
	}

	/**
	 * @param array $form_data Form data and settings.
	 * @param array $fields    List of submitted fields.
	 *
	 * @return array
	 */
	private function restoreConditionalLabels( $form_data, $fields ) {
		foreach ( $form_data['fields'] as $key => &$field ) {
			if ( isset( $field['choices'] ) ) {
				foreach ( $field['choices'] as &$choice ) {
					if ( $choice['label'] === $fields[ $key ]['value_raw'] ) {
						$choice['label'] = $fields[ $key ]['value'];
					}
				}
			}
		}

		return $form_data;
	}

}
