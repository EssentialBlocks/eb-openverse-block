<?php
/**
 * EB_Openverse_Block_Ajax
 *
 * AJAX Event Handler
 *
 * @class    EB_Openverse_Block_Ajax
 * @version  3.6.0
 * @package  admin/frontend
 * @category Class
 * @author   Sumaiya Siddika
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class EB_Openverse_Block_Ajax {

    private static $instance;

    public static function init() {
		if ( null === self::$instance ) {
            self::$instance = new self();
		}
        return self::$instance;
    }

    /**
     * The Constructor.
     */
    public function __construct() {
         self::eb_ajax_action_init();
    }

    private static function isset_check( $value, $default = '' ) {
        if ( isset( $_POST[ $value ] ) ) {
            return $_POST[ $value ];
        } else {
            return $default;
        }
    }

    public static function eb_ajax_action_init() {
         $ajax_events = array(
			 'eb_get_collections'      => 'eb_get_openverse_collections',
			 'eb_get_item'             => 'eb_get_openverse_item',
			 'eb_get_registration'     => 'eb_get_openverse_registration',
			 'eb_openverse_token'      => 'eb_generate_openverse_token',
			 'openverse_email_name_DB' => 'openverse_email_name_DB_callback',
		 );

		 // Logged-in users only: these actions read the stored Openverse
		 // credentials, write site settings and add files to the Media Library.
		 foreach ( $ajax_events as $ajax_event_key => $ajax_event_func ) {
			 add_action( 'wp_ajax_' . $ajax_event_key, array( __CLASS__, $ajax_event_func ) );
		 }
    }

    /**
     * Stop unless the request carries a valid nonce for $action.
     *
     * @param string $field  POST field holding the nonce.
     * @param string $action Nonce action.
     */
    private static function verify_nonce( $field, $action ) {
        if ( ! isset( $_POST[ $field ] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $field ] ) ), $action ) ) {
            wp_send_json_error( __( 'Nonce did not match', 'eb-openverse-block' ), 403 );
        }
    }

    /**
     * Stop unless the current user has $capability.
     *
     * @param string $capability Capability.
     */
    private static function require_capability( $capability ) {
        if ( ! current_user_can( $capability ) ) {
            wp_send_json_error( __( 'You are not allowed to do this.', 'eb-openverse-block' ), 403 );
        }
    }

    /**
     * A search filter value from the request: a single slug or a comma
     * separated list of slugs. Anything else is dropped.
     *
     * @param string $field POST field.
     * @return string
     */
    private static function filter_value( $field ) {
        $value = self::isset_check( $field );
        if ( ! is_string( $value ) ) {
            return '';
        }
        $value = trim( wp_unslash( $value ) );

        return preg_match( '/^[a-z0-9_,-]+$/i', $value ) ? $value : '';
    }

    /**
     * Image types that can be imported into the Media Library.
     *
     * @return array
     */
    private static function allowed_image_mimes() {
        return array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         => 'image/webp',
        );
    }

    /**
     * Openverse Registration
     */
    public static function eb_get_openverse_registration() {
        self::verify_nonce( 'openverse_reg_nonce', 'eb-openverse-reg-nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            // The editor shows `name[0]` from a failed registration.
            wp_send_json(
                array( 'name' => array( __( 'Only an administrator can connect this site to Openverse.', 'eb-openverse-block' ) ) ),
                403
            );
        }

        $name  = sanitize_text_field( self::isset_check( 'openverseName' ) );
        $email = sanitize_email( self::isset_check( 'openverseEmail' ) );

        // Registration for client id and client secret
        $url = 'https://api.openverse.org/v1/auth_tokens/register/';

        $response = EB_Openverse_Blocks_Api::post(
            $url,
            array(
                'name'        => $name,
                // todo: will generate description dynamically later
                'description' => '1234xyzv',
                'email'       => $email,
            ),
            EB_Openverse_Blocks_Api::makeRequestHeader(
                array(
					'Content-Type' => 'application/json',
                )
            ),
            array(
                'timeout' => 20,
            )
        );

        $response_array = is_object( $response ) ? get_object_vars( $response ) : (array) $response;

        if ( isset( $response_array['client_id'] ) && isset( $response_array['client_secret'] ) && isset( $response_array['name'] ) ) {
            self::openverse_reg_data_save(
                array(
					'client_id'     => sanitize_text_field( (string) $response_array['client_id'] ),
					'client_secret' => sanitize_text_field( (string) $response_array['client_secret'] ),
					'name'          => sanitize_text_field( (string) $response_array['name'] ),
					'email'         => sanitize_email( $email ),
                )
            );
        }

        // The client secret is stored server side and never sent to the browser.
        if ( is_array( $response_array ) ) {
            unset( $response_array['client_secret'] );
        }

        wp_send_json( $response_array );
    }

    /**
     * Whether this site is registered with Openverse, plus the name and email
     * the registration form starts from.
     */
    public static function openverse_email_name_DB_callback() {
        self::verify_nonce( 'openverse_nonce', 'eb-openverse-nonce' );
        self::require_capability( 'edit_posts' );

        $settings    = get_option( 'eb_settings' );
        $admin_email = current_user_can( 'manage_options' ) ? get_bloginfo( 'admin_email' ) : '';
        $site_name   = get_bloginfo( 'name' );

		if ( is_array( $settings ) && ! empty( $settings['openverseApi']['client_id'] ) ) {
			// The editor only checks that `client_id` is set; the credentials stay on the server.
			wp_send_json_success(
				array(
					'client_id' => true,
					'name'      => $settings['openverseApi']['name'] ?? '',
					'email'     => current_user_can( 'manage_options' ) ? ( $settings['openverseApi']['email'] ?? '' ) : '',
				)
			);
		} elseif ( ! empty( $admin_email ) || ! empty( $site_name ) ) {
			$site_info = array(
				'email' => $admin_email,
				'name'  => $site_name,
			);
			wp_send_json_success( $site_info );
		} else {
			wp_send_json_error( "Couldn't found data " );
		}
        exit;
    }

    /**
     * Openverse token generate
     */
    private static function eb_generate_openverse_token_callback() {
        // get client idd ... from db
        $settings = get_option( 'eb_settings' );

        if ( ! is_array( $settings ) || ! isset( $settings['openverseApi'] ) ) {
            wp_send_json_error( "Couldn't found data" );
        }

		$client_id     = $settings['openverseApi']['client_id'] ?? '';
		$client_secret = $settings['openverseApi']['client_secret'] ?? '';

        // Registration for client id and client secret
        $url = 'https://api.openverse.org/v1/auth_tokens/token/';

        $response = EB_Openverse_Blocks_Api::post(
            $url,
            array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'grant_type'    => 'client_credentials',
            ),
            EB_Openverse_Blocks_Api::makeRequestHeader(
                array(
					'Content-Type' => 'multipart/form-data',
                )
            ),
            array(
                'timeout' => 20,
            )
        );

        $response_array                  = is_object( $response ) ? get_object_vars( $response ) : (array) $response;
        $access_token                    = $response_array['access_token'] ?? null;
        $access_token_expires_in         = (int) ( $response_array['expires_in'] ?? 0 );
        $deduct_second_from_expire_token = 60;

        $set = set_transient( 'eb_openverse_token', $access_token, $access_token_expires_in - $deduct_second_from_expire_token );

        return $response_array;
    }

    /**
     * Openverse Token
     */
    public static function eb_generate_openverse_token() {
        self::verify_nonce( 'openverse_auth_nonce', 'eb-openverse-auth-nonce' );
        self::require_capability( 'manage_options' );

        $response = self::eb_generate_openverse_token_callback();

        // The token is cached server side; the editor only needs to know it worked.
        unset( $response['access_token'] );
        wp_send_json( $response );
    }

    /**
     * API Call to Get Data
     */
    public static function eb_get_openverse_collections() {
        self::verify_nonce( 'openverse_nonce', 'eb-openverse-nonce' );
        self::require_capability( 'edit_posts' );

        // Fetching the token and check the expire time
        $token = get_transient( 'eb_openverse_token' );

		if ( empty( $token ) ) {
			$token_info = self::eb_generate_openverse_token_callback();
			$token      = $token_info['access_token'] ?? null;
		}

        $limit = 12;

        $url   = 'https://api.openverse.org/v1/images';
        $param = array();

        $values = array(
			'page_size'    => $limit,
            'q'            => sanitize_text_field( self::isset_check( 'openverseQ' ) ),
            'license'      => self::filter_value( 'openverseFilterLicenses' ),
            'categories'   => self::filter_value( 'openverseFilterImgtype' ),
            'size'         => self::filter_value( 'openverseFilterSize' ),
            'extension'    => self::filter_value( 'openverseFilterExtension' ),
            'license_type' => self::filter_value( 'openverseFilterLicensesType' ),
            'page'         => max( 1, absint( self::isset_check( 'openversePage', 1 ) ) ),
        );

        $param = array_merge( $param, $values );

        $response = EB_Openverse_Blocks_Api::get(
            $url,
            $param,
            EB_Openverse_Blocks_Api::makeRequestHeader(
                array(
					'Content-Type' => 'application/json',
					'X-API-KEY'    => $token,
                )
            ),
            array(
                'timeout' => 20,
            )
        );
        return $response;
    }

    /**
     * Upload selected item to media
     */
    public static function eb_get_openverse_item() {
        self::verify_nonce( 'openverse_item_nonce', 'eb-openverse-item-nonce' );
        self::require_capability( 'edit_posts' );

        $file = '';
        if ( isset( $_POST['image_url'] ) ) {
            $file = esc_url_raw( wp_unslash( $_POST['image_url'] ), array( 'http', 'https' ) );
        }
        if ( '' === $file ) {
            wp_die();
        }

        // Without upload_files the block keeps using the remote image.
        if ( ! current_user_can( 'upload_files' ) ) {
            echo esc_url( $file );
            wp_die();
        }

        $filename = basename( (string) wp_parse_url( $file, PHP_URL_PATH ) );

        // Only image files can be imported. A URL without an extension is
        // checked after download, from the file contents.
        if ( '' !== pathinfo( $filename, PATHINFO_EXTENSION ) ) {
            $filetype = wp_check_filetype( $filename, self::allowed_image_mimes() );
            if ( empty( $filetype['type'] ) ) {
                wp_send_json_error( __( 'Only JPEG, PNG, GIF and WebP images can be imported.', 'eb-openverse-block' ), 400 );
            }
        }

        try {
            echo esc_url( wp_get_attachment_url( self::do_upload( $file, $filename ) ) );
        } catch ( \Exception $e ) {
            echo esc_html( 'Upload failed, details: ' . $e->getMessage() );
        }

        wp_die();

    }

    /**
     * Upload to media
     */
    private static function do_upload( $url, $title = null ) {

		if ( ! function_exists( 'download_url' ) || ! function_exists( 'media_handle_sideload' ) ) {
			return false;
		}
        // Download url to a temp file
        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) {
			return false;
        }

        $mimes     = self::allowed_image_mimes();
        $path      = (string) wp_parse_url( $url, PHP_URL_PATH );
        $filename  = pathinfo( $path, PATHINFO_FILENAME );
        $extension = pathinfo( $path, PATHINFO_EXTENSION );

        if ( ! $extension ) {
            $mime = function_exists( 'mime_content_type' ) ? mime_content_type( $tmp ) : false;
            $mime = is_string( $mime ) ? sanitize_mime_type( $mime ) : false;

            $mime_extensions = array(
                // mime_type         => extension (no period)
                'image/jpg'  => 'jpg',
                'image/jpeg' => 'jpeg',
                'image/gif'  => 'gif',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            );

            if ( isset( $mime_extensions[ $mime ] ) ) {
                $extension = $mime_extensions[ $mime ];
            } else {
                @unlink( $tmp );
                return false;
            }
        }
        // The downloaded file must really be one of the allowed image types.
        $checked = wp_check_filetype_and_ext( $tmp, "$filename.$extension", $mimes );
        if ( empty( $checked['type'] ) || ! isset( array_flip( $mimes )[ $checked['type'] ] ) ) {
            @unlink( $tmp );
            return false;
        }
        if ( ! empty( $checked['proper_filename'] ) ) {
            $filename  = pathinfo( $checked['proper_filename'], PATHINFO_FILENAME );
            $extension = pathinfo( $checked['proper_filename'], PATHINFO_EXTENSION );
        }

        $args = array(
            'name'     => "$filename.$extension",
            'tmp_name' => $tmp,
        );

        // Do the upload
        $attachment_id = media_handle_sideload(
            $args,
            0,
            $title,
            array(
                'test_form' => false,
                'mimes'     => $mimes,
            )
        );

        @unlink( $tmp );

        // Error uploading
        if ( is_wp_error( $attachment_id ) ) {
			return false;
        }

        return (int) $attachment_id;
    }

    /**
     * Openverse Reg Data Save to Option Table
     */
    private static function openverse_reg_data_save( $reg_data ) {

        $settings = is_array( get_option( 'eb_settings' ) ) ? get_option( 'eb_settings' ) : array();
        if ( empty( $reg_data ) ) {
            unset( $settings['openverseApi'] );
        } else {
            $settings['openverseApi'] = $reg_data;
        }

        if ( is_array( $settings ) > 0 ) {
            $output = update_option( 'eb_settings', $settings );
            // wp_send_json_success($output);
        } else {
            wp_send_json_error( "Couldn't save data" );
        }
		// exit;
    }
}

EB_Openverse_Block_Ajax::init();