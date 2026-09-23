<?php
/**
 * Load google fonts.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EB_Openverse_Font_Loader {


    private static $instance;

    /**
     * Registers the plugin.
     */
    public static function register() {
		if ( null === self::$instance ) {
            self::$instance = new self();
		}
        return self::$instance;
    }

    /**
     * The Constructor.
     */
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'fonts_loader' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'fonts_loader' ) );
    }

    /**
     * Load fonts.
     *
     * @access public
     */
    public function fonts_loader() {
         global $post;

        if ( $post && isset( $post->ID ) ) {

            // Legacy source: `_eb_attr` is only written by the old v1 font picker.
            $fonts = get_post_meta( $post->ID, '_eb_attr', true );
            $fonts = ! empty( $fonts ) && is_string( $fonts ) ? explode( ',', $fonts ) : array();

            // The typography control stores the chosen font in `*FontFamily`
            // block attributes and never writes `_eb_attr`, so read them from
            // the saved blocks — otherwise the font is used in the generated
            // CSS but never loaded on the frontend.
            if ( ! empty( $post->post_content ) && function_exists( 'parse_blocks' ) ) {
                $fonts = array_merge( $fonts, self::get_block_font_families( parse_blocks( $post->post_content ) ) );
            }

            if ( ! empty( $fonts ) ) {

                $fonts = array_unique( array_map( 'trim', $fonts ) );

                $system = array(
                    'Arial',
                    'Tahoma',
                    'Verdana',
                    'Helvetica',
                    'Times New Roman',
                    'Trebuchet MS',
                    'Georgia',
                );

                $gfonts = '';

                $gfonts_attr = ':100,100italic,200,200italic,300,300italic,400,400italic,500,500italic,600,600italic,700,700italic,800,800italic,900,900italic';

                foreach ( $fonts as $font ) {
                    if ( ! in_array( $font, $system, true ) && ! empty( $font ) && 'Default' !== $font ) {
                        $gfonts .= str_replace( ' ', '+', trim( $font ) ) . $gfonts_attr . '|';
                    }
                }

                if ( ! empty( $gfonts ) ) {
                    $query_args = array(
                        'family' => $gfonts,
                    );

                    // Own handle: other EB standalone plugins register
                    // 'eb-block-fonts' too, and a second registration of the
                    // same handle is silently ignored, dropping these fonts.
                    wp_register_style(
                        'eb-openverse-block-fonts',
                        add_query_arg( $query_args, '//fonts.googleapis.com/css' ),
                        array()
                    );

                    wp_enqueue_style( 'eb-openverse-block-fonts' );
                }

                // Reset.
                $gfonts = '';
            }
        }
    }

    /**
     * Collect `*FontFamily` attribute values from this plugin's blocks.
     *
     * @param array $blocks Parsed blocks.
     * @return array Font family names.
     */
    private static function get_block_font_families( $blocks ) {
        $fonts = array();

        foreach ( $blocks as $block ) {
            if ( isset( $block['blockName'] ) && 'eb-openverse-block/eb-openverse-block' === $block['blockName'] && ! empty( $block['attrs'] ) ) {
                foreach ( $block['attrs'] as $key => $value ) {
                    if ( is_string( $value ) && '' !== $value && 'FontFamily' === substr( $key, -10 ) ) {
                        $fonts[] = $value;
                    }
                }
            }

            if ( ! empty( $block['innerBlocks'] ) ) {
                $fonts = array_merge( $fonts, self::get_block_font_families( $block['innerBlocks'] ) );
            }
        }

        return $fonts;
    }
}
EB_Openverse_Font_Loader::register();
