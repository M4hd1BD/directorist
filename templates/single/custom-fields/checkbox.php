<?php
/**
 * @author  wpWax
 * @since   6.6
 * @version 6.7
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$value = $listing->get_custom_field_value( 'checkbox', $data );
?>

<div class="directorist-single-info directorist-single-info-fax">
	<div class="directorist-single-info-label"><?php directorist_icon( $icon );?><?php echo esc_html( $data['label'] ); ?></div>
	<div class="directorist-single-info-value"><?php echo esc_html( $value ); ?></div>
</div>