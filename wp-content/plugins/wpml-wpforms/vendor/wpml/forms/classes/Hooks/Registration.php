<?php

namespace WPML\Forms\Hooks;

abstract class Registration extends Base {

	/** Adds hooks. */
	public function addHooks() {
		add_filter( 'wpml_forms_bulk_registration_items', [ $this, 'bulkRegistrationItems' ] );
		add_action( "wpml_forms_bulk_registration_{$this->slug}", [ $this, 'bulkRegistration' ] );
	}

	/**
	 * Gets bulk registration item.
	 *
	 * @param int    $formId Form ID.
	 * @param string $formTitle Form title.
	 *
	 * @return array
	 */
	protected function getBulkRegistrationItem( $formId, $formTitle ) {
		return [
			'type'       => $this->slug,
			'id'         => $formId,
			'title'      => $formTitle,
			'registered' => $this->isRegistered( $formId ),
		];
	}

	/**
	 * Checks if form is registered for translation.
	 *
	 * @param int $formId Form ID.
	 *
	 * @return bool
	 */
	protected function isRegistered( $formId ) {

		$registered = false;
		$package    = $this->newPackage( $formId )->getPackage();
		$packages   = apply_filters( 'wpml_pt_all_packages', [] );

		foreach ( $packages as $existing_package ) {
			if ( $package['kind'] === $existing_package->kind && $package['name'] === $existing_package->name ) {
				$registered = true;
				break;
			}
		}

		return $registered;
	}

	/**
	 * Returns items for bulk registration.
	 *
	 * @param array $items Array of forms.
	 *
	 * @return mixed
	 */
	abstract public function bulkRegistrationItems( array $items );

	/**
	 * Does bulk registration.
	 *
	 * @param array $forms Array of form IDs.
	 *
	 * @return mixed
	 */
	abstract public function bulkRegistration( array $forms );

}
