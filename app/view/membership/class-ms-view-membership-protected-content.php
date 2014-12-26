<?php

/**
 * Render Accessible Content page.
 *
 * Extends MS_View for rendering methods and magic methods.
 *
 * @since 1.0.0
 * @package Membership
 * @subpackage View
 */
class MS_View_Membership_Protected_Content extends MS_View {

	/**
	 * Create view output.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function to_html() {
		// Modify the section header texts.
		$this->remove_filter( 'ms_view_membership_protected_content_header' );
		$this->add_filter(
			'ms_view_membership_protected_content_header',
			'list_header',
			10, 3
		);

		$tabs = $this->data['tabs'];

		$desc = array(
			__( 'In order to set-up Memberships you need to first decidewhat content you want to protect.', MS_TEXT_DOMAIN ),
			__( 'This is the content that will not be accessible to Guests / Logged out users.', MS_TEXT_DOMAIN ),
		);

		ob_start();
		// Render tabbed interface.
		?>
		<div class="ms-wrap wrap">
			<div class="ms-protected-content ms-edit-protection">
				<?php
				MS_Helper_Html::settings_header(
					array(
						'title' => __( 'Select Content to Protect', MS_TEXT_DOMAIN ),
						'desc' => $desc,
					)
				);

				$active_tab = $this->data['active_tab'];
				MS_Helper_Html::html_admin_vertical_tabs( $tabs, $active_tab );

				// Call the appropriate form to render.
				$callback_name = 'render_tab_' . str_replace( '-', '_', $active_tab );
				$render_callback = apply_filters(
					'ms_view_membership_protected_content_render_tab_callback',
					array( $this, $callback_name ),
					$active_tab, $this
				);

				$html = call_user_func( $render_callback );
				$html = apply_filters(
					'ms_view_membership_protected_' . $callback_name,
					$html
				);
				echo '' . $html;
				?>
			</div>
		</div>
		<?php

		// Only in "Protected Content" - not in "Accessible Content"
		if ( isset( $_REQUEST['from'] ) ) {
			$field = array(
				'id'    => 'go_back',
				'type'  => MS_Helper_Html::TYPE_HTML_LINK,
				'value' => __( '&laquo; Back', MS_TEXT_DOMAIN ),
				'url'   => base64_decode( $_REQUEST['from'] ),
				'class' => 'button',
			);
			MS_Helper_Html::html_element( $field );
		}

		$html = ob_get_clean();

		return apply_filters(
			'ms_view_membership_protected_content_to_html',
			$html,
			$this
		);
	}




	/* ====================================================================== *
	 *                               CATEGORY
	 * ====================================================================== */

