<?php
/**
 * @author wpWax
 */

namespace wpWax\Directorist;

defined( 'ABSPATH' ) || die();

Settings::instance()->create_section(
	'general' => [
        'label' => __('General', 'directorist'),
        'icon'  => '<i class="fa fa-sliders-h"></i>',
        'parent' => 'listing_settings',
		'fields' => [
			'enable_multi_directory' => [
                'type'  => 'toggle',
                'label' => 'Enable Multi Directory',
                'value' => false,
                'confirm-before-change' => true,
                'confirmation-modal' => [
                    'show-model-header' => false
                ],
                'data-on-change' => [
                    'action' => 'updateData',
                    'args'   => [ 'reload_after_save' => true ]
                ],
                'componets' => [
                    'link' => [
                        'label' => __( 'Start Building Directory', 'directorist' ),
                        'type'  => 'success',
                        'url'   => admin_url( 'edit.php?post_type=at_biz_dir&page=atbdp-directory-types' ),
                        'show'  => get_directorist_option( 'enable_multi_directory', false ),
                    ]
                ]
			],
            'font_type' => [
                'label' => __('Icon Library', 'directorist'),
                'type'  => 'select',
                'value' => 'line',
                'options' => [
                    [
                        'label' => __('Font Awesome', 'directorist'),
                        'value' => 'font',
                    ],
                    [
                        'label' => __('Line Awesome', 'directorist'),
                        'value' => 'line',
                    ],
                ],
            ],
		],
	],
);