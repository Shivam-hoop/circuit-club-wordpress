<?php

namespace WPML\Forms\Hooks\WpForms;

use WP_Post;
use WPML\API\Sanitize;
use WPML\LIB\WP\Hooks;
use function WPML\FP\spreadArgs;

class Import {

	const PAGE_SLUG = 'wpforms-tools';
	const VIEW_SLUG = 'import';

	public function addHooks() {
		if ( $this->isImportPage() ) {
			Hooks::onAction( 'save_post_wpforms', 10, 3 )
			     ->then( spreadArgs( [ $this, 'registerForm' ] ) );
		}
	}

	/**
	 * @param int     $formId
	 * @param WP_Post $form
	 * @param bool    $update
	 *
	 * @return void
	 */
	public function registerForm( $formId, $form, $update ) {
		if ( $update ) {
			do_action( 'wpforms_save_form', (int) $formId );
		}
	}

	private function isImportPage() : bool {
		global $pagenow;

		return 'admin.php' === $pagenow
			&& self::PAGE_SLUG === Sanitize::stringProp( 'page', $_GET ) // phpcs:ignore
			&& self::VIEW_SLUG === Sanitize::stringProp( 'view', $_GET ); // phpcs:ignore
	}
}
