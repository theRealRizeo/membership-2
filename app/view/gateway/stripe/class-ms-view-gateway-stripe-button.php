<?php

class MS_View_Gateway_Stripe_Button extends MS_View {

	public function to_html() {
		$fields = $this->prepare_fields();

		$invoice = MS_Model_Invoice::get_current_invoice( $this->data['ms_relationship'] );
		$member = MS_Model_Member::get_current_member();
		$gateway = $this->data['gateway'];

		// Stripe is using Ajax, so the URL is empty.
		$action_url = apply_filters(
			'ms_view_gateway_button_form_action_url',
			''
		);

		$row_class = 'gateway_' . $this->data['gateway']->id;
		if ( ! $this->data['gateway']->is_live_mode() ) {
			$row_class .= ' sandbox-mode';
		}

		ob_start();
		?>
		<tr class="<?php echo esc_attr( $row_class ); ?>">
			<td class='ms-buy-now-column' colspan='2' >
				<form action="<?php echo esc_url( $action_url ); ?>" method="post">
					<?php
					foreach ( $fields as $field ) {
						MS_Helper_Html::html_element( $field );
					}
					?>
					<script
						src="https://checkout.stripe.com/checkout.js" class="stripe-button"
						data-key="<?php echo esc_attr( $gateway->get_publishable_key() ); ?>"
						data-amount="<?php echo esc_attr( $invoice->total * 100 ); //amount in cents ?>"
						data-name="<?php echo esc_attr( bloginfo( 'name' ) ); ?>"
						data-description="<?php echo esc_attr( strip_tags( $invoice->description ) ); ?>"
						data-currency="<?php echo esc_attr( $invoice->currency ); ?>"
						data-panel-label="<?php echo esc_attr( $gateway->pay_button_url ); ?>"
						data-email="<?php echo esc_attr( $member->email ); ?>"
						>
					</script>
				</form>
			</td>
		</tr>
		<?php
		$html = ob_get_clean();
		return $html;
	}

	private function prepare_fields() {
		$fields = array(
			'_wpnonce' => array(
				'id' => '_wpnonce',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => wp_create_nonce(
					$this->data['gateway']->id . '_' . $this->data['ms_relationship']->id
				),
			),
			'gateway' => array(
				'id' => 'gateway',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['gateway']->id,
			),
			'ms_relationship_id' => array(
				'id' => 'ms_relationship_id',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['ms_relationship']->id,
			),
			'step' => array(
				'id' => 'step',
				'type' => MS_Helper_Html::INPUT_TYPE_HIDDEN,
				'value' => $this->data['step'],
			),
		);

		return $fields;
	}
}