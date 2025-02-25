<?php

namespace WPML\Forms\UI;

class Notices {

	/** Adds missing dependencies notice. */
	public function addHooksForMissingDependencies() {
		add_action( 'admin_notices', [ $this, 'renderMissingDependenciesNotice' ] );
	}

	/**
	 * Renders admin notice for missing dependencies.
	 *
	 * @codeCoverageIgnore
	 */
	public function renderMissingDependenciesNotice() {
		$linkUrl = 'https://wpml.org/faq/how-to-add-string-translation-to-your-site/?utm_source=plugin&utm_medium=gui&utm_campaign=wpml-forms';
		$message = sprintf(
			/* translators: The placeholders are replaced by an HTML link pointing to the String translation add-on FAQ article. */
			esc_html__( 'WPML Forms: Please activate the WPML CMS Plugin and the %1$sString Translation add-on%2$s', 'wpml-forms' ),
			'<a href="' . esc_url( $linkUrl ) . '" class="wpml-external-link" target="_blank">',
			'</a>'
		);
		echo '<div class="notice notice-error"><p>' . wp_kses_post( $message ) . '</p></div>';
	}
}
