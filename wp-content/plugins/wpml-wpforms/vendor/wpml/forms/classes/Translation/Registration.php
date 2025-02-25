<?php

namespace WPML\Forms\Translation;

class Registration {

	/**
	 * Holds existing forms info.
	 *
	 * @var array $items
	 */
	private $items;

	/**
	 * Checks if there are existing forms.
	 *
	 * @return bool
	 */
	public function hasItems() {
		return (bool) $this->get();
	}

	/**
	 * Checks if there are existing unregistered forms.
	 *
	 * @return bool
	 */
	public function hasUnregisteredItems() {
		return array_search( false, array_column( $this->get(), 'registered' ), true ) !== false;
	}

	/**
	 * Gets existing forms info.
	 *
	 * @return array
	 */
	public function get() {

		if ( null === $this->items ) {
			$this->items = apply_filters( 'wpml_forms_bulk_registration_items', [] );
			array_multisort( array_column( $this->items, 'title' ), SORT_STRING, $this->items );
		}

		return $this->items;
	}

	/** Triggers bulk registration. */
	public function register() {
		$items = $this->get();
		$forms = [];
		foreach ( $items as $form ) {
			$forms[ $form['type'] ][] = $form['id'];
		}
		foreach ( $forms as $formType => $formIds ) {
			do_action( "wpml_forms_bulk_registration_{$formType}", $formIds );
		}
	}
}
