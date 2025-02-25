<?php



namespace WPML\Forms\Hooks\WpForms;

use SitePress;
use WPML\Forms\Hooks\Base;
use WPML\Forms\Translation\Factory;
use WPML\FP\Obj;

class EntryPreviewField  extends Base {

	const BEFORE_WPFORMS_PRO = 9;

	/** @var SitePress */
	private $sitepress;

	/**
	 * WPML\Forms\Hooks\WpForms\EntryPreviewField constructor.
	 *
	 * @param string    $slug Form type slug.
	 * @param string    $kind Translation package kind.
	 * @param Factory   $factory Translation package factory.
	 * @param SitePress $sitepress
	 */
	public function __construct( $slug, $kind, Factory $factory, SitePress $sitepress ) {
		$this->sitepress = $sitepress;
		parent::__construct( $slug, $kind, $factory );
	}

	public function addHooks() {
		add_filter( 'wpforms_frontend_form_data', [ $this, 'mayBeAddLanguageField' ] );
		add_action( 'wp_ajax_wpforms_get_entry_preview', [ $this, 'markAsNeedsTranslations' ], self::BEFORE_WPFORMS_PRO );
		add_action( 'wp_ajax_nopriv_wpforms_get_entry_preview', [ $this, 'markAsNeedsTranslations' ], self::BEFORE_WPFORMS_PRO );
	}

	/**
	 * Add a temporary language hidden field to be displayed on page.
	 * This makes the language code be submitted with the form.
	 *
	 * @param array $formData
	 * @return array $formData
	 */
	public function mayBeAddLanguageField( array $formData ) {
		if ( $this->hasPreviewEntryField( $formData ) ) {
			$formData['fields'][0] = [
				'id'            => 0,
				'type'          => 'hidden',
				'default_value' => $this->sitepress->get_current_language(),
			];
		}
		return $formData;
	}

	/**
	 * Switch to the correct language code used on original request.
	 */
	public function markAsNeedsTranslations() {
		$submittedData = filter_input( INPUT_POST, 'wpforms', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$languageCode  = Obj::pathOr( '', [ 'fields', 0 ], $submittedData );
		$this->sitepress->switch_lang( $languageCode, true );
		add_filter( 'wpforms_pro_fields_entry_preview_get_field_label', [ $this, 'mayBeApplyPreviewLabelTranslation' ], 10, 3 );
	}

	/**
	 * Translate the field's label if it's a preview request
	 *
	 * @param string $label
	 * @param array  $rawField
	 * @param array  $formData
	 * @return string
	 */
	public function mayBeApplyPreviewLabelTranslation( $label, $rawField, $formData ) {
		$package         = $this->newPackage( $this->getId( $formData ) );
		$fieldId         = $this->getId( $rawField );
		$field           = (array) Obj::path( [ 'fields', $fieldId ], $formData );
		$fieldId         = (string) $this->getId( $field );
		$translatedField = $package->translateField( $field, $fieldId );
		return Obj::propOr( $label, 'label', $translatedField );
	}

	/**
	 * Whether the form contains an entry preview field.
	 *
	 * @return bool
	 */
	private function hasPreviewEntryField( array $formData ) {
		foreach ( $formData['fields'] as $field ) {
			if ( $field['type'] === 'entry-preview' ) {
				return true;
			}
		}
		return false;
	}
}
