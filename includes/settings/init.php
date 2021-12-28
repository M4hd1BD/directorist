<?php
/**
 * @author wpWax
 */

namespace wpWax\Directorist;

defined( 'ABSPATH' ) || die();

class Settings {

	private $extension_url    = '';
	public $sections          = [];
	public $fields            = [];
	public $layouts           = [];
	public $config            = [];
	public $default_form      = [];
	public $old_custom_fields = [];
	public $cetagory_options  = [];

	public function run() {
		add_action('directorist_installed', [ $this, 'update_init_options' ] );
		add_action('directorist_updated', [ $this, 'update_init_options' ] );

		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [$this, 'add_menu_pages'] );
		add_action( 'wp_ajax_save_settings_data', [ $this, 'handle_save_settings_data_request' ] );
		add_action( 'wp_ajax_save_settings_data', [ $this, 'handle_save_settings_data_request' ] );
		add_filter( 'atbdp_listing_type_settings_field_list', [ $this, 'register_setting_fields' ] );

		$this->extension_url = sprintf("<a target='_blank' href='%s'>%s</a>", esc_url(admin_url('edit.php?post_type=at_biz_dir&page=atbdp-extension')), __('Checkout Awesome Extensions', 'directorist'));
	}

	public function update_init_options() {
		// Set lazy_load_taxonomy_fields option
		$enable_lazy_loading = directorist_has_no_listing() ? true : false;
		update_directorist_option( 'lazy_load_taxonomy_fields', $enable_lazy_loading );
	}

    public static function in_settings_page() {
        if ( ! is_admin() ) {
            return false;
        }

        if ( ! isset( $_REQUEST['post_type'] ) && ! isset( $_REQUEST['page'] ) ) {
            return false;
        }

        if ( 'at_biz_dir' !== $_REQUEST['post_type'] && 'atbdp-settings' !== $_REQUEST['page'] ) {
            return false;
        }

        return true;
    }

	// get_simple_data_content
	public function get_simple_data_content( array $args = [] ) {
		$default = [ 'path' => '', 'json_decode' => true ];
		$args = array_merge( $default,  $args );

		$path = ( ! empty( $args['path'] ) ) ? $args['path'] : '';

		// $path = 'directory/directory.json'
		$file = trailingslashit( dirname( ATBDP_FILE ) ) . "admin/assets/simple-data/{$path}";
		if ( ! file_exists( $file ) ) { return ''; }

		$data = file_get_contents( $file );

		if ( $args['json_decode'] ) {
			$data = json_decode( $data, true );
		}

		return $data;
	}

	// handle_save_settings_data_request
	public function handle_save_settings_data_request() {
		$status = [ 'success' => false, 'status_log' => [] ];

        if ( ! directorist_verify_nonce() ) {
            $status['status_log'] = [
				'type' => 'error',
				'message' => __( 'Something is wrong! Please refresh and retry.', 'directorist' ),
			];

            wp_send_json( [ 'status' => $status ] );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            $status['status_log'] = [
				'type' => 'error',
				'message' => __( 'You are not allowed to access this resource', 'directorist' ),
			];

			wp_send_json( [ 'status' => $status ] );
        }


		$field_list = ( ! empty( $_POST['field_list'] ) ) ? Directorist\Helper::maybe_json( $_POST['field_list'] ) : [];

		// If field list is empty
		if ( empty( $field_list ) || ! is_array( $field_list ) ) {
			$status['status_log'] = [
				'type' => 'success',
				'message' => __( 'No changes made', 'directorist' ),
			];

			wp_send_json( [ 'status' => $status, 'field_list' => $field_list ] );
		}

		$options = [];
		foreach ( $field_list as $field_key ) {
			if ( ! isset( $_POST[ $field_key ] ) ) { continue; }

			$options[ $field_key ] = $_POST[ $field_key ];
		}

        // Prepare Settings
        $this->prepare_settings();

		$update_settings_options = $this->update_settings_options( $options );

		wp_send_json( $update_settings_options );
	}

	// update_settings_options
	public function update_settings_options( array $options = [] ) {
		$status = [ 'success' => false, 'status_log' => [] ];

		// If field list is empty
		if ( empty( $options ) || ! is_array( $options ) ) {
			$status['status_log'] = [
				'type' => 'success',
				'message' => __( 'Nothing to save', 'directorist' ),
			];

			return [ 'status' => $status ];
		}

		// Update the options
		$atbdp_options = get_option('atbdp_option');
		foreach ( $options as $option_key => $option_value ) {
			if ( ! isset( $this->fields[ $option_key ] ) ) { continue; }

			$atbdp_options[ $option_key ] = Directorist\Helper::maybe_json( $option_value, true );
		}

		update_option( 'atbdp_option', $atbdp_options );

		// Send Status
		$status['options'] = $options;
		$status['success'] = true;
		$status['status_log'] = [
			'type' => 'success',
			'message' => __( 'Saving Successful', 'directorist' ),
		];

		return [ 'status' => $status ];
	}

	// maybe_serialize
	public function maybe_serialize($value = '') {
		return maybe_serialize(Directorist\Helper::maybe_json($value));
	}

    // add_menu_pages
    public function add_menu_pages() {
        add_submenu_page(
            'edit.php?post_type=at_biz_dir',
            'Settings',
            'Settings',
            'manage_options',
            'atbdp-settings',
            [ $this, 'menu_page_callback__settings_manager' ],
            12
        );
    }

    // menu_page_callback__settings_manager
    public function menu_page_callback__settings_manager() {
        // Prepare Settings
        $this->prepare_settings();

        // Get Saved Data
        $atbdp_options = get_option('atbdp_option');

        foreach( $this->fields as $field_key => $field_opt ) {
            if ( ! isset(  $atbdp_options[ $field_key ] ) ) {
                $this->fields[ $field_key ]['forceUpdate'] = true;
                continue;
            }

            $this->fields[ $field_key ]['value'] = $atbdp_options[ $field_key ];
        }

        $atbdp_settings_manager_data = [
            'fields'  => $this->fields,
            'layouts' => $this->layouts,
            'config'  => $this->config,
        ];

        wp_localize_script('directorist-settings-manager', 'atbdp_settings_manager_data', $atbdp_settings_manager_data);

        atbdp_load_admin_template('settings-manager/settings');
    }

    /**
     * Get all the pages in an array where each page is an array of key:value:id and key:label:name
     *
     * Example : array(
     *                  array('value'=> 1, 'label'=> 'page_name'),
     *                  array('value'=> 50, 'label'=> 'page_name'),
     *          )
     * @return array page names with key value pairs in a multi-dimensional array
     * @since 3.0.0
     */
    function get_pages_vl_arrays() {
        $pages = get_pages();
        $pages_options = array();
        if ($pages) {
            foreach ($pages as $page) {
                $pages_options[] = array('value' => $page->ID, 'label' => $page->post_title);
            }
        }

        return $pages_options;
    }

    function get_user_roles() {
        $get_editable_roles = get_editable_roles();
        $role               = array();
        $role[]             = array( 'value' => 'all', 'label' => __( 'All', 'directorist' ) );
        if( $get_editable_roles ) {
            foreach( $get_editable_roles as $key => $value ) {
                $role[] = array(
                    'value' => $key,
                    'label' => $value['name']
                );
            }
        }

        return $role;
    }

    /**
     * Get all the pages with previous page in an array where each page is an array of key:value:id and key:label:name
     *
     * Example : array(
     *                  array('value'=> 1, 'label'=> 'page_name'),
     *                  array('value'=> 50, 'label'=> 'page_name'),
     *          )
     * @return array page names with key value pairs in a multi-dimensional array
     * @since 3.0.0
     */
    function get_pages_with_prev_page() {
        $pages = get_pages();
        $pages_options = array();
        $pages_options[] = array( 'value' => 'previous_page', 'label' => 'Previous Page' );
        if ($pages) {
            foreach ($pages as $page) {
                $pages_options[] = array('value' => $page->ID, 'label' => $page->post_title);
            }
        }

        return $pages_options;
    }

    /**
     * Get an array of events to notify both the admin and the users
     * @return array it returns an array of events
     * @since 3.1.0
     */
    private function default_notifiable_events() {
        return apply_filters('atbdp_default_notifiable_events', array(
            array(
                'value' => 'order_created',
                'label' => __('Order Created', 'directorist'),
            ),
            array(
                'value' => 'order_completed',
                'label' => __('Order Completed', 'directorist'),
            ),
            array(
                'value' => 'listing_submitted',
                'label' => __('New Listing Submitted', 'directorist'),
            ),
            array(
                'value' => 'listing_published',
                'label' => __('Listing Approved/Published', 'directorist'),
            ),
            array(
                'value' => 'listing_edited',
                'label' => __('Listing Edited', 'directorist'),
            ),
            array(
                'value' => 'payment_received',
                'label' => __('Payment Received', 'directorist'),
            ),
            array(
                'value' => 'listing_deleted',
                'label' => __('Listing Deleted', 'directorist'),
            ),
            array(
                'value' => 'listing_contact_form',
                'label' => __('Listing Contact Form', 'directorist'),
            ),
            array(
                'value' => 'listing_review',
                'label' => __('Listing Review', 'directorist'),
            ),
        ));
    }

    /**
     * Get the list of an array of notification events array to notify admin
     * @return array It returns an array of events when an admin should be notified
     * @since 3.1.0
     */
    public function events_to_notify_admin() {
        $events = $this->default_notifiable_events();
        return apply_filters('atbdp_events_to_notify_admin', $events);
    }

    /**
     * Get the list of an array of notification events array to notify user
     * @return array It returns an array of events when an user should be notified
     * @since 3.1.0
     */
    public function events_to_notify_user() {
        $events = array_merge($this->default_notifiable_events(), $this->only_user_notifiable_events());
        return apply_filters('atbdp_events_to_notify_user', $events);
    }

    /**
     * Get the default events to notify the user.
     * @return array It returns an array of default events when an user should be notified.
     * @since 3.1.0
     */
    public function default_events_to_notify_user() {
        return apply_filters('atbdp_default_events_to_notify_user', array(
            'order_created',
            'listing_submitted',
            'payment_received',
            'listing_published',
            'listing_to_expire',
            'listing_expired',
            'remind_to_renew',
            'listing_renewed',
            'order_completed',
            'listing_edited',
            'listing_deleted',
            'listing_contact_form',
        ));
    }

    /**
     * Get an array of events to notify only users
     * @return array it returns an array of events
     * @since 3.1.0
     */
    private function only_user_notifiable_events() {
        return apply_filters('atbdp_only_user_notifiable_events', array(
            array(
                'value' => 'listing_to_expire',
                'label' => __('Listing nearly Expired', 'directorist'),
            ),
            array(
                'value' => 'listing_expired',
                'label' => __('Listing Expired', 'directorist'),
            ),
            array(
                'value' => 'remind_to_renew',
                'label' => __('Remind to renew', 'directorist'),
            ),
        ));
    }

    /**
     * Get the default events to notify the admin.
     * @return array It returns an array of default events when an admin should be notified.
     * @since 3.1.0
     */
    public function default_events_to_notify_admin() {
        return apply_filters('atbdp_default_events_to_notify_admin', array(
            'order_created',
            'order_completed',
            'listing_submitted',
            'payment_received',
            'listing_published',
            'listing_deleted',
            'listing_contact_form',
            'listing_review'
        ));
    }

	public function prepare_settings() {




		$this->set_fields();
		$this->set_layouts();
		$this->set_config();
	}

    public function create_section( $args ) {
    	$this->sections[] = $args;
	}

    public function set_config() {
        $this->config = [
            'fields_theme' => 'butterfly',
            'submission' => [
                'url' => admin_url('admin-ajax.php'),
                'with' => [
                    'action' => 'save_settings_data',
                    'directorist_nonce' => wp_create_nonce( directorist_get_nonce_key() ),
                ],
            ],
        ];
    }

    public function set_layouts() {
        $this->layouts = apply_filters('atbdp_listing_type_settings_layout', [
            'listing_settings' => [
                'label' => __( 'Listings', 'directorist' ),
                'icon' => '<i class="fa fa-list directorist_Blue"></i>',
                'submenu' => apply_filters('atbdp_listing_settings_submenu', [
                    'general' => [
                        'label' => __('General', 'directorist'),
                        'icon' => '<i class="fa fa-sliders-h"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_general_sections', [
                            'general_settings' => [
                                'fields'      => [
                                    'enable_multi_directory',
                                    'font_type', 'can_renew_listing', 'email_to_expire_day', 'email_renewal_day', 'delete_expired_listing', 'delete_expired_listings_after', 'deletion_mode', 'paginate_author_listings', 'display_author_email', 'author_cat_filter', 'guest_listings',
                                ],
                            ],

                        ] ),
                    ],
                    'listings_page' => [
                        'label' => __('All Listings', 'directorist'),
                        'icon' => '<i class="fa fa-archive"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_listings_page_sections', [
                            'labels' => [
                                'fields'      => [
                                    'display_listings_header', 'all_listing_title', 'listing_filters_button', 'listing_filters_icon', 'listings_filter_button_text', 'listing_tags_field', 'listing_default_radius_distance', 'listings_filters_button', 'listings_reset_text', 'listings_apply_text', 'display_sort_by', 'sort_by_text', 'listings_sort_by_items', 'display_view_as', 'view_as_text', 'listings_view_as_items', 'default_listing_view', 'grid_view_as', 'all_listing_columns', 'order_listing_by', 'sort_listing_by', 'preview_image_quality', 'way_to_show_preview', 'crop_width', 'crop_height', 'prv_container_size_by', 'prv_background_type', 'prv_background_color', 'default_preview_image', 'info_display_in_single_line', 'address_location', 'publish_date_format', 'paginate_all_listings', 'all_listing_page_items'
                                ],
                            ],
                        ] ),
                    ],
                    'single_listing' => [
                        'label' => __('Single Listings', 'directorist'),
                        'icon' => '<i class="fa fa-info"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_listing_page_sections', [
                            'labels' => [
                                'fields'      => [
                                    'disable_single_listing', 'restrict_single_listing_for_logged_in_user', 'atbdp_listing_slug', 'single_listing_slug_with_directory_type', 'edit_listing_redirect', 'submission_confirmation', 'pending_confirmation_msg', 'publish_confirmation_msg', 'dsiplay_slider_single_page', 'single_slider_image_size', 'single_slider_background_type', 'single_slider_background_color', 'gallery_crop_width', 'gallery_crop_height', 'address_map_link', 'user_email', 'rel_listings_logic', 'fix_listing_double_thumb'
                                ],
                            ],
                        ] ),
                    ],
                    'categories_locations' => [
                        'label' => __( 'Location & Category', 'directorist' ),
                        'icon' => '<i class="fa fa-list-alt"></i>',
                        'sections' => apply_filters( 'atbdp_categories_settings_sections', [
                            'categories_settings' => [
                                'title'       => __('Categories Page Settings', 'directorist'),
                                'fields'      => [
                                    'display_categories_as', 'categories_column_number', 'categories_depth_number', 'order_category_by', 'sort_category_by', 'display_listing_count', 'hide_empty_categories'
                                 ],
                            ],
                            'locations_settings' => [
                                'title'       => __('Locations Page Settings', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_locations_as', 'locations_column_number', 'locations_depth_number', 'order_location_by', 'sort_location_by', 'display_location_listing_count', 'hide_empty_locations'
                                 ],
                            ],
                        ] ),
                    ],


                    'review' => [
                        'label' => __('Review', 'directorist'),
                        'icon' => '<i class="fa fa-star"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_review_sections', [
                            'labels' => [
                                'fields'      => [
                                    'enable_review', 'enable_owner_review', 'approve_immediately', 'review_approval_text', 'enable_reviewer_img', 'enable_reviewer_content', 'required_reviewer_content', 'review_num', 'guest_review'
                                ],
                            ],
                        ] ),
                    ],

                    'currency_settings' => [
                        'label' => __( 'Listing Currency', 'directorist' ),
                        'icon' => '<i class="fa fa-money-bill"></i>',
                        'sections' => apply_filters( 'atbdp_currency_settings_sections', [
                            'title_metas' => [
                                'fields'      => [
                                    'g_currency_note', 'g_currency', 'g_thousand_separator', 'allow_decimal', 'g_decimal_separator', 'g_currency_position'
                                 ],
                            ],
                        ] ),
                    ],

                    'map' => [
                        'label' => __('Map', 'directorist'),
                        'icon' => '<i class="fa fa-map"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_map_sections', [
                            'map_settings' => [
                                'title'       => __('Map', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'select_listing_map', 'map_api_key', 'country_restriction', 'restricted_countries', 'default_latitude', 'default_longitude', 'use_def_lat_long', 'map_zoom_level', 'map_view_zoom_level', 'listings_map_height'
                                ],
                            ],
                            'map_info_window' => [
                                'title'       => __('Map Info Window Settings', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_map_info', 'display_image_map', 'display_title_map', 'display_address_map', 'display_direction_map'
                                ],
                            ],
                        ] ),
                    ],

                ]),
            ],

            'page_settings' => [
                'label' => __( 'Page Setup', 'directorist' ),
                'icon' => '<i class="fa fa-desktop directorist_wordpress"></i>',
                'sections' => apply_filters( 'atbdp_listing_settings_page_settings_sections', [
                    'upgrade_pages' => [
                        'title'       => __('Upgrade/Regenerate Pages', 'directorist'),
                        'description' => '',
                        'fields'      => [
                            'regenerate_pages'
                         ],
                    ],
                    'pages_links_views' => [
                        'title'       => __('Page, Links & View Settings', 'directorist'),
                        'description' => '',
                        'fields'      => apply_filters( 'atbdp_pages_settings_fields', [
                            'add_listing_page', 'all_listing_page', 'user_dashboard', 'author_profile_page', 'all_categories_page', 'single_category_page', 'all_locations_page', 'single_location_page', 'single_tag_page', 'custom_registration', 'user_login', 'search_listing', 'search_result_page', 'checkout_page', 'payment_receipt_page', 'transaction_failure_page', 'privacy_policy', 'terms_conditions'
                         ] ),
                    ],
                ]),
            ],

            'search_settings' => [
                'label' => __( 'Search', 'directorist' ),
                'icon' => '<i class="fa fa-search directorist_warning"></i>',
                'submenu' => apply_filters('atbdp_email_settings_submenu', [
                    'search_form' => [
                        'label' => __('Search Listing', 'directorist'),
                        'icon' => '<i class="fa fa-search"></i>',
                        'sections' => apply_filters( 'directorist_search_setting_sections', [
                            'search_form' => [
                                'fields'      => [
                                    'search_title', 'search_subtitle', 'search_border', 'search_more_filter', 'search_more_filter_icon', 'search_button', 'search_button_icon', 'home_display_filter', 'search_filters','search_default_radius_distance', 'search_listing_text', 'search_more_filters', 'search_reset_text', 'search_apply_filter', 'show_popular_category', 'popular_cat_title', 'popular_cat_num', 'search_home_bg', 'lazy_load_taxonomy_fields'
                                 ],
                            ],
                        ] ),
                    ],

                    'search_result' => [
                        'label' => __('Search Result', 'directorist'),
                        'icon' => '<i class="fa fa-check"></i>',
                        'sections' => apply_filters( 'atbdp_reg_settings_sections', [
                            'search_result' => [
                                'fields'      => [
                                    'search_header', 'search_result_filters_button_display', 'search_result_filter_button_text', 'search_result_display_filter', 'sresult_default_radius_distance', 'search_result_filters_button', 'sresult_reset_text', 'sresult_apply_text', 'search_view_as', 'search_viewas_text', 'search_view_as_items', 'search_sort_by', 'search_sortby_text', 'search_sort_by_items', 'search_order_listing_by', 'search_sort_listing_by', 'search_listing_columns', 'paginate_search_results', 'search_posts_num', 'radius_search_unit'
                                 ],
                            ],
                        ] ),
                    ],

                ]),
            ],

            'user_settings' => [
                'label' => __( 'User', 'directorist' ),
                'icon' => '<i class="fa fa-users-cog directorist_green"></i>',
                'submenu' => apply_filters('atbdp_user_settings_submenu', [
                    'registration_form' => [
                        'label' => __('Registration Form', 'directorist'),
                        'icon' => '<i class="fa fa-envelope-open"></i>',
                        'sections' => apply_filters( 'atbdp_reg_settings_sections', [
                            'username' => [
                                'title'       => __('Username', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'reg_username'
                                 ],
                            ],
                            'password' => [
                                'title'       => __('Password', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_password_reg', 'reg_password', 'require_password_reg'
                                 ],
                            ],
                            'email' => [
                                'title'       => __('Email', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'reg_email'
                                 ],
                            ],
                            'website' => [
                                'title'       => __('Website', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_website_reg', 'reg_website', 'require_website_reg'
                                 ],
                            ],
                            'first_name' => [
                                'title'       => __('First Name', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_fname_reg', 'reg_fname', 'require_fname_reg'
                                 ],
                            ],
                            'last_name' => [
                                'title'       => __('Last Name', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_lname_reg', 'reg_lname', 'require_lname_reg'
                                 ],
                            ],
                            'about' => [
                                'title'       => __('About/Bio', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_bio_reg', 'reg_bio', 'require_bio_reg'
                                 ],
                            ],
                            'user_type' => [
                                'title'       => __('User Type Registration', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_user_type'
                                 ],
                            ],
                            'privacy_policy' => [
                                'title'       => __('Privacy Policy', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'registration_privacy', 'registration_privacy_label', 'registration_privacy_label_link'
                                 ],
                            ],
                            'terms_condition' => [
                                'title'       => __('Terms Conditions', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'regi_terms_condition', 'regi_terms_label', 'regi_terms_label_link'
                                 ],
                            ],

                            'signup_button' => [
                                'title'       => __('Sign Up Button', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'reg_signup'
                                 ],
                            ],
                            'login_message' => [
                                'title'       => __('Login Message', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_login', 'login_text', 'log_linkingmsg'
                                 ],
                            ],
                            'redirection' => [
                                'title'       => __('', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'auto_login', 'redirection_after_reg'
                                 ],
                            ],
                        ] ),
                    ],
                    'login_form' => [
                        'label' => __('Login Form', 'directorist'),
                        'icon' => '<i class="fa fa-mail-bulk"></i>',
                        'sections' => apply_filters( 'directorist_login_form_templates_settings_sections', [
                            'username' => [
                                'title'       => __('Username', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'log_username'
                                 ],
                            ],
                            'password' => [
                                'title'       => __('Password', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'log_password'
                                 ],
                            ],
                            'remember_login_info' => [
                                'title'       => __('Remember Login Information', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_rememberme', 'log_rememberme'
                                 ],
                            ],
                            'login_button' => [
                                'title'       => __('Login Button', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'log_button'
                                 ],
                            ],
                            'signup_message' => [
                                'title'       => __('Sign Up Message', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_signup', 'reg_text', 'reg_linktxt'
                                 ],
                            ],
                            'recover_password' => [
                                'title'       => __('Recover Password', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'display_recpass', 'recpass_text', 'recpass_desc', 'recpass_username', 'recpass_placeholder', 'recpass_button'
                                 ],
                            ],
                            'login_redirect' => [
                                'title'       => '',
                                'description' => '',
                                'fields'      => [
                                    'redirection_after_login'
                                 ],
                            ],

                        ] ),
                    ],
                    'user_dashboard' => [
                        'label' => __('Dashboard', 'directorist'),
                        'icon' => '<i class="fa fa-chart-bar"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_user_dashboard_sections', [
                            'general_dashboard' => [
                                'fields'      => [
                                     'my_profile_tab', 'my_profile_tab_text', 'fav_listings_tab', 'fav_listings_tab_text', 'announcement_tab', 'announcement_tab_text'
                                ],
                            ],
                            'author_dashboard' => [
                                'title'       => __('Author Dashboard', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'my_listing_tab', 'my_listing_tab_text', 'user_listings_pagination', 'user_listings_per_page', 'submit_listing_button'
                                    ],
                            ],
                            'user_dashboard' => [
                                'title'       => __('User Dashboard', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'become_author_button', 'become_author_button_text'
                                    ],
                            ],
                        ] ),
                    ],
                    'all_authors' => [
                        'label' => __('All Authors', 'directorist'),
                        'icon' => '<i class="fa fa-users"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_user_dashboard_sections', [
                            'all_authors' => [
                                'title'       => __('All Authors', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'all_authors_columns', 'all_authors_sorting', 'all_authors_image', 'all_authors_name', 'all_authors_role', 'all_authors_select_role', 'all_authors_info', 'all_authors_description', 'all_authors_description_limit', 'all_authors_social_info', 'all_authors_button', 'all_authors_button_text', 'all_authors_pagination', 'all_authors_per_page'
                                    ],
                            ],
                        ] ),
                    ],
                ]),
            ],

            'email_settings' => [
                'label' => __( 'Email', 'directorist' ),
                'icon' => '<i class="fa fa-envelope directorist_Blue"></i>',
                'submenu' => apply_filters('atbdp_email_settings_submenu', [
                    'email_general' => [
                        'label' => __('Email General', 'directorist'),
                        'icon' => '<i class="fa fa-envelope-open directorist_info"></i>',
                        'sections' => apply_filters( 'atbdp_reg_settings_sections', [
                            'username' => [
                                'fields'      => [
                                    'disable_email_notification', 'email_from_name', 'email_from_email', 'admin_email_lists', 'notify_admin', 'notify_user'
                                 ],
                            ],
                        ] ),
                    ],
                    'email_templates' => [
                        'label' => __('Email Templates', 'directorist'),
                        'icon' => '<i class="fa fa-mail-bulk directorist_info"></i>',
                        'sections' => apply_filters( 'atbdp_email_templates_settings_sections', [
                            'general' => [
                                'title'       => __('General', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'allow_email_header', 'email_header_color'
                                 ],
                            ],
                            'new_listing' => [
                                'title'       => __('For New Listing', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_note', 'email_sub_new_listing', 'email_tmpl_new_listing'
                                 ],
                            ],
                            'approved_listings' => [
                                'title'       => __('For Approved/Published Listings', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_pub_listing', 'email_tmpl_pub_listing'
                                 ],
                            ],
                            'edited_listings' => [
                                'title'       => __('For Edited Listings', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_edit_listing', 'email_tmpl_edit_listing'
                                 ],
                            ],
                            'about_expire_listings' => [
                                'title'       => __('For About To Expire Listings', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                   'email_sub_to_expire_listing', 'email_tmpl_to_expire_listing'
                                 ],
                            ],
                            'expired_listings' => [
                                'title'       => __('For Expired Listings', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_expired_listing', 'email_tmpl_expired_listing'
                                 ],
                            ],
                            'remind_renewal_listings' => [
                                'title'       => __('For Renewal Listings (Remind To Renew)', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_to_renewal_listing', 'email_tmpl_to_renewal_listing'
                                 ],
                            ],
                            'after_renewed_listings' => [
                                'title'       => __('For Renewed Listings (After Renewed)', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_renewed_listing', 'email_tmpl_renewed_listing'
                                 ],
                            ],
                            'deleted_listings' => [
                                'title'       => __('For Deleted/Trashed Listings', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_deleted_listing', 'email_tmpl_deleted_listing'
                                 ],
                            ],
                            'new_order_created' => [
                                'title'       => __('For New Order (Created)', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_new_order', 'email_tmpl_new_order'
                                 ],
                            ],
                            'new_order_offline_bank' => [
                                'title'       => __('For New Order (Created Using Offline Bank Transfer)', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_offline_new_order', 'email_tmpl_offline_new_order'
                                 ],
                            ],
                            'completed_order' => [
                                'title'       => __('For Completed Order', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_completed_order', 'email_tmpl_completed_order'
                                 ],
                            ],
                            'listing_contact_email' => [
                                'title'       => __('For Listing Contact Email', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_listing_contact_email', 'email_tmpl_listing_contact_email'
                                 ],
                            ],
                            'registration_confirmation' => [
                                'title'       => __('Registration Confirmation', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'email_sub_registration_confirmation', 'email_tmpl_registration_confirmation'
                                 ],
                            ],
                        ] ),
                    ],
                ]),
            ],

            'monetization_settings' => [
                'label' => __( 'Monetization', 'directorist' ),
                'icon' => '<i class="fa fa-credit-card directorist_info"></i>',
                'submenu' => apply_filters('atbdp_monetization_settings_submenu', [
                    'monetization_general' => [
                        'label' => __('General Settings', 'directorist'),
                        'icon' => '<i class="fa fa-home"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_monetization_general_sections', [
                            'general' => [
                                'title'       => __('Monetization Settings', 'directorist'),
                                'description' => '',
                                'fields'      => [ 'enable_monetization' ],
                            ],
                            'plan_promo' => [
                                'title'       => __('Monetize by Listing Plans', 'directorist'),
                                'description' => '',
                                'fields'      => [ 'monetization_promotion' ],
                            ],
                        ] ),
                    ],
                    'featured_listings' => [
                        'label' => __('Featured Listing', 'directorist'),
                        'icon' => '<i class="fa fa-arrow-up"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_featured_sections', [
                            'featured' => [
                                'fields'      => [
                                    'enable_featured_listing',
                                    'featured_listing_title',
                                    'featured_listing_desc',
                                    'featured_listing_price',
                                    'featured_listing_time',
                                ],
                            ],
                        ] ),
                    ],
                    'gateway' => [
                        'label' => __('Gateways Settings', 'directorist'),
                        'icon' => '<i class="fa fa-bezier-curve"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_gateway_sections', [
                            'gateway_general' => [
                                'fields'      => [
                                    'paypal_gateway_promotion',
                                    'gateway_test_mode',
                                    'active_gateways',
                                    'default_gateway',
                                    'payment_currency_note',
                                    'payment_currency',
                                    'payment_thousand_separator',
                                    'payment_decimal_separator',
                                    'payment_currency_position'
                                ],
                            ],
                        ] ),
                    ],
                    'offline_gateway' => [
                        'label' => __('Offline Gateways Settings', 'directorist'),
                        'icon' => '<i class="fa fa-university"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_offline_gateway_sections', [
                            'offline_gateway_general' => [
                                'fields'      => [
                                    'offline_payment_note',
                                    'bank_transfer_title',
                                    'bank_transfer_description',
                                    'bank_transfer_instruction'
                                ],
                            ],
                        ] ),
                    ],
                ]),
            ],

            'style_settings' => [
                'label' => __( 'Personalization', 'directorist' ),
                'icon' => '<i class="fa fa-paint-brush directorist_success"></i>',
                'submenu' => apply_filters('atbdp_style_settings_submenu', [
                    'single_template' => [
                        'label' => __('Single Listing Template', 'directorist'),
                        'icon' => '<i class="fa fa-swatchbook directorist_info"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_single_template_sections', [
                            'general' => [
                                'title'       => '',
                                'description' => '',
                                'fields'      => [
                                    'single_listing_template',
                                    'single_temp_max_width'
                                ],
                            ],
                            'padding' => [
                                'title'       => __('Padding (PX)'),
                                'description' => '',
                                'fields'      => [
                                    'single_temp_padding_top', 'single_temp_padding_bottom', 'single_temp_padding_left', 'single_temp_padding_right'
                                ],
                            ],
                            'margin' => [
                                'title'       => __('Margin (PX)'),
                                'description' => '',
                                'fields'      => [
                                    'single_temp_margin_top', 'single_temp_margin_bottom', 'single_temp_margin_left', 'single_temp_margin_right'
                                ],
                            ],
                        ] ),
                    ],
                    'color_settings' => [
                        'label' => __('Color', 'directorist'),
                        'icon' => '<i class="fa fa-palette directorist_info"></i>',
                        'sections'=> apply_filters('atbdp_style_settings_controls', [
                            'button_type' => [
                                'title' => __('Button Color', 'directorist'),
                                'fields' => [
                                    'button_type', 'primary_example', 'primary_color', 'primary_hover_color', 'back_primary_color', 'back_primary_hover_color', 'border_primary_color', 'border_primary_hover_color', 'secondary_example', 'secondary_color', 'secondary_hover_color', 'back_secondary_color', 'back_secondary_hover_color', 'secondary_border_color', 'secondary_border_hover_color', 'danger_example', 'danger_color', 'danger_hover_color', 'back_danger_color', 'back_danger_hover_color', 'danger_border_color', 'danger_border_hover_color', 'success_example', 'success_color', 'success_hover_color', 'back_success_color', 'back_success_hover_color', 'border_success_color', 'border_success_hover_color', 'lighter_example', 'lighter_color', 'lighter_hover_color', 'back_lighter_color', 'back_lighter_hover_color', 'border_lighter_color', 'border_lighter_hover_color', 'priout_example', 'priout_color', 'priout_hover_color', 'back_priout_color', 'back_priout_hover_color', 'border_priout_color', 'border_priout_hover_color', 'prioutlight_example', 'prioutlight_color', 'prioutlight_hover_color', 'back_prioutlight_color', 'back_prioutlight_hover_color', 'border_prioutlight_color', 'border_prioutlight_hover_color', 'danout_example', 'danout_color', 'danout_hover_color', 'back_danout_color', 'back_danout_hover_color', 'border_danout_color', 'border_danout_hover_color'
                                ]
                            ],

                            'badge_color' => [
                                'title' => __('Badge Color', 'directorist'),
                                'fields' => apply_filters('atbdp_badge_color', [
                                    'open_back_color',
                                    'closed_back_color',
                                    'featured_back_color',
                                    'popular_back_color',
                                    'new_back_color',
                                ])
                            ],

                            'map_marker' => [
                                'title' => __('All Listings Map Marker', 'directorist'),
                                'fields' => apply_filters('atbdp_map_marker_color', [
                                    'marker_shape_color',
                                    'marker_icon_color'
                                ])
                            ],

                            'primary_color' => array(
                                'title' => __('Primary Color', 'directorist'),
                                'fields' => apply_filters('atbdp_primary_dark_color', [
                                    'primary_dark_back_color',
                                    'primary_dark_border_color'
                                ])
                            ),
                        ])
                    ],
                    'badge' => [
                        'label' => __('Badge', 'directorist'),
                        'icon' => '<i class="fa fa-certificate"></i>',
                        'sections' => apply_filters( 'atbdp_listing_settings_badge_sections', [
                            'badge_management' => [
                                'title'       => __('Badge Management', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'new_badge_text', 'new_listing_day'
                                ],
                            ],
                            'popular_badge' => [
                                'title'       => __('Popular Badge', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'popular_badge_text', 'listing_popular_by', 'views_for_popular', 'average_review_for_popular', 'count_loggedin_user'
                                ],
                            ],
                            'featured_badge' => [
                                'title'       => __('Featured Badge', 'directorist'),
                                'description' => '',
                                'fields'      => [
                                    'feature_badge_text'
                                ],
                            ],
                        ] ),
                    ],

                ]),
            ],

            'extension_settings' => [
                'label' => __( 'Extensions', 'directorist' ),
                'icon' => '<i class="fa fa-magic directorist_danger"></i>',
                'submenu' => apply_filters('atbdp_extension_settings_submenu', [
                    'extensions_general' => [
                        'label' => __('Extensions General', 'directorist'),
                        'icon' => '<i class="fa fa-home"></i>',
                        'sections' => apply_filters( 'atbdp_extension_settings_controls', [
                            'general_settings' => [
                                'fields' =>  apply_filters( 'atbdp_extension_fields', [
                                     'extension_promotion'
                                ]) ,
                            ],
                        ] ),
                    ],
                ]),
            ],

            'tools' => [
                'label' => __( 'Tools', 'directorist' ),
                'icon' => '<i class="fa fa-tools directorist_info"></i>',
                'submenu' => apply_filters('atbdp_tools_submenu', [
                    'announcement_settings' => [
                        'label'     => __('Announcement', 'directorist'),
                        'icon' => '<i class="fa fa-bullhorn"></i>',
                        'sections'  => apply_filters('atbdp_announcement_settings_controls', [
                            'send-announcement'     => [
                                'fields'        => [
                                    'announcement',
                                ]
                            ],
                        ]),
                    ],

                    'listings_import' => [
                        'label' => __('Listings Import/Export', 'directorist'),
                        'icon' => '<i class="fa fa-upload"></i>',
                        'sections'  => apply_filters('atbdp_listings_import_controls', [
                            'import_methods' => array(
                                'fields' => apply_filters('atbdp_csv_import_settings_fields', [
                                    'listing_import_button', 'listing_export_button',
                                ]),
                            ),
                        ]),
                    ],

                    'settings_import_export' => [
                        'label' => __( 'Settings Import/Export', 'directorist' ),
                        'icon' => '<i class="fa fa-tools"></i>',
                        'sections'  => apply_filters('atbdp_settings_import_export_controls', [
                            'import_export' => [
                                'title' => __( 'Import/Export', 'directorist' ),
                                'fields' => [ 'import_settings', 'export_settings' ]
                            ],
                            'restore_default' => [
                                'title' => __( 'Restore Default', 'directorist' ),
                                'fields' => [ 'restore_default_settings' ]
                            ],
                        ]),
                    ],

                ]),
            ],

            'advanced' => [
                'label' => __( 'Advanced', 'directorist' ),
                'icon' => '<i class="fa fa-filter directorist_wordpress"></i>',
                'submenu' => apply_filters('atbdp_advanced_submenu', [
                    'seo_settings' => [
                        'label' => __( 'Title & Meta (SEO)', 'directorist' ),
                        'icon' => '<i class="fa fa-bolt"></i>',
                        'sections' => apply_filters( 'atbdp_seo_settings_sections', [
                            'title_metas' => [
                                'fields'      => [
                                    'atbdp_enable_seo', 'add_listing_page_meta_title', 'add_listing_page_meta_desc', 'all_listing_meta_title', 'all_listing_meta_desc', 'dashboard_meta_title', 'dashboard_meta_desc', 'author_profile_meta_title', 'author_page_meta_desc', 'category_meta_title', 'category_meta_desc', 'single_category_meta_title', 'single_category_meta_desc', 'all_locations_meta_title', 'all_locations_meta_desc', 'single_locations_meta_title', 'single_locations_meta_desc', 'registration_meta_title', 'registration_meta_desc', 'login_meta_title', 'login_meta_desc', 'homepage_meta_title', 'homepage_meta_desc', 'meta_title_for_search_result', 'search_result_meta_title', 'search_result_meta_desc'
                                 ],
                            ],
                        ] ),
                    ],

                    'miscellaneous' => [
                        'label'     => __('Miscellaneous', 'directorist'),
                        'icon' => '<i class="fas fa-thumbtack"></i>',
                        'sections'  => apply_filters('atbdp_caching_controls', [
                            'caching' => [
                                'title' => __( 'Caching', 'directorist' ),
                                'fields'      => [
                                    'atbdp_enable_cache', 'atbdp_reset_cache',
                                 ],
                            ],
                            'debugging' => [
                                'title' => __( 'Debugging', 'directorist' ),
                                'fields'      => [
                                    'script_debugging',
                                 ],
                            ],
                            'uninstall' => [
                                'title' => __( 'Uninstall', 'directorist' ),
                                'fields' => [ 'enable_uninstall' ]
                            ],
                        ] ),
                    ],
                ]),
            ],

        ]);
    }

    public function set_fields() {

    }


}