<?php

namespace WPML\Forms\Loader;

use WPML\Forms\Translation\Preferences;

abstract class Base {

	/** @var array $config */
	protected $config;

	/** @var Preferences $preferences */
	protected $preferences;

	/**
	 * Base constructor.
	 *
	 * @param array $config Configuration data.
	 */
	public function __construct( array $config ) {
		$this->config = $config;
	}

	/** Loads Add-Ons.  */
	public function load() {
		$this->preferences = new Preferences( $this->getSlug(), $this->config );

		if ( $this->preferences->get() ) {
			$this->addHooks();
		}
	}

	/** Gets package slug. */
	abstract protected function getSlug();

	/** Gets package title.  */
	abstract protected function getTitle();

	/** Adds hooks. */
	abstract protected function addHooks();
}
