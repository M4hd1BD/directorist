<?php

if ( ! class_exists('Directorist_Admin_Dashboard') ) {
	class Directorist_Admin_Dashboard
	{
		private $extension_url    = '';
		public $fields            = [];
		public $layouts           = [];
		public $config            = [];
		public $default_form      = [];
		public $old_custom_fields = [];
		public $cetagory_options  = [];

		// run
		public function run()
		{
            if ( ! is_admin() ) {
                return;
            }

            add_action( 'admin_menu', [$this, 'add_menu_pages'] );

		}

        public static function in_admin_dashboard() {
            if ( ! is_admin() ) {
                return false;
            }

            if ( ! isset( $_REQUEST['post_type'] ) && ! isset( $_REQUEST['page'] ) ) {
                return false;
            }

            if ( 'at_biz_dir' !== $_REQUEST['post_type'] && 'directorist-admin-dashboard' !== $_REQUEST['page'] ) {
                return false;
            }

            return true;
        }



	
        // add_menu_pages
        public function add_menu_pages()
        {
            add_submenu_page(
                'edit.php?post_type=at_biz_dir',
                'Dashboard',
                'Dashboard',
                'manage_options',
                'directorist-admin-dashboard',
                [ $this, 'menu_page_callback__admin_dashboard' ],
                0
            );
        }

        // menu_page_callback__admin_dashboard
        public function menu_page_callback__admin_dashboard()
        {
            
            // Get Saved Data
            $atbdp_options = get_option('atbdp_option');

            foreach( $this->fields as $field_key => $field_opt ) {
                if ( ! isset(  $atbdp_options[ $field_key ] ) ) {
                    $this->fields[ $field_key ]['forceUpdate'] = true;
                    continue;
                }

                $this->fields[ $field_key ]['value'] = $atbdp_options[ $field_key ];
            }

            $dashboard_data = [
                'fields'  => $this->fields,
                'layouts' => $this->layouts,
                'config'  => $this->config,
            ];

            wp_localize_script('directorist-admin-dashboard', 'directorist_dashboard_data', $dashboard_data);


            atbdp_load_admin_template('admin-dashboard/dashboard');
        }
       
    }
}