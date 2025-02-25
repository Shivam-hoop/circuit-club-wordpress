<?php

namespace WPML\Forms\Addons\WpForms;

use WPML\Forms\Hooks\Base;
use WPML\Forms\WpForms\SmartTag;

class SaveAndResume extends Base {

	public function addHooks() {
		add_filter( 'wpforms_process_smart_tags', [ $this, 'applyNotificationTranslations' ], 9, 2 );
	}

	/**
	 * @param string $message
	 * @param array  $formData Form data.
	 *
	 * @return string
	 */
	public function applyNotificationTranslations( $message, $formData ) {
		if ( strpos( $message, '{resume_link}' ) === false ) {
			return $message;
		}

		$package              = $this->newPackage( $formData['id'] );
		$formData['settings'] = $package->translateFormSettings( $formData['settings'] );

		$message = SmartTag::process( $formData['settings']['save_resume_email_notification_message'], $formData );

		return wpautop( $message );
	}
}
