<?php
/**
 * Plugin Name: Referral Extension for GiveWP
 * Plugin URI: https://staubrein.com
 * Description: This plugin adds the possibility to save referrals in donations
 * Version: 1.0
 * Author: Dustin Kroeger
 * Author URI: https://staubrein.com
 */

// add the referral get parameter globally
function stb_add_referral_query_var( $vars ){
    $vars[] = 'referral';
    return $vars;
}
add_filter( 'query_vars', 'stb_add_referral_query_var' );

// set the referral cookie if needed
function stb_set_check_referral_cookie() {

    if ( is_admin() ) {
        return;
    }
    // If the query string "refferal" is not set or Cookie exists, exit this function
    if ( ! isset($_GET['referral']) || isset( $_COOKIE['stb_referral'] ) ) {
        return;
    }
    setcookie( 'stb_referral', $_GET['referral'], time()+3600*24*3, COOKIEPATH, COOKIE_DOMAIN );
}
add_action( 'init', 'stb_set_check_referral_cookie', 0 );

/**
 * Custom Form Fields in Donation form
 *
 * @param $form_id
 */
function stb_give_donations_referral_field( $form_id ) {

    ?>
    <div id="give-message-wrap" class="form-row form-row-wide">
        <label class="give-label" for="give-engraving-message">
            <?php _e( 'What should be engraved on the plaque?', 'give' ); ?>
            <span class="give-tooltip give-icon give-icon-question"
                    data-tooltip="<?php _e( 'Please provide the names that should be engraved on the plaque.', 'give' ) ?>">
            </span>
        </label>

        <textarea class="give-textarea" name="referral" id="give-engraving-message"><?=$_COOKIE['stb_referral']?></textarea>
    </div>
    <?php
}

add_action( 'give_after_donation_levels', 'stb_give_donations_referral_field' );


/**
 * Add Field to Payment Meta
 *
 * Store the custom field data custom post meta attached to the `give_payment` CPT.
 *
 * @param $payment_id
 *
 * @return mixed
 */
function stb_give_donations_save_referral( $payment_id ) {

	if ( isset( $_POST['referral'] ) ) {
		$message = wp_strip_all_tags( $_POST['referral'], true );
		give_update_payment_meta( $payment_id, 'stb_referral', $message );
	}
}
add_action( 'give_insert_payment', 'stb_give_donations_save_referral' );

/**
 * Show Data in Transaction Details
 *
 * Show the custom field(s) on the transaction page.
 *
 * @param $payment_id
 */
function stb_give_donations_donation_details( $payment_id ) {

	$referral = give_get_meta( $payment_id, 'stb_referral', true );

	if ( $referral ) : ?>

		<div id="give-referral" class="postbox">
			<h3 class="hndle"><?php esc_html_e( 'Referral Code', 'give' ); ?></h3>
			<div class="inside" style="padding-bottom:10px;">
				<?php echo wpautop( $referral ); ?>
			</div>
		</div>

	<?php endif;

}

add_action( 'give_view_donation_details_billing_before', 'stb_give_donations_donation_details', 10, 1 );


/**
 * Add Donation referral header in CSV.
 *
 * @param array $cols columns name for CSV
 *
 * @return  array $cols columns name for CSV
 */
function stb_update_columns_heading( $cols ) {
	if ( isset( $cols['stb_referral'] ) ) {
		$cols['stb_referral'] = __( 'Referral Code', 'give' );
	}

	return $cols;

}

add_filter( 'give_export_donation_get_columns_name', 'stb_update_columns_heading' );


/**
 * Add Donation referral fields in CSV.
 *
 * @param array Donation data.
 * @param Give_Payment $payment Instance of Give_Payment
 * @param array $columns Donation data $columns that are not being merge
 *
 * @return array Donation data.
 */
function stb_export_donation_data( $data, $payment, $columns ) {
	if ( ! empty( $columns['stb_referral'] ) ) {
		$message                        = $payment->get_meta( 'stb_referral' );
		$data['stb_referral'] = isset( $message ) ? wp_kses_post( $message ) : '';
	}

	return $data;
}

add_filter( 'give_export_donation_data', 'stb_export_donation_data', 10, 3 );

/**
 * Remove Custom meta fields from Export donation standard fields.
 *
 * @param array $responses Contain all the fields that need to be display when donation form is display
 * @param int $form_id Donation Form ID
 *
 * @return array $responses
 */
function stb_export_custom_fields( $responses, $form_id ) {

	if ( ! empty( $responses['standard_fields'] ) ) {
		$standard_fields = $responses['standard_fields'];
		if ( in_array( 'stb_referral', $standard_fields ) ) {
			$standard_fields              = array_diff( $standard_fields, array( 'stb_referral' ) );
			$responses['standard_fields'] = $standard_fields;
		}
	}

	return $responses;
}

add_filter( 'give_export_donations_get_custom_fields', 'stb_export_custom_fields', 10, 2 );
