<?php

namespace WPML\Forms\Hooks\WpForms;

use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;
use WPML\Element\API\Languages;
use WPML\Forms\Helpers\WpForms\Entry;
use WPML\Forms\Helpers\WpForms\DynamicChoices;

class EntryEdit {

	/** @var Strings */
	private $strings;

	public function __construct( Strings $strings ) {
		$this->strings = $strings;
	}

	/**
	 * @return void
	 */
	public function addHooks() {
		$maybeAddHooks = function( $mode ) {
			if ( 'edit' === $mode ) {
				// phpcs:ignore WordPress.Security.NonceVerification
				$entryEdit = (int) $_GET['entry_id'];

				Hooks::onFilter( 'wpforms_pro_admin_entries_edit_form_data' )
					->then( spreadArgs( $this->getMaybeTranslateFormData( $entryEdit ) ) );
				Hooks::onFilter( 'wpforms_entry_single_data' )
					->then( spreadArgs( [ $this, 'maybeConvertDynamicChoices' ] ) );
			}
		};

		Hooks::onAction( 'wpforms_entries_init' )
			->then( spreadArgs( $maybeAddHooks ) );
	}

	/**
	 * @param int $entryEdit
	 *
	 * @return callable(array):array
	 */
	public function getMaybeTranslateFormData( int $entryEdit ) : callable {
		return function( array $formData ) use ( $entryEdit ) {
			$language = Entry::getLanguageById( $entryEdit );

			if ( $language && Languages::getCurrentCode() !== $language ) {
				return Languages::whileInLanguage( $language )
					->invoke( [ $this->strings, 'applyTranslations' ] )
					->runWith( $formData );
			}

			return $formData;
		};
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	public function maybeConvertDynamicChoices( array $fields ) : array {
		return wpml_collect( $fields )
			->map( [ DynamicChoices::class, 'convertRawValue' ] )
			->all();
	}
}
