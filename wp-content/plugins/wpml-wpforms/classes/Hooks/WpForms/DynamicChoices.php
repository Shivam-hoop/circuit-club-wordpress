<?php

namespace WPML\Forms\Hooks\WpForms;

class DynamicChoices {

	/** Adds hooks. */
	public function addHooks() {
		add_filter( 'wpforms_dynamic_choice_post_type_args', [ $this, 'allowFilters' ] );
	}

	/**
	 * Unsets suppress_filters for languages to be selected.
	 *
	 * @param array $args Filter arguments.
	 * @return array
	 */
	public function allowFilters( $args ) {
		$args['suppress_filters'] = false;
		return $args;
	}
}
