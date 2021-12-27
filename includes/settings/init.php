<?php
/**
 * @author wpWax
 */

namespace wpWax\Directorist\Settings;

defined( 'ABSPATH' ) || die();

class Settings_Panel {

	private $extension_url    = '';
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




}