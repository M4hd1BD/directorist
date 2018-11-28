<?php
$listings = ATBDP()->user->current_user_listings();
$uid = get_current_user_id();
$c_user = get_userdata($uid);

$u_website = $c_user->user_url;
$avatar = get_user_meta($uid, 'avatar', true);
$u_phone = get_user_meta($uid, 'phone', true);
$u_pro_pic = get_user_meta($uid, 'pro_pic', true);
$facebook = get_user_meta($uid, 'facebook', true);
$twitter = get_user_meta($uid, 'twitter', true);
$google = get_user_meta($uid, 'google', true);
$linkedIn = get_user_meta($uid, 'linkedIn', true);
$youtube = get_user_meta($uid, 'youtube', true);
$u_address = get_user_meta($uid, 'address', true);
$date_format = get_option('date_format');
$featured_active = get_directorist_option('enable_featured_listing');
$is_disable_price = get_directorist_option('disable_list_price');


/*@todo; later show featured listing first on the user dashboard maybe??? */
?>
<div id="directorist" class="directorist atbd_wrapper dashboard_area">
    <div class="<?php echo is_directoria_active() ? 'container' : 'container-fluid'; ?>">
        <div class="row">
            <div class="col-md-12">

                <div class="atbd_add_listing_title">
                    <h2><?php _e('My Dashboard', ATBDP_TEXTDOMAIN); ?></h2>
                </div> <!--ends add_listing_title-->

                <div class="atbd_dashboard_wrapper">
                    <div class="atbd_user_dashboard_nav">
                        <!-- Nav tabs -->
                        <ul class="nav nav-tabs" role="tablist">
                            <li role="presentation" class="active nav-item">
                                <a href="#my_listings" class="nav-link active" aria-controls="my_listings" role="tab"
                                   data-toggle="tab">
                                    <?php $list_found = ($listings->found_posts > 0) ? $listings->found_posts : '0';
                                    printf(__('My Listing (%s)', ATBDP_TEXTDOMAIN), $list_found); ?>
                                </a>
                            </li>
                            <li role="presentation" class="nav-item"><a href="#profile" class="nav-link"
                                                                        aria-controls="profile" role="tab"
                                                                        data-toggle="tab"><?php _e('My Profile', ATBDP_TEXTDOMAIN); ?></a>
                            </li>
                        </ul>

                        <div class="nav_button">
                            <a href="<?= esc_url(ATBDP_Permalink::get_add_listing_page_link()); ?>"
                               class="<?= atbdp_directorist_button_classes(); ?>"><?php _e('Submit New Listing', ATBDP_TEXTDOMAIN); ?></a>
                            <a href="<?= esc_url(wp_logout_url()); ?>"
                               class="<?= atbdp_directorist_button_classes(); ?>"><?php _e('Log Out', ATBDP_TEXTDOMAIN); ?></a>
                        </div>
                    </div> <!--ends dashboard_nav-->

                    <!-- Tab panes -->
                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane active row" data-uk-grid id="my_listings">
                            <?php if ($listings->have_posts()) {
                                foreach ($listings->posts as $post) {
                                    // get only one parent or high level term object
                                    $top_category = ATBDP()->taxonomy->get_one_high_level_term($post->ID, ATBDP_CATEGORY);
                                    $price = get_post_meta($post->ID, '_price', true);
                                    $featured = get_post_meta($post->ID, '_featured', true);
                                    $listing_img = get_post_meta($post->ID, '_listing_img', true);
                                    $tagline = get_post_meta($post->ID, '_tagline', true);

                                    ?>
                                    <div class="col-lg-4 col-sm-6" id="listing_id_<?= $post->ID; ?>">
                                        <div class="atbd_single_listing atbd_listing_card">
                                            <article class="atbd_single_listing_wrapper <?php echo ($featured) ? 'directorist-featured-listings' : ''; ?>">
                                                <figure class="atbd_listing_thumbnail_area">
                                                    <div class="atbd_listing_image">
                                                        <?= (!empty($listing_img[0])) ? '<img src="' . esc_url(wp_get_attachment_image_url($listing_img[0], array(432, 400))) . '" alt="listing image">' : '' ?>
                                                    </div>

                                                    <figcaption class="atbd_thumbnail_overlay_content">

                                                        <div class="atbd_lower_badge">
                                                            <?php
                                                            if ($featured) {
                                                                printf(
                                                                    '<span class="atbd_badge atbd_badge_featured">Featured</span>',
                                                                    esc_html__('Featured', ATBDP_TEXTDOMAIN)
                                                                );
                                                            }
                                                            ?>
                                                        </div>
                                                    </figcaption>
                                                </figure>

                                                <?php /*todo: Shahadat -> please implement the current markup*/ ?>
                                                <div class="atbd_listing_info">
                                                    <div class="atbd_content_upper">
                                                        <div class="atbd_dashboard_tittle_metas">
                                                            <h4 class="atbd_listing_title">
                                                                <a href="<?= get_post_permalink($post->ID); ?>">
                                                                    <?= !empty($post->post_title) ? esc_html(stripslashes($post->post_title)) : ''; ?>
                                                                </a>
                                                            </h4>

                                                            <?php /* todo: Shahadat -> new markup implemented */ ?>
                                                            <div class="atbd_listing_meta">
                                                                <?php
                                                                /**
                                                                 * Fires after the title and sub title of the listing is rendered
                                                                 *
                                                                 *
                                                                 * @since 1.0.0
                                                                 */

                                                                do_action('atbdp_after_listing_tagline');
                                                                /*@todo: Shahadat -> added new markup, Average pricing */ ?>
                                                            </div><!-- End atbd listing meta -->
                                                        </div>

                                                        <div class="db_btn_area">
                                                            <?php
                                                            $lstatus = get_post_meta($post->ID, '_listing_status', true);
                                                            // If the listing needs renewal then there is no need to show promote button
                                                            if ('renewal' == $lstatus || 'expired' == $lstatus) {
                                                                $can_renew = get_directorist_option('can_renew_listing');
                                                                if (!$can_renew) return false;// vail if renewal option is turned off on the site.
                                                                ?>
                                                                <a href="<?= esc_url(ATBDP_Permalink::get_renewal_page_link($post->ID)) ?>"
                                                                   id="directorist-renew"
                                                                   data-listing_id="<?= $post->ID; ?>"
                                                                   class="directory_btn btn btn-default">
                                                                    <?php _e('Renew', ATBDP_TEXTDOMAIN); ?>
                                                                </a>
                                                                <!--@todo; add expiration and renew date-->
                                                            <?php } else {
                                                                // show promotions if the featured is available
                                                                // featured available but the listing is not featured, show promotion button
                                                                if ($featured_active && empty($featured)) {
                                                                    ?>
                                                                    <div class="atbd_promote_btn_wrapper">
                                                                        <a href="<?= esc_url(ATBDP_Permalink::get_checkout_page_link($post->ID)) ?>"
                                                                           id="directorist-promote"
                                                                           data-listing_id="<?= $post->ID; ?>"
                                                                           class="directory_btn btn btn-primary">
                                                                            <?php _e('Promote Your listing', ATBDP_TEXTDOMAIN); ?>
                                                                        </a>
                                                                    </div>
                                                                <?php }
                                                            } ?>

                                                            <a href="<?= esc_url(ATBDP_Permalink::get_edit_listing_page_link($post->ID)); ?>"
                                                               id="edit_listing"
                                                               class="directory_edit_btn btn btn-outline-primary"><?php _e('Edit', ATBDP_TEXTDOMAIN); ?></a>
                                                            <a href="#" id="remove_listing"
                                                               data-listing_id="<?= $post->ID; ?>"
                                                               class="directory_remove_btn btn btn-outline-danger"><?php _e('Delete', ATBDP_TEXTDOMAIN); ?></a>
                                                        </div> <!--ends .db_btn_area-->
                                                        <?php /* @todo: deleted the read more link */ ?>
                                                    </div><!-- end ./atbd_content_upper -->

                                                    <div class="atbd_listing_bottom_content">
                                                        <div class="listing-meta">
                                                            <?php
                                                            $exp_date = get_post_meta($post->ID, '_expiry_date', true);
                                                            $never_exp = get_post_meta($post->ID, '_never_expire', true);
                                                            $exp_text = !empty($never_exp)
                                                                ? __('Never Expires', ATBDP_TEXTDOMAIN)
                                                                : date_i18n($date_format, strtotime($exp_date)); ?>
                                                            <p><?php printf(__('<span>Expiration:</span> %s', ATBDP_TEXTDOMAIN), $exp_text); ?></p>
                                                            <p><?php printf(__('<span>Listing Status:</span> %s', ATBDP_TEXTDOMAIN), get_post_status_object($post->post_status)->label); ?></p>
                                                            <?php
                                                            atbdp_display_price($price, $is_disable_price);
                                                            /**
                                                             * Fires after the price of the listing is rendered
                                                             *
                                                             *
                                                             * @since 3.1.0
                                                             */
                                                            do_action('atbdp_after_listing_price');
                                                            ?>

                                                        </div>
                                                    </div><!-- end ./atbd_listing_bottom_content -->
                                                </div>
                                            </article>
                                        </div>
                                    </div> <!--ends . col-lg-3 col-sm-6-->
                                    <?php
                                }
                            } else {
                                esc_html_e('Looks like you have not created any listing yet!', ATBDP_TEXTDOMAIN);
                            }
                            //@todo;add pagination on dashboard echo atbdp_pagination($listings, $paged);
                            ?>

                        </div> <!--ends #my_listings-->
                        <div role="tabpanel" class="tab-pane " id="profile">
                            <form action="#" id="user_profile_form" method="post">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="user_pro_img_area">
                                            <div class="user_img" id="profile_pic_container">
                                                <div class="cross" id="remove_pro_pic"><span class="fa fa-times"></span>
                                                </div>
                                                <div class="choose_btn">
                                                    <input type="hidden" name="user[pro_pic]" id="pro_pic"
                                                           value="<?= !empty($u_pro_pic) ? esc_url($u_pro_pic) : ''; ?>">
                                                    <label for="pro_pic"
                                                           id="upload_pro_pic"><?php _e('Change', ATBDP_TEXTDOMAIN); ?></label>
                                                </div> <!--ends .choose_btn-->
                                                <img src="<?= !empty($u_pro_pic) ? esc_url($u_pro_pic) : esc_url(ATBDP_PUBLIC_ASSETS . 'images/no-image.jpg'); ?>"
                                                     id="pro_img" alt="">

                                            </div> <!--ends .user_img-->
                                        </div> <!--ends .user_pro_img_area-->
                                    </div> <!--ends .col-md-4-->

                                    <div class="col-md-8">
                                        <div class="profile_title"><h4><?php _e('My Profile', ATBDP_TEXTDOMAIN); ?></h4>
                                        </div>

                                        <div class="user_info_wrap">
                                            <!--hidden inputs-->
                                            <input type="hidden" name="ID" value="<?= get_current_user_id(); ?>">
                                            <!--Full name-->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="full_name">Full Name</label>
                                                        <input class="form-control" type="text" name="user[full_name]"
                                                               value="<?= !empty($c_user->display_name) ? esc_attr($c_user->display_name) : ''; ?>"
                                                               placeholder="Enter your full name">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="user_name">User Name</label>
                                                        <input class="form-control" id="user_name" type="text"
                                                               disabled="disabled" name="user[user_name]"
                                                               value="<?= !empty($c_user->user_login) ? esc_attr($c_user->user_login) : ''; ?>"> <?php _e('(username can not be changed)', ATBDP_TEXTDOMAIN); ?>
                                                    </div>
                                                </div>
                                            </div> <!--ends .row-->
                                            <!--First Name-->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="first_name"><?php _e('First Name', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input class="form-control" id="first_name" type="text"
                                                               name="user[first_name]"
                                                               value="<?= !empty($c_user->first_name) ? esc_attr($c_user->first_name) : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="last_name"><?php _e('Last Name', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input class="form-control" id="last_name" type="text"
                                                               name="user[last_name]"
                                                               value="<?= !empty($c_user->last_name) ? esc_attr($c_user->last_name) : ''; ?>">
                                                    </div>
                                                </div>
                                            </div> <!--ends .row-->
                                            <!--Email-->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="req_email"><?php _e('Email (required)', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input class="form-control" id="req_email" type="text"
                                                               name="user[user_email]"
                                                               value="<?= !empty($c_user->user_email) ? esc_attr($c_user->user_email) : ''; ?>"
                                                               required>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="phone"><?php _e('Cell Number', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input class="form-control" type="tel" name="user[phone]"
                                                               value="<?= !empty($u_phone) ? esc_attr($u_phone) : ''; ?>"
                                                               placeholder="Enter your phone number">
                                                    </div>
                                                </div>
                                            </div> <!--ends .row-->
                                            <!--Website-->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="website"><?php _e('Website', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input class="form-control" id="website" type="text"
                                                               name="user[website]"
                                                               value="<?= !empty($u_website) ? esc_url($u_website) : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="address"><?php _e('Address', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input class="form-control" id="address" type="text"
                                                               name="user[address]"
                                                               value="<?= !empty($u_address) ? esc_attr($u_address) : ''; ?>">
                                                    </div>
                                                </div>
                                            </div> <!--ends .row-->


                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="new_pass"><?php _e('New Password', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input id="new_pass" class="form-control" type="password" name="user[new_pass]"
                                                               value="<?= !empty($new_pass) ? esc_attr($new_pass) : ''; ?>"
                                                               placeholder="Enter a new password">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="confirm_pass"><?php _e('Confirm New Password', ATBDP_TEXTDOMAIN); ?></label>
                                                        <input id="confirm_pass" class="form-control" type="password" name="user[confirm_pass]"
                                                               value="<?= !empty($confirm_pass) ? esc_attr($confirm_pass) : ''; ?>"
                                                               placeholder="Confirm your new password">
                                                    </div>
                                                </div>
                                            </div><!--ends .row-->
                                            <!--social info-->
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="facebook"><?php _e('Facebook', ATBDP_TEXTDOMAIN); ?></label>
                                                        <p><?php _e('Keep blank to hide it', ATBDP_TEXTDOMAIN)?></p>
                                                        <input id="facebook" class="form-control" type="url" name="user[facebook]"
                                                               value="<?= !empty($facebook) ? esc_attr($facebook) : ''; ?>"
                                                               placeholder="Enter your facebook url">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="twitter"><?php _e('Twitter', ATBDP_TEXTDOMAIN); ?></label>
                                                        <p><?php _e('Keep blank to hide it', ATBDP_TEXTDOMAIN)?></p>
                                                        <input id="twitter" class="form-control" type="url" name="user[twitter]"
                                                               value="<?= !empty($twitter) ? esc_attr($twitter) : ''; ?>"
                                                               placeholder="Enter your twitter url">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="google"><?php _e('Google+', ATBDP_TEXTDOMAIN); ?></label>
                                                        <p><?php _e('Keep blank to hide it', ATBDP_TEXTDOMAIN)?></p>
                                                        <input id="google" class="form-control" type="url" name="user[google]"
                                                               value="<?= !empty($google) ? esc_attr($google) : ''; ?>"
                                                               placeholder="Enter google+ url">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label for="linkedIn"><?php _e('LinkedIn', ATBDP_TEXTDOMAIN); ?></label>
                                                        <p><?php _e('Keep blank to hide it', ATBDP_TEXTDOMAIN)?></p>
                                                        <input id="linkedIn" class="form-control" type="url" name="user[linkedIn]"
                                                               value="<?= !empty($linkedIn) ? esc_attr($linkedIn) : ''; ?>"
                                                               placeholder="Enter linkedIn url">
                                                    </div>
                                                </div>
                                                <div class="col-md-12">
                                                    <div class="form-group">
                                                        <label for="youtube"><?php _e('Youtube', ATBDP_TEXTDOMAIN); ?></label>
                                                        <p><?php _e('Keep blank to hide it', ATBDP_TEXTDOMAIN)?></p>
                                                        <input id="youtube" class="form-control" type="url" name="user[youtube]"
                                                               value="<?= !empty($youtube) ? esc_attr($youtube) : ''; ?>"
                                                               placeholder="Enter youtube url">
                                                    </div>
                                                </div>
                                            </div><!--ends social info .row-->


                                            <button type="submit" class="btn btn-primary"
                                                    id="update_user_profile"><?php _e('Save Changes', ATBDP_TEXTDOMAIN); ?></button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            <div id="pro_notice" style="display: inline-block; padding: 20px">

                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>
