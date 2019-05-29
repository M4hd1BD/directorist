
<?php
wp_enqueue_script('atbdp-map-view',ATBDP_PUBLIC_ASSETS . 'js/map-view.js');
$data = array(
    'plugin_url' => ATBDP_URL,
    'zoom'       => !empty($zoom) ? $zoom : 4
);
wp_localize_script( 'atbdp-map-view', 'atbdp_map', $data );
?>
<div class="atbdp-body atbdp-map embed-responsive embed-responsive-16by9 atbdp-margin-bottom" data-type="markerclusterer" style="height: <?php echo !empty($listings_map_height)?$listings_map_height:'';?>px;">
    <?php while( $all_listings->have_posts() ) : $all_listings->the_post();
        global $post;
        $manual_lat                     = get_post_meta($post->ID, '_manual_lat', true);
        $manual_lng                     = get_post_meta($post->ID, '_manual_lng', true);
        $listing_img                    = get_post_meta(get_the_ID(), '_listing_img', true);
        $listing_prv_img                = get_post_meta(get_the_ID(), '_listing_prv_img', true);
        $crop_width                     = get_directorist_option('crop_width', 360);
        $crop_height                    = get_directorist_option('crop_height', 300);
        $address                        = get_post_meta(get_the_ID(), '_address', true);
        if(!empty($listing_prv_img)) {

            $prv_image   = wp_get_attachment_image_src($listing_prv_img, 'large')[0];

        }
        if(!empty($listing_img[0])) {

            $default_img = atbdp_image_cropping(ATBDP_PUBLIC_ASSETS . 'images/grid.jpg', $crop_width, $crop_height, true, 100)['url'];;
            $gallery_img = wp_get_attachment_image_src($listing_img[0], 'medium')[0];

        }
        ?>

        <?php if( ! empty( $manual_lat ) && ! empty( $manual_lng ) ) : ?>
            <div class="marker" data-latitude="<?php echo $manual_lat; ?>" data-longitude="<?php echo $manual_lng; ?>">
                <div>

                    <div class="media-left">
                        <a href="<?php the_permalink(); ?>">
                            <?php
                            $default_image = get_directorist_option('default_preview_image', ATBDP_PUBLIC_ASSETS . 'images/grid.jpg');
                            if(!empty($listing_prv_img)){

                                echo '<img src="'.esc_url($prv_image).'" alt="'.esc_html(stripslashes(get_the_title())).'">';

                            } if(!empty($listing_img[0]) && empty($listing_prv_img)) {

                                echo '<img src="' . esc_url($gallery_img) . '" alt="'.esc_html(stripslashes(get_the_title())).'">';

                            }if (empty($listing_img[0]) && empty($listing_prv_img)){

                                echo '<img src="'.$default_image.'" alt="'.esc_html(stripslashes(get_the_title())).'">';

                            }
                            ?>
                        </a>
                    </div>


                    <div class="media-body">
                        <div class="atbdp-listings-title-block">
                            <h3 class="atbdp-no-margin"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

                        </div>

                        <span class="fa fa-briefcase"></span> <a href="" class="map-info-link"><?php echo $address;?></a>


                        <?php do_action( 'atbdp_after_listing_content', $post->ID, 'map' ); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    <?php endwhile; ?>
</div>