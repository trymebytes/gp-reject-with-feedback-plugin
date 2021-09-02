<?php

require_once __DIR__ . '/class-gp-reject-with-feedback-in-forum-topic.php';

class GP_Reject_With_Feedback {

	/**
	 * Initialize plugin here
	 */
	public function init() {

		add_action( 'gp_pre_tmpl_load', array( $this, 'register_custom_js_on_page' ), 10, 2 );
		$this->add_reject_action();

	}

	/**
	 * Register custom javascript file and variables and this is fired only for the translations page template
	 *
	 * @param string $template page template
	 * @return void
	 */
	public function register_custom_js_on_page( $template ) {

		if ( 'translations' !== $template ) {
			return;
		}

		wp_register_script( 'gp-reject-feedback-editor', plugins_url( 'assets/js/editor.js', __FILE__ ), array( 'gp-editor' ), '2018-05-19' );
		wp_add_inline_script(
			'gp-reject-feedback-editor',
			'const gp_reject_with_feedback_js = ' . json_encode(
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'gp_reject_with_feedback_js_nonce' ),
				)
			),
			'before'
		);
		gp_enqueue_script( 'gp-reject-feedback-editor' );

	}

	/**
	 * Callback function for add_reject_action hook
	 */
	public function reject_with_feedback() {
		if ( ! check_ajax_referer( 'gp_reject_with_feedback_js_nonce', 'nonce' ) ) {
			return;
		}
		$data                       = [];
		$data['locale']             = $_POST['data']['locale'];
		$data['translation_id']     = $_POST['data']['translation_id'];
		$data['original_id']        = $_POST['data']['original_id'];
		$data['rejection_feedback'] = sanitize_text_field( $_POST['data']['rejection_feedback'] );

		if ( ! empty( $data['rejection_feedback'] ) ) {
			//reject translation with feedback
			$this->process_reject_with_feedback( $data );
		}

		$translation_status_update_result = $this->update_translation_status( $data, 'rejected' );
		echo $translation_status_update_result;
		die();
	}

	/**
	 * Hook reject_with_feedback() function to 'wp_ajax_reject_with_feedback' action
	 */
	public function add_reject_action() {
		add_action( 'wp_ajax_reject_with_feedback', array( $this, 'reject_with_feedback' ) );
	}

	/**
	 * Check if translation alreadhy has a topic in the forum
	 * @param int $translation_id
	 * @return bool
	 */
	private function get_translation_meta_data( $original_id ) {
		$object_type = 'reject-translation';
		$object_id   = $original_id;
		$meta_key    = 'rejection_topic_id';

		$translation_meta_data = gp_get_meta( $object_type, $object_id, $meta_key );

		return $translation_meta_data;
	}

	/**
	 * Create a forum topic if a topic for this translation does not exist otherwise,
	 * reply an existing topic for the translation in the forum
	 * @param array $data translation data
	 * @return int id of the topic or id of the reply
	 */
	private function process_reject_with_feedback( $data ) {
		$gp_reject_instance = new GP_Reject_With_Feedback_In_Forum_Topic( $data );

		$original_id = $data['original_id'];

		$translation_meta_data = $this->get_translation_meta_data( $original_id );

		if ( ! empty( $translation_meta_data ) ) {
			//add rejection feedback as reply
			$topic_id = intval( $translation_meta_data );

			//adds reply and returns the reply_id
			return $gp_reject_instance->add_reply( $topic_id );
		}
		//create new topic for this rejection and return topic_id
		return $gp_reject_instance->create_topic();

	}

	/**
	 * Update translation status
	 * @param array $data translation data
	 * @param string $status  status to set for the translation
	 */
	private function update_translation_status( $data, $status ) {
		$translation = GP::$translation->get( $data['translation_id'] );
		$result      = $translation->set_status( $status );
		if ( $result ) {
			$gp_route = new GP_Route();
			return $gp_route->tmpl( 'translation-row', get_defined_vars() );
		} else {
			return $this->die_with_error( 'Error in retrieving translation!' );
		}
	}
}
