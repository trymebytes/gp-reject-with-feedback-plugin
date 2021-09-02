<?php
/**
 *Handles bbPress forum related actions for the rejection action
 */
class GP_Reject_With_Feedback_In_Forum_Topic {

	const OPTION_NAME = 'custom_gp_rejection_forum_data';

	private $translation_id;

	private $locale;

	private $rejection_feedback;

	private $original_id;

	public function __construct( $data ) {
		$this->locale             = $data['locale'];
		$this->translation_id     = $data['translation_id'];
		$this->rejection_feedback = $data['rejection_feedback'];
		$this->original_id        = $data['original_id'];
	}

	/**
	 * Create a bbPress forum for a locale. A locale should have only one forum
	 */
	public function create_forum() {
		$forum_data = [
			'post_parent'    => 0, // forum ID
			'post_status'    => bbp_get_public_status_id(),
			'post_type'      => bbp_get_forum_post_type(),
			'post_author'    => bbp_get_current_user_id(),
			'post_password'  => '',
			'post_content'   => '',
			'post_title'     => $this->locale . ' - translations',
			'menu_order'     => 0,
			'comment_status' => 'closed',
		];

		// Insert forum
		$forum_id = bbp_insert_forum( $forum_data );

		$locale_reject_forum_data = [
			'forum_id' => $forum_id,
		];

		$reject_data                  = [];
		$reject_data[ $this->locale ] = $locale_reject_forum_data;

		//store the forum_id for this locale WordPress option
		update_option( self::OPTION_NAME, $reject_data );

		return $forum_id;
	}

	/**
	 * Return forum ID for locale, return false if locale
	 * @return int|false - forum_id or false
	 */
	public function getForumIdForLocale() {
		$all_locale_forums = get_option( self::OPTION_NAME );

		if ( array_key_exists( $this->locale, $all_locale_forums ) ) {
			return $all_locale_forums[ $this->locale ]['forum_id'];
		}
		return false;
	}

	/**
	 * Create a topic bbPress topic the first time a translation is rejected
	 * @return int $topic_id id of the created topic
	 */
	public function create_topic() {
		$forum_id = $this->getForumIdForLocale();

		if ( ! $this->getForumIdForLocale() ) {
			$forum_id = $this->create_forum();
		}

		//Get the original string that needs translation
		$original            = GP::$original->get( $this->original_id );
		$string_to_translate = $original->singular;

		$topic_data = [
			'post_author'  => bbp_get_current_user_id(),
			'post_parent'  => $forum_id,
			'post_title'   => 'Translate ' . $string_to_translate,
			'post_content' => $this->rejection_feedback,
		];

		$topic_meta = [
			'forum_id' => $forum_id,
		];

		// Insert topic
		$topic_id = bbp_insert_topic( $topic_data, $topic_meta );

		//store the topic id for this translation in Glotpress meta
		gp_update_meta( $this->original_id, 'rejection_topic_id', $topic_id, 'reject-translation' );
		return $topic_id;

	}

	/**
	 * Add a reply to an existing bbPress topic for a translation
	 * When a translation has once been rejected, the next rejection adds a reply to the exiting topic and does not create another topic
	 * @param int $topic_id ID of existing bbPress topic for this translation
	 * @return int $reply_id ID of the reply to the topic
	 */
	public function add_reply( $topic_id ) {
		$reply_data = [
			'post_author'  => bbp_get_current_user_id(),
			'post_parent'  => $topic_id,
			'post_title'   => '[REJECTION REPLY]',
			'post_content' => $this->rejection_feedback,
		];

		$reply_meta = [
			'forum_id' => $forum_id,
			'topic_id' => $topic_id,
		];
		// Insert topic
		$reply_id = bbp_insert_reply( $reply_data, $reply_meta );
		return $reply_id;
	}
}