	/**
	 * Render category tab.
	 *
	 * @since 1.0.0
	 */
	public function render_tab_category() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$rule_cat = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CATEGORY );
		$category_rule_list_table = new MS_Helper_List_Table_Rule_Category(
			$rule_cat,
			$membership
		);
		$category_rule_list_table->prepare_items();

		$rule_cpt = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP );
		$cpt_rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type_Group(
			$rule_cpt,
			$membership
		);
		$cpt_rule_list_table->prepare_items();

		$parts = array();

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) {
			$parts['category'] = __( 'Categories', MS_TEXT_DOMAIN );
		}
		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) {
			$parts['cpt_group'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
		}

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_CATEGORY,
			array(
				'membership' => $this->data['membership'],
				'parts' => $parts,
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_POST_BY_POST ) ) : ?>
				<div class="ms-group">
					<div class="inside">
						<div class="wpmui-field-label">
							<?php _e( 'Protected Categories:', MS_TEXT_DOMAIN ); ?>
						</div>
						<?php
						$category_rule_list_table->display();

						do_action(
							'ms_view_membership_protected_content_footer',
							MS_Model_Rule::RULE_TYPE_CATEGORY,
							$this
						);

						MS_Helper_Html::html_separator();
						?>
					</div>
				</div>
			<?php
			endif;

			if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_CPT_POST_BY_POST ) ) : ?>
				<div class="ms-group">
					<div class="inside">
						<div class="wpmui-field-label">
							<?php _e( 'Protected Custom Post Types:', MS_TEXT_DOMAIN ); ?>
						</div>
						<?php
						$cpt_rule_list_table->display();

						do_action(
							'ms_view_membership_protected_content_footer',
							MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE_GROUP,
							$this
						);
						?>
					</div>
				</div>
			<?php
			endif;
			?>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			null,
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               PAGE
	 * ====================================================================== */

	public function render_tab_page() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_PAGE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Page( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_PAGE,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			$rule_list_table->views();
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->search_box( __( 'Search Pages', MS_TEXT_DOMAIN ), 'search' );
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_PAGE,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               ADMIN SIDE
	 * ====================================================================== */

	public function render_tab_adminside() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_ADMINSIDE );

		$rule_list_table = new MS_Helper_List_Table_Rule_Adminside( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_ADMINSIDE,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			$rule_list_table->views();
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_ADMINSIDE,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               MEMBER CAPS
	 * ====================================================================== */

	public function render_tab_membercaps() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MEMBERCAPS );

		if ( ! MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
			$input_desc = '';
			if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MULTI_MEMBERSHIPS ) ) {
				$input_desc = __( 'Tipp: If a member belongs to more than one membership then the User Role capabilities of both roles are merged.', MS_TEXT_DOMAIN );
			}
			$options = array( '' => __( '(Don\'t change the members role)', MS_TEXT_DOMAIN ) );
			$options += $rule->get_content_array();

			$role_selection = array(
				'id' => 'ms-user-role',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'desc' => $input_desc,
				'value' => $rule->user_role,
				'field_options' => $options,
				'ajax_data' => array(
					'action' => MS_Controller_Rule::AJAX_ACTION_UPDATE_FIELD,
					'membership_id' => $membership->id,
					'rule_type' => $rule->rule_type,
					'field' => 'user_role',
				),
			);
		}

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_MEMBERCAPS,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		$rule_list_table = new MS_Helper_List_Table_Rule_Membercaps( $rule, $membership );
		$rule_list_table->prepare_items();

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
				$rule_list_table->views();
				?>
				<form action="" method="post">
					<?php $rule_list_table->display(); ?>
					<div class="ms-protection-edit-link">
						<?php
						MS_Helper_Html::html_element( $edit_link );

						do_action(
							'ms_view_membership_protected_content_footer',
							MS_Model_Rule::RULE_TYPE_MEMBERCAPS,
							$this
						);
						?>
					</div>
				</form>
				<?php
			} else {
				MS_Helper_Html::html_element( $role_selection );
			}
			?>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}
	/* ====================================================================== *
	 *                               SPECIAL PAGES
	 * ====================================================================== */

	public function render_tab_special() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_SPECIAL );

		$rule_list_table = new MS_Helper_List_Table_Rule_Special( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_SPECIAL,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			$rule_list_table->views();
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_SPECIAL,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               POSTS
	 * ====================================================================== */

	public function render_tab_post() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_POST );
		$rule_list_table = new MS_Helper_List_Table_Rule_Post( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_POST,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			$rule_list_table->views(); ?>
			<form action="" method="post">
				<?php
				$rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' );
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_POST,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               CUSTOM POST TYPE
	 * ====================================================================== */

	public function render_tab_cpt() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Custom_Post_Type( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( array( 'title' => $title, 'desc' => $desc ) );
			MS_Helper_Html::html_separator();

			$rule_list_table->views(); ?>
			<form action="" method="post">
				<?php
				$rule_list_table->search_box( __( 'Search Posts', MS_TEXT_DOMAIN ), 'search' );
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               COMMENT, MORE, MENU
	 * ====================================================================== */

	/**
	 * Render tab content for:
	 * Comments, More tag, Menus
	 *
	 * @since  1.0.0
	 */
	public function render_tab_comment() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$menu_protection = $this->data['settings']->menu_protection;
		$protected_content = MS_Model_Membership::get_protected_content();

		$rule_more_tag = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MORE_TAG );
		$rule_comment = $membership->get_rule( MS_Model_Rule::RULE_TYPE_COMMENT );

		switch ( $menu_protection ) {
			case 'item':
				$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_MENU );
				$rule_list_table = new MS_Helper_List_Table_Rule_Menu(
					$rule_menu,
					$membership,
					$this->data['menu_id']
				);
				break;

			case 'menu':
				$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_REPLACE_MENUS );
				$rule_list_table = new MS_Helper_List_Table_Rule_Replace_Menu(
					$rule_menu,
					$membership
				);
				break;

			case 'location':
				$rule_menu = $membership->get_rule( MS_Model_Rule::RULE_TYPE_REPLACE_MENULOCATIONS );
				$rule_list_table = new MS_Helper_List_Table_Rule_Replace_Menulocation(
					$rule_menu,
					$membership
				);
				break;
		}

		$val_comment = $rule_comment->get_rule_value( MS_Model_Rule_Comment::CONTENT_ID );
		$val_more_tag = absint( $rule_more_tag->get_rule_value( MS_Model_Rule_More::CONTENT_ID ) );

		$fields = array(
			'comment' => array(
				'id' => 'comment',
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Members have:', MS_TEXT_DOMAIN ),
				'value' => $val_comment,
				'field_options' => $rule_comment->get_content_array(),
				'class' => 'chosen-select',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_COMMENT,
					'values' => MS_Model_Rule_Comment::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'more_tag' => array(
				'id' => 'more_tag',
				'type' => MS_Helper_Html::INPUT_TYPE_RADIO,
				'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Members can read full post (beyond the More Tag):', MS_TEXT_DOMAIN ),
				'value' => $val_more_tag,
				'field_options' => $rule_more_tag->get_options_array(),
				'class' => 'ms-more-tag',
				'data_ms' => array(
					'membership_id' => $membership->id,
					'rule_type' => MS_Model_Rule::RULE_TYPE_MORE_TAG,
					'values' => MS_Model_Rule_More::CONTENT_ID,
					'action' => $action,
					'_wpnonce' => $nonce,
				),
			),

			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		if ( 'item' === $menu_protection ) {
			$fields['menu_id'] = array(
				'id' => 'menu_id',
				'title' => __( 'Menus:', MS_TEXT_DOMAIN ),
				'desc' => __( 'Select menu to load:', MS_TEXT_DOMAIN ),
				'value' => $this->data['menu_id'],
				'type' => MS_Helper_Html::INPUT_TYPE_SELECT,
				'field_options' => $this->data['menus'],
				'class' => 'chosen-select',
			);
		}

		if ( MS_Model_Rule_Comment::RULE_VALUE_WRITE === $val_comment ) {
			$fields['comment'] = array(
				'id' => 'comment',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'Comments:', MS_TEXT_DOMAIN ),
				'value' => __( 'Members can Read and Post comments', MS_TEXT_DOMAIN ),
				'class' => 'wpmui-field-description',
				'wrapper' => 'div',
			);
		}

		if ( $val_more_tag ) {
			$fields['more_tag'] = array(
				'id' => 'more_tag',
				'type' => MS_Helper_Html::TYPE_HTML_TEXT,
				'title' => __( 'More Tag:', MS_TEXT_DOMAIN ),
				'value' => __( 'Members can read full post (beyond the More Tag)', MS_TEXT_DOMAIN ),
				'class' => 'wpmui-field-description',
				'wrapper' => 'div',
			);
		}

		$fields = apply_filters(
			'ms_view_membership_protected_content_get_tab_comment_fields',
			$fields
		);

		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_COMMENT,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();
			?>

			<div class="ms-group">
				<div class="ms-half">
					<div class="inside">
						<?php
						MS_Helper_Html::html_element( $fields['comment'] );
						MS_Helper_Html::save_text();

						do_action(
							'ms_view_membership_protected_content_footer',
							MS_Model_Rule::RULE_TYPE_COMMENT,
							$this
						);

						MS_Helper_Html::html_separator( 'vertical' );
						?>
					</div>
				</div>

				<div class="ms-half">
					<div class="inside">
						<?php
						MS_Helper_Html::html_element( $fields['more_tag'] );
						MS_Helper_Html::save_text();

						do_action(
							'ms_view_membership_protected_content_footer',
							MS_Model_Rule::RULE_TYPE_MORE_TAG,
							$this
						);
						?>
					</div>
				</div>
			</div>

			<?php MS_Helper_Html::html_separator(); ?>

			<div class="ms-group">
				<div class="ms-inside">

				<?php if ( 'item' === $menu_protection ) {
					?>
					<form id="ms-menu-form" method="post">
						<?php MS_Helper_Html::html_element( $fields['menu_id'] ); ?>
					</form>
					<?php
					$rule_list_table->display();

					do_action(
						'ms_view_membership_protected_content_footer',
						MS_Model_Rule::RULE_TYPE_MENU,
						$this
					);
				} else {
					$rule_list_table->display();
					if ( 'menu' === $menu_protection ) {
						?>
						<p>
							<?php _e( 'Hint: Only one replacement rule is applied to each menu.', MS_TEXT_DOMAIN ); ?>
						</p>
						<?php
					}
				}
				?>

				</div>
			</div>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               SHORTCODE
	 * ====================================================================== */

	public function render_tab_shortcode() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_SHORTCODE );
		$rule_list_table = new MS_Helper_List_Table_Rule_Shortcode( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_SHORTCODE,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			$rule_list_table->views(); ?>
			<form action="" method="post">
				<?php
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_SHORTCODE,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               URL GROUP
	 * ====================================================================== */

	public function render_tab_url_group() {
		$fields = $this->get_control_fields();

		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$rule = $membership->get_rule( MS_Model_Rule::RULE_TYPE_URL_GROUP );
		$rule_list_table = new MS_Helper_List_Table_Rule_Url_Group( $rule, $membership );
		$rule_list_table->prepare_items();

		$header_data = apply_filters(
			'ms_view_membership_protected_content_header',
			array(),
			MS_Model_Rule::RULE_TYPE_URL_GROUP,
			array(
				'membership' => $this->data['membership'],
			),
			$this
		);

		ob_start();
		?>
		<div class="ms-settings">
			<?php
			MS_Helper_Html::settings_tab_header( $header_data );
			MS_Helper_Html::html_separator();

			$rule_list_table->views();
			?>
			<form action="" method="post">
				<?php
				$rule_list_table->search_box( __( 'Search URLs', MS_TEXT_DOMAIN ), 'search' );
				$rule_list_table->display();

				do_action(
					'ms_view_membership_protected_content_footer',
					MS_Model_Rule::RULE_TYPE_URL_GROUP,
					$this
				);
				?>
			</form>
		</div>
		<?php

		MS_Helper_Html::settings_footer(
			array( $fields['step'] ),
			$this->data['show_next_button']
		);
		return ob_get_clean();
	}

	/* ====================================================================== *
	 *                               SHARED
	 * ====================================================================== */

	public function get_control_fields() {
		$membership = $this->data['membership'];
		$action = $this->data['action'];
		$nonce = wp_create_nonce( $action );

		$fields = array(
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		return apply_filters(
			'ms_view_membership_protected_content_get_control_fields',
			$fields
		);
	}

	/**
	 * Modifies the title/description of the list header
	 *
	 * @since  1.1.0
	 * @param  array $header_data The original header details.
	 * @param  string $rule ID of the list that is displayed.
	 * @param  array $args Additional arguments, specific to $rule.
	 * @return array {
	 *     The new title/description
	 *
	 *     title
	 *     desc
	 * }
	 */
	public function list_header( $header_data, $rule, $args ) {
		switch ( $rule ) {
			case MS_Model_Rule::RULE_TYPE_CATEGORY:
				$args['parts'] = WDev()->get_array( $args['parts'] );

				$header_data['title'] = sprintf(
					__( '%s Access', MS_TEXT_DOMAIN ),
					implode( ', ', $args['parts'] )
				);
				$header_data['desc'] = sprintf(
					__( 'Give access to protected %2$s to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name,
					implode( ' & ', $args['parts'] )
				);
				break;

			case MS_Model_Rule::RULE_TYPE_PAGE:
				$header_data['title'] = __( 'Choose Pages you want to protect', MS_TEXT_DOMAIN );
				$header_data['desc'] = '';
				break;


			case MS_Model_Rule::RULE_TYPE_ADMINSIDE:
				$header_data['title'] = __( 'Admin Side Protection', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Admin Side pages to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_MEMBERCAPS:
				if (  MS_Model_Addon::is_enabled( MS_Model_Addon::ADDON_MEMBERCAPS_ADV ) ) {
					$header_data['title'] = __( 'Member Capabilities', MS_TEXT_DOMAIN );
					$header_data['desc'] = sprintf(
						__( 'All %1$s members are granted the following Capabilities.', MS_TEXT_DOMAIN ),
						$args['membership']->name
					);
				} else {
					$header_data['title'] = __( 'User Roles', MS_TEXT_DOMAIN );
					$header_data['desc'] = sprintf(
						__( 'All %1$s members are assigned to the following User Role.', MS_TEXT_DOMAIN ),
						$args['membership']->name
					);
				}
				break;

			case MS_Model_Rule::RULE_TYPE_SPECIAL:
				$header_data['title'] = __( 'Special Pages', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Special Pages to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_POST:
				$header_data['title'] = __( 'Posts', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Posts to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_CUSTOM_POST_TYPE:
				$header_data['title'] = __( 'Custom Post Types', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to following Custom Post Types to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_COMMENT:
				$header_data['title'] = __( 'Comments, More Tag & Menus', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to protected Comments, More Tag & Menus to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_SHORTCODE:
				$header_data['title'] = __( 'Shortcodes', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to protected Shortcodes to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;


			case MS_Model_Rule::RULE_TYPE_URL_GROUP:
				$header_data['title'] = __( 'URL Protection', MS_TEXT_DOMAIN );
				$header_data['desc'] = sprintf(
					__( 'Give access to protected URLs to %1$s members.', MS_TEXT_DOMAIN ),
					$args['membership']->name
				);
				break;
		}

		return $header_data;
	}

}