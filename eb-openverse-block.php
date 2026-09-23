<?php
/**
 * Plugin Name:     EB Openverse Block
 * Description:     Easily search & use royalty free images, stock photos, CC-licensed images, etc from Openverse for your website.
 * Version:         1.2.1
 * Author:          WPDeveloper
 * Author URI:      https://wpdeveloper.net
 * License:         GPL-3.0-or-later
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:     eb-openverse-block
 * Requires at least: 6.0
 * Tested up to:    7.1
 * Requires PHP:    7.4
 *
 * @package         eb-openverse-block
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers all block assets so that they can be enqueued through the block editor
 * in the corresponding context.
 *
 * @see https://developer.wordpress.org/block-editor/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */

require_once __DIR__ . '/includes/font-loader.php';
require_once __DIR__ . '/includes/post-meta.php';
require_once __DIR__ . '/includes/helpers.php';
if ( file_exists( __DIR__ . '/lib/style-handler/style-handler.php' ) ) {
	require_once __DIR__ . '/lib/style-handler/style-handler.php';
}
require_once __DIR__ . '/includes/api-class.php';
require_once __DIR__ . '/includes/class-openverse-ajax.php';
/**
 * Init eb_openverse_block
 *
 * @return void
 */
function create_block_eb_openverse_block_init() {
	if ( ! defined( 'EB_OPENVERSE_BLOCK_VERSION' ) ) {
		define( 'EB_OPENVERSE_BLOCK_VERSION', '1.2.1' );
	}
	if ( ! defined( 'EB_OPENVERSE_BLOCK_ADMIN_URL' ) ) {
		define( 'EB_OPENVERSE_BLOCK_ADMIN_URL', plugin_dir_url( __FILE__ ) );
	}
	if ( ! defined( 'EB_OPENVERSE_BLOCK_ADMIN_PATH' ) ) {
		define( 'EB_OPENVERSE_BLOCK_ADMIN_PATH', dirname( __FILE__ ) );
	}

	$script_asset_path = EB_OPENVERSE_BLOCK_ADMIN_PATH . '/dist/index.asset.php';
	if ( ! file_exists( $script_asset_path ) ) {
		throw new Error(
			'You need to run `npm start` or `npm run build` for the "block/testimonial" block first.'
		);
	}
	$index_js         = EB_OPENVERSE_BLOCK_ADMIN_URL . 'dist/index.js';
	$script_asset     = require $script_asset_path;
	$all_dependencies = array_merge(
        $script_asset['dependencies'],
        array(
			'wp-blocks',
			'wp-i18n',
			'wp-element',
			'wp-block-editor',
			'eb-openverse-block-controls-util',
			'essential-blocks-eb-animation',
        )
    );

	wp_register_script(
		'create-block-eb-openverse-block-editor-script',
		$index_js,
		$all_dependencies,
		$script_asset['version'],
		true
	);

	$load_animation_js = EB_OPENVERSE_BLOCK_ADMIN_URL . 'lib/resources/js/eb-animation-load.js';
	wp_register_script(
		'essential-blocks-eb-animation',
		$load_animation_js,
		array(),
		EB_OPENVERSE_BLOCK_VERSION,
		true
	);

	$fontawesome_css = EB_OPENVERSE_BLOCK_ADMIN_URL . 'lib/resources/css/font-awesome5.css';
	wp_register_style(
		'fontawesome-frontend-css',
		$fontawesome_css,
		array(),
		EB_OPENVERSE_BLOCK_VERSION
	);

	wp_register_style(
		'fontpicker-default-theme',
		EB_OPENVERSE_BLOCK_ADMIN_URL . 'lib/resources/css/fonticonpicker.base-theme.react.css',
		array(),
		EB_OPENVERSE_BLOCK_VERSION,
		'all'
	);

	wp_register_style(
		'fontpicker-material-theme',
		EB_OPENVERSE_BLOCK_ADMIN_URL . 'lib/resources/css/fonticonpicker.material-theme.react.css',
		array(),
		EB_OPENVERSE_BLOCK_VERSION,
		'all'
	);

	$animate_css = EB_OPENVERSE_BLOCK_ADMIN_URL . 'lib/resources/css/animate.min.css';
	wp_register_style(
		'essential-blocks-animation',
		$animate_css,
		array(),
		EB_OPENVERSE_BLOCK_VERSION
	);

	$style_css = EB_OPENVERSE_BLOCK_ADMIN_URL . 'dist/style.css';
	// Editor Style.
	wp_register_style(
		'create-block-eb-openverse-block-editor-style',
		$style_css,
		array(
			'fontawesome-frontend-css',
			'fontpicker-default-theme',
			'fontpicker-material-theme',
			'essential-blocks-animation',
		),
		EB_OPENVERSE_BLOCK_VERSION
	);

	// Frontend Style.
	wp_register_style(
		'create-block-eb-openverse-block-frontend-style',
		$style_css,
		array(
			'fontawesome-frontend-css',
			'essential-blocks-animation',
		),
		EB_OPENVERSE_BLOCK_VERSION
	);

	if ( ! WP_Block_Type_Registry::get_instance()->is_registered( 'essential-blocks/openverse' ) ) {
		register_block_type(
			EB_Openverse_Helper::get_block_register_path( 'eb-openverse-block/eb-openverse-block', EB_OPENVERSE_BLOCK_ADMIN_PATH ),
			array(
				'editor_script'   => 'create-block-eb-openverse-block-editor-script',
				'editor_style'    => 'create-block-eb-openverse-block-editor-style',
				'render_callback' => function ( $attributes, $content ) {
					if ( ! is_admin() ) {
						wp_enqueue_style( 'create-block-eb-openverse-block-frontend-style' );
						wp_enqueue_script( 'essential-blocks-eb-animation' );
					}
					return $content;
				},
			)
		);
	}
}
add_action( 'init', 'create_block_eb_openverse_block_init', 99 );