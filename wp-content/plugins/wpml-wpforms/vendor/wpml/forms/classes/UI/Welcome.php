<?php

namespace WPML\Forms\UI;

class Welcome {

	const DISMISS_ACTION = 'wpml-forms-welcome-notice-dismiss';
	const ASSETS_HANDLE  = 'wpml-forms-welcome-notice';

	/** Adds hooks. */
	public function addHooks() {
		if ( \current_user_can( 'activate_plugins' )
			&& ! get_option( self::DISMISS_ACTION, false )
			&& ! \did_action( 'wpml_forms_welcome_notice_queued' )
		) {
			\add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
			\add_action( 'admin_notices', [ $this, 'renderNotice' ] );
			\add_action( 'wp_ajax_' . self::DISMISS_ACTION, [ $this, 'ajax' ] );
			\do_action( 'wpml_forms_welcome_notice_queued' );
		}
	}

	/** Enqueues scripts. */
	public function enqueueScripts() {

		$res_url = \untrailingslashit( \plugin_dir_url( wpml_forms_path() . '/loader.php' ) ) . '/res';

		\wp_enqueue_script(
			self::ASSETS_HANDLE,
			$res_url . '/js/welcome-notice.js',
			[ 'jquery' ],
			wpml_forms_latest()
		);

		\wp_enqueue_style(
			self::ASSETS_HANDLE,
			$res_url . '/css/welcome-notice.css',
			[],
			wpml_forms_latest()
		);

		$params = [
			'action'      => self::DISMISS_ACTION,
			'_ajax_nonce' => \wp_create_nonce( self::DISMISS_ACTION ),
		];

		\wp_localize_script( self::ASSETS_HANDLE, 'wpml_forms_welcome_notice', $params );
	}

	/**
	 * Renders notice.
	 *
	 * @codeCoverageIgnore
	 */
	public function renderNotice() {
		include wpml_forms_path() . '/templates/welcome.php';
	}

	/** AJAX callback to dismiss message. */
	public function ajax() {
		\check_ajax_referer( self::DISMISS_ACTION );
		\update_option( self::DISMISS_ACTION, true );
		\wp_send_json_success();
	}
}
