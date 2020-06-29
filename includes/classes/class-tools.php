<?php
defined('ABSPATH') || die('Direct access is not allowed.');
/**
 * @since 4.7.2
 * @package Directorist
 */
if (!class_exists('ATBDP_Tools')) :

    class ATBDP_Tools
    {

        public function __construct()
        {
            add_action('admin_menu', array($this, 'add_tools_submenu'), 10);
            add_action('admin_init', array($this, 'atbdp_csv_import_controller'));
        }


        public function atbdp_csv_import_controller()
        {
            // step one
            
           // var_dump(admin_url());
            if (isset($_POST['atbdp_save_csv_step'])) {
                // redirect to step two || data mapping
                $file = wp_import_handle_upload();
                $file = $file['file'];
                $url = admin_url() . "edit.php?post_type=at_biz_dir&page=tools&step=2";

                $params = array(
                    'step'            => 2,
                    'file'            => str_replace( DIRECTORY_SEPARATOR, '/', $file ),
                    // 'delimiter'       => $this->delimiter,
                    // 'update_existing' => $this->update_existing,
                    // 'map_preferences' => $this->map_preferences,
                    // '_wpnonce'        => wp_create_nonce( 'woocommerce-csv-importer' ), // wp_nonce_url() escapes & to &amp; breaking redirects.
                );


                    wp_safe_redirect( add_query_arg( $params, $url) );
            }



            // Get the data from all those CSVs!
            $posts = $this->csv_get_data();
            // foreach ($posts as $post) {
            //     // If the post exists, skip this post and go to the next one
            //     // if ($post_exists($post["title"])) {
            //     //     continue;
            //     // }
            //     // Insert the post into the database

            //     $this->insert_post($post);

            //     // Update post's custom field with attachment
            //     //update_field( $sitepoint["custom-field"], $post["attachment"]["id"], $post["id"] );

            // }
        }

        private function insert_post($post)
        {
            $post["id"] = wp_insert_post(array(
                "post_title"   => $post["title"],
                "post_content" => $post["content"],
                "post_type"    => 'at_biz_dir',
                "post_status"  => "publish"
            ));

            // Get uploads dir
            $uploads_dir = wp_upload_dir();

            // Set attachment meta
            $attachment = array();
            $attachment["path"] = "{$uploads_dir["baseurl"]}/sitepoint-attachments/{$post["attachment"]}";
            $attachment["file"] = wp_check_filetype($attachment["path"]);
            $attachment["name"] = basename($attachment["path"], ".{$attachment["file"]["ext"]}");

            // Replace post attachment data
            $post["attachment"] = $attachment;

            // Insert attachment into media library
            $post["attachment"]["id"] = wp_insert_attachment(array(
                "guid"           => $post["attachment"]["path"],
                "post_mime_type" => $post["attachment"]["file"]["type"],
                "post_title"     => $post["attachment"]["name"],
                "post_content"   => "",
                "post_status"    => "inherit"
            ));
        }

        private function csv_get_data($single_data = null)
        {
            $data = '';
            $errors = array();
            // Get array of CSV files
            // $files_ = glob(ATBDP_TEMPLATES_DIR . "/import-export/data/*.csv");
            
            $file = isset($_GET['file']) ? wp_unslash($_GET['file']) : array();
           // $files = $_FILES['import'];
                // Attempt to change permissions if not readable
                if (!is_readable($file)) {
                    chmod($file, 0744);
                }
                // Check if file is writable, then open it in 'read only' mode
                if (is_readable($file) && $_file = fopen($file, "r")) {
                    // To sum this part up, all it really does is go row by
                    //  row, column by column, saving all the data
                    $post = array();
                    // Get first row in CSV, which is of course the headers
                    $header = fgetcsv($_file);
                    //return $header;
                    while ($row = fgetcsv($_file)) {
                        foreach ($header as $i => $key) {
                            $post[$key] = $row[$i];
                        }
                        $data = $post;
                    }
                    fclose($_file);
                } else {
                    $errors[] = "File '$file' could not be opened. Check the file's permissions to make sure it's readable by your server.";
                }
            if (!empty($errors)) {
                // ... do stuff with the errors
            }
            return $data;
        }

        private function importable_fields()
        {
            return apply_filters('atbdp_csv_listing_import_mapping_default_columns', array(
                'title'             => __('Title', 'directorist'),
                'description'       => __('Description', 'directorist'),
                'tagline'           => __('Tagline', 'directorist'),
                'price'             => __('Price', 'directorist'),
                'price_range'       => __('Price Range', 'directorist'),
                'view_count'        => __('View Count', 'directorist'),
                'excerpt'           => __('Excerpt', 'directorist'),
                'location'          => __('Location', 'directorist'),
                'tag'               => __('Tag', 'directorist'),
                'category'          => __('Category', 'directorist'),
                'hide_contact_info' => __('Hide Contact Info', 'directorist'),
                'address'           => __('Address', 'directorist'),
                'post_code'         => __('Zip/Post Code', 'directorist'),
                'phone'             => __('Phone', 'directorist'),
                'phone_two'         => __('Phone Two', 'directorist'),
                'fax'               => __('Fax', 'directorist'),
                'email'             => __('Email', 'directorist'),
                'website'           => __('Website', 'directorist'),
                'preview_image'     => __('Preview Image', 'directorist'),
                'video'             => __('Video', 'directorist'),
                'pricing_plan'      => __('Pricing Plan (Requires Pricing Plan Extension)', 'directorist'),
                'is_claimed'        => __('Claimed (Requires Claim Listing Extension)', 'directorist'),
            ));
        }

        /**
         * It adds a submenu for showing all the Tools and details support
         */
        public function add_tools_submenu()
        {
            add_submenu_page('edit.php?post_type=at_biz_dir', __('Tools', 'directorist'), __('Tools', 'directorist'), 'manage_options', 'tools', array($this, 'render_tools_submenu_page'));
        }

        public function render_tools_submenu_page()
        {
            ATBDP()->load_template('tools',  array('data' => $this->csv_get_data(), 'fields' => $this->importable_fields()));
        }
    }

endif;
