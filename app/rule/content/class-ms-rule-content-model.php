<?php
/**
 * @copyright Incsub (http://incsub.com/)
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU General Public License, version 2 (GPL-2.0)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston,
 * MA 02110-1301 USA
 *
*/

/**
 * Membership Comment Rule class.
 *
 * Persisted by Membership class.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage Model
 */
class MS_Rule_Content_Model extends MS_Model_Rule {

	/**
	 * Rule type.
	 *
	 * @since 1.0.0
	 *
	 * @var string $rule_type
	 */
	protected $rule_type = self::RULE_TYPE_CONTENT;

	/**
	 * Available special pages
	 *
	 * @since 1.1.0
	 *
	 * @var array
	 */
	protected $_content = null;

	/**
	 * Comment content ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string $content_id
	 */
	const CONTENT_ID = 'content';

	/**
	 * Rule value constants.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	const COMMENT_NO_ACCESS = 'cmt_none';
	const COMMENT_READ = 'cmt_read';
	const COMMENT_WRITE = 'cmt_full';
	const MORE_LIMIT = 'no_more';

	/**
	 * Flag of the final comment access level.
	 * When an user is member of multiple memberships with different
	 * comment-access-restrictions then the MOST GENEROUS access will be granted.
	 *
	 * @since  1.0.0
	 *
	 * @var int
	 */
	protected static $comment_access = self::COMMENT_NO_ACCESS;

	/**
	 * Set-up the Rule
	 *
	 * @since  1.1.0
	 */
	static public function prepare_class() {
		// Register the tab-output handler for the admin side
		MS_Factory::load( 'MS_Rule_Content_View' )->register();
	}

	/**
	 * Verify access to the current content.
	 *
	 * This rule will return NULL (not relevant), because the comments are
	 * protected via WordPress hooks instead of protecting the whole page.
	 *
	 * @since 1.0.0
	 *
	 * @param string $id The content id to verify access.
	 * @return bool|null True if has access, false otherwise.
	 *     Null means: Rule not relevant for current page.
	 */
	public function has_access( $id = null ) {
		return apply_filters(
			'ms_rule_content_model_has_access',
			null,
			$id,
			$this
		);
	}

	/**
	 * Set initial protection.
	 *
	 * @since 1.0.0
	 *
	 * @param MS_Model_Relationship $ms_relationship Optional. Not used.
	 */
	public function protect_content( $ms_relationship = false ) {
		parent::protect_content( $ms_relationship );

		// ********* COMMENTS **********

		// No comments on special pages (signup, account, ...)
		$this->add_filter( 'the_content', 'check_special_page' );

		$rule_value = $this->get_rule_value( self::CONTENT_ID );

		/*
		 * This is a static variable so it can collect the most generous
		 * permission of any rule that is applied for the current user.
		 */
		if ( self::COMMENT_NO_ACCESS == self::$comment_access ) {
			self::$comment_access = $rule_value;
		} elseif ( self::COMMENT_READ == self::$comment_access ) {
			if ( $rule_value = self::COMMENT_WRITE ) {
				self::$comment_access = $rule_value;
			}
		}

		$this->add_action(
			'ms_model_plugin_setup_protection_after',
			'protect_comments'
		);

		// ********** READ MORE **********

		$this->protection_message = MS_Plugin::instance()->settings->get_protection_message(
			MS_Model_Settings::PROTECTION_MSG_MORE_TAG
		);

		if ( ! parent::has_access( self::CONTENT_ID ) ) {
			$this->add_filter( 'the_content_more_link', 'show_moretag_protection', 99, 2 );
			$this->add_filter( 'the_content', 'replace_more_tag_content', 1 );
			$this->add_filter( 'the_content_feed', 'replace_more_tag_content', 1 );
		}
	}

	// ********* COMMENTS **********

	/**
	 * Setup the comment permissions after all membership rules were parsed.
	 *
	 * @since  1.0.0
	 */
	public function protect_comments() {
		static $Done = false;

		if ( $Done ) { return; }
		$Done = true;

		switch ( self::$comment_access ) {
			case self::RULE_VALUE_WRITE:
				// Don't change the inherent comment status.
				break;

			case self::RULE_VALUE_READ:
				$this->add_filter( 'comment_form_before', 'hide_form_start', 1 );
				$this->add_filter( 'comment_form_after', 'hide_form_end', 99 );
				add_filter( 'comment_reply_link', '__return_null', 99 );
				break;

			case self::RULE_VALUE_NO_ACCESS:
				add_filter( 'comments_open', '__return_false', 99 );
				add_filter( 'get_comments_number', '__return_zero', 99 );
				break;
		}
	}

