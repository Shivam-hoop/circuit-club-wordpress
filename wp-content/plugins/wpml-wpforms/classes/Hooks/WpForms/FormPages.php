<?php

namespace WPML\Forms\Hooks\WpForms;

use WP;
use WPML\FP\Obj;
use WPML\Element\API\Languages;
use WPML\Settings\LanguageNegotiation;

class FormPages {

	const PRIORITY_BEFORE_FORM_PAGES = 9;

	/** @var Strings */
	private $strings;

	public function __construct( Strings $strings ) {
		$this->strings = $strings;
	}

	public function addHooks() {
		add_filter(
			'wpforms_form_pages_frontend_handle_request_form_data',
			[ $this->strings, 'applyTranslations' ]
		);

		if (
			LanguageNegotiation::isDir()
			&& $this->isSecondaryLanguage()
		) {
			add_action( 'parse_request', [ $this, 'parseRequest' ], self::PRIORITY_BEFORE_FORM_PAGES );
		}
	}

	/**
	 * @param WP $wp
	 *
	 * @return void
	 */
	public function parseRequest( WP $wp ) {
		if (
			Obj::path( [ 'query_vars', 'error' ], $wp )
			&& $this->isRequestPrefixedByLanguageCode( $wp->request )
		) {
			$wp->query_vars['name'] = $wp->request;
		}
	}

	/**
	 * @return bool
	 */
	private function isSecondaryLanguage() : bool {
		return Languages::getDefaultCode() !== Languages::getCurrentCode();
	}

	/**
	 * @param string $wpRequest
	 *
	 * @return bool
	 */
	private function isRequestPrefixedByLanguageCode( string $wpRequest ) : bool {
		$request = ! empty( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$escapedLanguageCode = preg_quote( Languages::getCurrentCode(), '/' );
		$pattern             = "/^\/$escapedLanguageCode\//";
		$request             = preg_replace( $pattern, '/', $request );

		$request = ! empty( $request ) ? sanitize_key( (string) wp_parse_url( $request, PHP_URL_PATH ) ) : '';

		return $request === $wpRequest;
	}
}
