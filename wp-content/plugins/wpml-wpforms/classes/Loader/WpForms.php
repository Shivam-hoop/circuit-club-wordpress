<?php

namespace WPML\Forms\Loader;

use SitePress;
use WPML\Forms\Hooks\WpForms\AteLayoutHooks;
use WPML\Forms\Hooks\WpForms\ConversationalForms;
use WPML\Forms\Hooks\WpForms\DynamicChoices;
use WPML\Forms\Hooks\WpForms\EntryEdit;
use WPML\Forms\Hooks\WpForms\EntryPreviewField;
use WPML\Forms\Hooks\WpForms\FormPages;
use WPML\Forms\Hooks\WpForms\Import;
use WPML\Forms\Hooks\WpForms\Notifications;
use WPML\Forms\Hooks\WpForms\Package;
use WPML\Forms\Hooks\WpForms\Strings;
use WPML\Forms\Addons\WpForms\SaveAndResume;
use WPML\Forms\Hooks\WpForms\SubLabels;
use WPML\Forms\Translation\Factory as FormsFactory;
use WPML\Forms\Addons\WpForms\SurveyAndPolls;
use WPML\Forms\Hooks\WpForms\Factory;


class WpForms extends Base {

	/** Gets package slug. */
	protected function getSlug() {
		return 'wpforms';
	}

	/** Gets package title. */
	protected function getTitle() {
		return 'WPForms';
	}

	/** Adds hooks. */
	protected function addHooks() {
		/** @var SitePress $sitepress */
		global $sitepress;

		$formsFactory = new FormsFactory( $this->preferences );

		$wpforms = new Strings(
			$this->getSlug(),
			$this->getTitle(),
			$formsFactory,
			$sitepress
		);
		$wpforms->addHooks();

		$notifications = new Notifications(
			$this->getSlug(),
			$this->getTitle(),
			$formsFactory
		);
		$notifications->addHooks();

		$package_filter = new Package( $this->getSlug() );
		$package_filter->addHooks();

		$conversational_forms = new ConversationalForms( $wpforms );
		$conversational_forms->addHooks();

		$dynamic_choices = new DynamicChoices();
		$dynamic_choices->addHooks();

		if ( defined( 'WPFORMS_FORM_PAGES_VERSION' ) ) {
			$form_pages = new FormPages( $wpforms );
			$form_pages->addHooks();
		}

		if ( is_admin() ) {
			$entryEdit = new EntryEdit( $wpforms );
			$entryEdit->addHooks();
		}

		$entryPreviewField = new EntryPreviewField(
			$this->getSlug(),
			$this->getTitle(),
			$formsFactory,
			$sitepress
		);
		$entryPreviewField->addHooks();

		$factory   = new Factory();
		$ateLayout = new AteLayoutHooks( $factory );
		$ateLayout->addHooks();

		$import = new Import();
		$import->addHooks();

		$subLabels = new SubLabels();
		$subLabels->addHooks();

		if ( defined( 'WPFORMS_SURVEYS_POLLS_VERSION' ) ) {
			$surveyAndPolls = new SurveyAndPolls(
				$this->getSlug(),
				$this->getTitle(),
				$formsFactory
			);
			$surveyAndPolls->addHooks();
		}

		if ( defined( 'WPFORMS_SAVE_RESUME_VERSION' ) ) {
			$saveAndResume = new SaveAndResume(
				$this->getSlug(),
				$this->getTitle(),
				$formsFactory
			);
			$saveAndResume->addHooks();
		}
	}
}