	/**
	 * Before the comment form is output we start buffering.
	 *
	 * @since  1.0.4.4
	 */
	public function hide_form_start() {
		ob_start();
	}

	/**
	 * At the end of the comment form we clear the buffer: The form is gone!
	 *
	 * @since  1.0.4.4
	 */
	public function hide_form_end() {
		ob_end_clean();
	}

	/**
	 * Close comments for membership special pages.
	 *
	 * Related Action Hooks:
	 * - the_content
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The content to filter.
	 */
	public function check_special_page( $content ) {
		$ms_pages = MS_Factory::load( 'MS_Model_Pages' );
		if ( $ms_pages->is_membership_page() ) {
			add_filter( 'comments_open', '__return_false', 100 );
		}

		return apply_filters(
			'ms_rule_content_model_check_special_page',
			$content,
			$this
		);
	}

	// ********** READ MORE **********

	/**
	 * Show more tag protection message.
	 *
	 * Related Action Hooks:
	 * - the_content_more_link
	 *
	 * @since 1.0.0
	 *
	 * @param string $more_tag_link the more tag link before filter.
	 * @param string $more_tag The more tag content before filter.
	 * @return string The protection message.
	 */
	public function show_moretag_protection( $more_tag_link, $more_tag ) {
		$msg = stripslashes( $this->protection_message );

		return apply_filters(
			'ms_rule_more_model_show_moretag_protection',
			$msg,
			$more_tag_link,
			$more_tag,
			$this
		);
	}

	/**
	 * Replace more tag
	 *
	 * Related Action Hooks:
	 * - the_content
	 * - the_content_feed
	 *
	 * @since 1.0.0
	 *
	 * @param string $the_content The post content before filter.
	 * @return string The content replaced by more tag content.
	 */
	public function replace_more_tag_content( $the_content ) {
		$more_starts_at = strpos( $the_content, '<span id="more-' );

		if ( false !== $more_starts_at ) {
			$the_content = substr( $the_content, 0, $more_starts_at );
			$the_content .= stripslashes( $this->protection_message );
		}

		return apply_filters(
			'ms_rule_more_model_replace_more_tag_content',
			$the_content,
			$this
		);
	}

	// ********** ADMIN FUNCTIONS **********

	/**
	 * Returns a list of special pages that can be configured by this rule.
	 *
	 * @since  1.0.4
	 *
	 * @param  bool $flat If set to true then all pages are in the same
	 *      hierarchy (no sub-arrays).
	 * @return array List of special pages.
	 */
	protected function get_rule_items() {
		if ( ! is_array( $this->_content ) ) {
			$this->_content = array();

			$this->_content[self::COMMENT_NO_ACCESS] = (object) array(
				'label' => __( 'Comments: No Access', MS_TEXT_DOMAIN ),
			);
			$this->_content[self::COMMENT_READ] = (object) array(
				'label' => __( 'Comments: Read Only Access', MS_TEXT_DOMAIN ),
			);
			$this->_content[self::COMMENT_WRITE] = (object) array(
				'label' => __( 'Comments: Read and Write Access', MS_TEXT_DOMAIN ),
			);
			$this->_content[self::MORE_LIMIT] = (object) array(
				'label' => __( 'Hide "read more" content', MS_TEXT_DOMAIN ),
			);
		}

		return $this->_content;
	}

	/**
	 * Count protection rules quantity.
	 *
	 * @since 1.0.0
	 *
	 * @return int $count The rule count result.
	 */
	public function count_rules( $args = null ) {
		$count = count( $this->get_contents( $args ) );

		return apply_filters(
			'ms_rule_content_model_count_rules',
			$count,
			$this
		);
	}

	/**
	 * Get content to protect.
	 *
	 * @since 1.0.0
	 *
	 * @param $args Optional. Not used.
	 * @return array The content.
	 */
	public function get_contents( $args = null ) {
		$items = $this->get_rule_items();
		$contents = array();

		foreach ( $items as $key => $data ) {
			$content = (object) array();

			// Search the special page name...
			if ( ! empty( $args['s'] ) ) {
				if ( stripos( $data->label, $args['s'] ) === false ) {
					continue;
				}
			}

			$content->id = $key;
			$content->type = MS_Model_RULE::RULE_TYPE_CONTENT;
			$content->name = $data->label;
			$content->post_title = $data->label;

			$content->access = $this->get_rule_value( $content->id );

			$content->delayed_period = $this->has_dripped_rules( $content->id );
			$content->avail_date = $this->get_dripped_avail_date(
				$content->id, MS_Helper_Period::current_date( null, true )
			);

			$contents[ $content->id ] = $content;
		}

		return apply_filters(
			'ms_rule_content_model_get_content',
			$contents,
			$args,
			$this
		);
	}

}