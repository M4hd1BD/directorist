<?php
/**
 * @author  AazzTech
 * @since   6.7
 * @version 6.7
 */
?>
<div class="atbd_data_info atbd_listing_meta">
	<?php
	// e_var_dump($info);
	foreach ( $info as $item ):
		$listing->render_item( $item );
	endforeach;
	?>
</div>