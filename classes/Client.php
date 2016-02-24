<?php
/**
 * AudioTheme.com REST API client.
 *
 * @package   AudioTheme\Agent
 * @copyright Copyright (c) 2016, AudioTheme, LLC
 * @license   GPL-2.0+
 * @since     1.0.0
 */

/**
 * AudioTheme.com REST API client class.
 *
 * @package AudioTheme\Agent
 * @since   1.0.0
 */
class AudioTheme_Agent_Client {
	/**
	 * Base API URL.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $base_url = 'https://audiotheme.com/api';

	/**
	 * Authorization endpoint.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $authorization_endpoint = 'https://audiotheme.com/oauth2/authorize';

	/**
	 * Registration endpoint.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $registration_endpoint = 'https://audiotheme.com/oauth2/register';

	/**
	 * Subscription endpoint.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $subscription_endpoint = 'https://audiotheme.com/oauth2/subscribe';

	/**
	 * Token endpoint.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $token_endpoint = 'https://audiotheme.com/oauth2/token';

	/**
	 * Client identifier.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $client_id;

	/**
	 * Client secret.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	protected $client_secret;

	/**
	 * Instantiate a client.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( $this->is_registered() ) {
			$this->set_client_id( $this->get_registered_metadata( 'client_id' ) );
			$this->set_client_secret( $this->get_registered_metadata( 'client_secret' ) );
		}
	}

	/**
	 * Retrieve a package by its slug.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $slug Package slug.
	 * @param  array  $args Endpoint query args.
	 * @return object
	 */
	public function get_package( $slug, $args = array() ) {
		return $this->get( '/v1/packages/' . $slug, $args );
	}

	/**
	 * Retrieve a list of packages.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $slugs Package slugs.
	 * @return array
	 */
	public function get_packages( $slugs = array() ) {
		return $this->get( '/v1/packages', array(
			'slugs' => $slugs,
		) );
	}

	/**
	 * Retrieve subscriptions associated with the registered client and authorized user.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_subscriptions() {
		return $this->get( '/v1/subscriptions/me' );
	}

	/**
	 * Perform a remote GET request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $path      Path to the resource.
	 * @param  array  $args      Endpoint arguments.
	 * @param  array  $http_args WP HTTP API arguments.
	 * @return mixed
	 */
	public function get( $path = '', $args = array(), $http_args = array() ) {
		$url = $this->get_url( $path );
		$response = $this->request( add_query_arg( $args, $url ), $http_args );
		return $this->parse_response( $response );
	}

	/**
	 * Perform a remote POST request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $path Path to the resource.
	 * @param  array  $args WP HTTP API arguments.
	 * @return mixed
	 */
	public function post( $path = '', $args = array() ) {
		$response = $this->request( $this->get_url( $path ), $args, 'POST' );
		return $this->parse_response( $response );
	}

	/**
	 * Perform a remote request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url     URL.
	 * @param  array  $args    WP HTTP API args.
	 * @param  string $method  Optional. HTTP method.
	 * @param  bool   $refresh Optional. Whether to try to refresh the access token. Defaults to true.
	 * @return mixed
	 */
	public function request( $url, $args = array(), $method = 'GET', $refresh = true ) {
		if ( ! $this->is_authorized() ) {
			#return new WP_Error( 'unauthorized_client', __( 'The access token is missing. Try authorizing the client first.' ) );
		}

		$args = wp_parse_args( $args, array(
			'headers' => array(),
			'method'  => $method,
		) );

		if ( $this->is_authorized() ) {
			$token = $this->get_access_token();

			// Refresh the access token if it has expired.
			if ( ! empty( $token ) && time() > (int) $this->get_grant_value( 'expires_at' ) ) {
				$token = $this->refresh_access_token();
			}

			if ( is_wp_error( $token ) ) {
				$this->disconnect();
				return $token;
			}

			// Add a Bearer token in the Authorization header.
			if ( 'bearer' === $this->get_grant_value( 'token_type' ) ) {
				$args['headers'] = wp_parse_args( $args['headers'], array(
					'Authorization' => 'Bearer ' . $token,
				) );
			}

			// https://tools.ietf.org/html/rfc7523#section-2.2
			// 'client_assertion_type' => 'urn:ietf:params:oauth:client-assertion-type:jwt-bearer',
			// 'client_assertion'      => '',
		}

		$response = wp_remote_request( $url, $args );
		$status   = wp_remote_retrieve_response_code( $response );

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( $refresh && 401 === $status ) {
			// Try to refresh the token if there's an 401 error.
			$token = $this->refresh_access_token();

			if ( is_wp_error( $token ) ) {
				$this->disconnect();
				return $token;
			}

			$args['headers']['Authorization'] = 'Bearer ' . $token;
		 	return $this->request( $url, $args, $method, false );
		} elseif ( ! $refresh && ( 400 === $status || 401 === $status ) ) {
			// @todo If the refresh token is expired or the token has been revoked, disconnect?
			$this->disconnect();
		}

		return $response;
	}

	/**
	 * Whether the client is authorized.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_authorized() {
		$token = $this->get_access_token();
		return ! empty( $token );
	}

	/**
	 * Authorize the client.
	 *
	 * @since 1.0.0
	 *
	 * @param  int    $user_id User ID.
	 * @param  string $code    Authorization code.
	 * @return object
	 */
	protected function authorize( $user_id, $code ) {
		$response = wp_remote_post( $this->get_token_endpoint(), array(
			'body' => array(
				'grant_type'   => 'authorization_code',
				'code'         => $code,
				'redirect_uri' => $this->get_redirect_uri(),

				// https://tools.ietf.org/html/rfc7521#section-4.1
				// 'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
				// 'assertion'  => '',
			),
			'headers' => array(
				'Authorization' => sprintf(
					'Basic %s',
					base64_encode( $this->get_client_id() . ':' . $this->get_client_secret() )
				),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
		) );

		return $this->parse_response( $response );
	}

	/**
	 * Deauthorize the client.
	 *
	 * @since 1.0.0
	 *
	 * @return $this
	 */
	public function disconnect() {
		// @todo Revoke the token remotely.
		delete_option( 'audiotheme_agent_token' );
		return $this;
	}

	/**
	 * Retrieve the client identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_client_id() {
		return $this->client_id;
	}

	/**
	 * Set the client identifier.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $client_id Client identifier.
	 * @return $this
	 */
	public function set_client_id( $client_id ) {
		$this->client_id = $client_id;
		return $this;
	}

	/**
	 * Retrieve the client secret.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_client_secret() {
		return $this->client_secret;
	}

	/**
	 * Set the client secret.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $client_secret Client secret.
	 * @return $this
	 */
	public function set_client_secret( $client_secret ) {
		$this->client_secret = $client_secret;
		return $this;
	}

	/**
	 * Retrieve client registration metadata.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_client_metadata() {
		return array(
			'redirect_uris'              => array(
				$this->get_redirect_uri(),
			),
			'token_endpoint_auth_method' => 'client_secret_basic',
			'grant_types'                => array(
				'authorization_code',
				'refresh_token',
				'urn:ietf:params:oauth:grant-type:jwt-bearer',
			),
			'response_types'             => array(
				'code',
			),
			'client_name'                => esc_html( get_bloginfo( 'name' ) ),
			'client_uri'                 => esc_url_raw( home_url() ),
			'logo_uri'                   => esc_url_raw( get_site_icon_url() ),
			'scope'                      => 'read',
			'contacts'                   => array(
				'support@audiotheme.com',
			),
			'tos_uri'                    => '',
			'policy_uri'                 => '',
			'software_id'                => 'e3c8b4b3-3e0b-4c75-af42-0cd70202f391',
			'software_version'           => '1.0.0',
		);
	}

	/**
	 * Retrieve registered metadata.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key Optional. Key for a specific value.
	 * @return array
	 */
	public function get_registered_metadata( $key = null ) {
		$metadata = (array) get_option( 'audiotheme_agent_client', array() );

		$value = null;
		if ( ! empty( $key ) && isset( $metadata[ $key ] ) ) {
			$value = $metadata[ $key ];
		} elseif ( empty( $key ) ) {
			$value = $metadata;
		}

		return $value;
	}

	/**
	 * Retrieve the authorization endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_authorization_endpoint() {
		return $this->authorization_endpoint;
	}

	/**
	 * Set the authorization endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Authorization endpoint URL.
	 * @return $this
	 */
	public function set_authorization_endpoint( $url ) {
		$this->authorization_endpoint = $url;
		return $this;
	}

	/**
	 * Retrieve the registration endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_registration_endpoint() {
		return $this->registration_endpoint;
	}

	/**
	 * Set the registration endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Registration endpoint URL.
	 * @return $this
	 */
	public function set_registration_endpoint( $url ) {
		$this->registration_endpoint = $url;
		return $this;
	}

	/**
	 * Retrieve the subscription endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_subscription_endpoint() {
		return $this->subscription_endpoint;
	}

	/**
	 * Set the subscription endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Subscription endpoint URL.
	 * @return $this
	 */
	public function set_subscription_endpoint( $url ) {
		$this->subscription_endpoint = $url;
		return $this;
	}

	/**
	 * Retrieve the token endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_token_endpoint() {
		return $this->token_endpoint;
	}

	/**
	 * Set the token endpoint URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url Token endpoint URL.
	 * @return $this
	 */
	public function set_token_endpoint( $url ) {
		$this->token_endpoint = $url;
		return $this;
	}

	/**
	 * Retrieve the application URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $args Optional. Query arguments to add to the application URL.
	 * @return string
	 */
	public function get_application_url( $args = array() ) {
		$args = array_merge( $args, array( 'page' => 'audiotheme-agent' ) );
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Retrieve the authorization URL with required query arguments.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_authorization_url() {
		return add_query_arg(
			array(
				'response_type' => 'code',
				'client_id'     => $this->get_client_id(),
				'redirect_uri'  => $this->get_redirect_uri(),
				'scope'         => '',
				'state'         => wp_create_nonce( 'authorize-client_' . get_current_user_id() ),
			),
			$this->get_authorization_endpoint()
		);
	}

	/**
	 * Retrieve the OAuth 2.0 redirect URI.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_redirect_uri() {
		return add_query_arg(
			'action',
			'authorize-audiotheme-agent',
			admin_url( 'admin-post.php' )
		);
	}

	/**
	 * Set the base URL for the REST API.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url URL.
	 * @return $this
	 */
	public function set_base_url( $url ) {
		$this->base_url = rtrim( $url, '/' );
		return $this;
	}

	/**
	 * Retrieve the full URL for a resource path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $path Resource path.
	 * @return string
	 */
	public function get_url( $path = '' ) {
		return $this->base_url . '/' . ltrim( $path, '/' );
	}

	/**
	 * Whether the client is registered with the REST API.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_registered() {
		$data = $this->get_registered_metadata();
		return ! empty( $data['client_id'] ) && ! empty( $data['client_secret'] );
	}

	/**
	 * Register the client.
	 *
	 * @since 1.0.0
	 *
	 * @param  string         $token Initial access token.
	 * @param  array          $args  Registration metadata.
	 * @return WP_Error|$this
	 */
	public function register( $token, $args = array() ) {
		$metadata = wp_parse_args( $args, $this->get_client_metadata() );

		$response = wp_remote_post( $this->get_registration_endpoint(), array(
			'body'    => wp_json_encode( $metadata ),
			'headers' => array(
				'Authorization' => sprintf( 'Bearer %s', $token ),
				'Content-Type'  => 'application/json',
			),
		) );

		$data = $this->parse_response( $response, 201 );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		/*
		 * Save the registration data.
		 *
		 * Should include the following plus any saved client metadata:
		 * - client_id
		 * - client_secret
		 * - client_id_issued_at
		 * - client_secret_expires_at
		 */
		update_option( 'audiotheme_agent_client', (array) $data );

		return $this;
	}

	/**
	 * Connect the client to a subscription.
	 *
	 * Combines the registration and authorization grant steps.
	 *
	 * @since 1.0.0
	 *
	 * @param  string         $token Initial access token.
	 * @param  array          $args  Registration metadata.
	 * @return WP_Error|$this
	 */
	public function subscribe( $token, $args = array() ) {
		$metadata = wp_parse_args( $args, $this->get_client_metadata() );

		if ( $this->is_registered() ) {
			$metadata['client_id'] = $this->get_client_id();
			$metadata['client_secret'] = $this->get_client_secret();
		}

		$response = wp_remote_post( $this->get_subscription_endpoint(), array(
			'body'    => wp_json_encode( $metadata ),
			'headers' => array(
				'Authorization' => sprintf( 'Bearer %s', $token ),
				'Content-Type'  => 'application/json',
			),
		) );

		$data = $this->parse_response( $response, array( 200, 201 ) );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		if ( ! empty( $data->register ) ) {
			/*
			 * Save the registration data.
			 *
			 * Should include the following plus any saved client metadata:
			 * - client_id
			 * - client_secret
			 * - client_id_issued_at
			 * - client_secret_expires_at
			 */
			update_option( 'audiotheme_agent_client', (array) $data->register );

			$this->set_client_id( $data->register->client_id );
			$this->set_client_secret( $data->register->client_secret );
		}

		if ( ! empty( $data->authorize ) ) {
			$token = $this->save_token( $data->authorize );
		}

		return $this;
	}

	/**
	 * Retrieve the access token.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	protected function get_access_token() {
		return $this->get_grant_value( 'access_token' );
	}

	/**
	 * Retrieve a value from the grant response.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key Optional. Key of the value to retrieve. Defaults to returning the access token. Keys: access_token, token_type, expires_in, refresh_token
	 * @return mixed
	 */
	protected function get_grant_value( $key = 'access_token' ) {
		$token = get_option( 'audiotheme_agent_token' );

		$value = '';
		if ( isset( $token[ $key ] ) ) {
			$value = $token[ $key ];
		}

		return $value;
	}

	/**
	 * Exchange the refresh token for another access token.
	 *
	 * @since 1.0.0
	 *
	 * @return object|WP_Error Token object or an error.
	 */
	protected function refresh_access_token() {
		$response = wp_remote_post( $this->get_token_endpoint(), array(
			'body' => array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->get_grant_value( 'refresh_token' ),
			),
			'headers' => array(
				'Authorization' => sprintf(
					'Basic %s',
					base64_encode( $this->get_client_id() . ':' . $this->get_client_secret() )
				),
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
		) );

		$token = $this->save_token( $this->parse_response( $response ) );

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		return $token->access_token;
	}

	/**
	 * Save the authorization grant response.
	 *
	 * @since 1.0.0
	 *
	 * @param  object $token Authorization grant response.
	 * @return object
	 */
	public function save_token( $token ) {
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		if ( empty( $token->access_token ) ) {
			return new WP_Error( 'missing_token', __( 'Missing access token.', 'audiotheme-agent' ) );
		}

		if ( empty( $token->refresh_token ) ) {
			return new WP_Error( 'missing_token', __( 'Missing refresh token.', 'audiotheme-agent' ) );
		}

		// Add the expiration time.
		if ( isset( $token->expires_in ) ) {
			$token->expires_at = time() + (int) $token->expires_in;
		}

		if ( false === get_option( 'audiotheme_agent_token', false ) ) {
			add_option( 'audiotheme_agent_token', (array) $token, '', 'no' );
		} else {
			update_option( 'audiotheme_agent_token', (array) $token );
		}

		return $token;
	}

	/**
	 * Parse an HTTP response.
	 *
	 * @since 1.0.0
	 *
	 * @param  string    $response        HTTP response.
	 * @param  int|array $expected_status Optional. Expected status code. Defaults to 200.
	 * @return mixed
	 */
	public function parse_response( $response, $expected_status = 200 ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$content      = wp_remote_retrieve_body( $response );
		$status       = wp_remote_retrieve_response_code( $response );
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );

		if ( is_wp_error( $content ) ) {
			return $content;
		}

		#if ( 'application/json' === $content_type ) {
			// @todo Consider a strict mode to return JSON decoding errors.
			$content = $this->maybe_decode_json( $content );
		#}

		if ( isset( $content->error ) && isset( $content->error_description ) ) {
			$data = isset( $content->data ) ? (array) $content->data : '';
			return new WP_Error( $content->error, $content->error_description, $data );
		}

		if ( ! is_array( $expected_status ) ) {
			$expected_status = array( $expected_status );
		}

		if ( ! in_array( $status, $expected_status ) ) {
			return new WP_Error( 'unexpected_status_code', __( 'An unexpected status code was returned by the remote server.', 'audiotheme-agent' ), array(
				'body'   => $content,
				'status' => $status,
			) );
		}

		return $content;
	}

	/**
	 * Maybe decode a JSON string.
	 *
	 * @since 1.0.0
	 *
	 * @param  string        $value JSON string.
	 * @return object|string
	 */
	protected function maybe_decode_json( $value ) {
		if ( ! is_string( $value ) ) {
			return $value;
		}

		$json = json_decode( $value );

		if ( null === $json ) {
			return $value;
		}

		return $json;
	}

	/**
	 * Handle redirects from the Authorization Code grant.
	 *
	 * @since 1.0.0
	 */
	public function handle_callback() {
		$user_id = get_current_user_id();

		// Bail if this isn't an authorization callback request.
		if ( ! isset( $_GET['action'] ) || 'authorize-audiotheme-agent' !== $_GET['action'] ) {
			return;
		}

		// Verify the nonce to ensure the user intended to take this action.
		if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( $_GET['state'], 'authorize-client_' . $user_id ) ) {
			$this->report_error( new WP_Error( 'invalid_state', esc_html__( 'Are you sure you want authorize this connection? Please try again.', 'audiotheme-agent' ) ) );
		}

		// Check to see if the user denied authorization.
		if ( isset( $_GET['error'] ) && 'access_denied' === $_GET['error'] ) {
			$this->report_error( new WP_Error( 'access_denied', esc_html__( 'Please authorize the connection before continuing.', 'audiotheme-agent' ) ) );
		}

		// Handle errors returned by the authorization server.
		if ( ! empty( $_GET['error'] ) ) {
			$error_code    = sanitize_text_field( $_GET['error'] );
			$error_message = esc_html__( 'The authorization server returned an error.', 'audiotheme-agent' );
			$error_data    = array();

			if ( ! empty( $_GET['error_description'] ) ) {
				$error_message = wp_kses( $_GET['error_description'] );
			}

			if ( ! empty( $_GET['error_uri'] ) ) {
				$error_data['error_uri'] = esc_url_raw( $_GEt['error_uri'] );
			}

			$this->report_error( new WP_Error( $error_code, $error_message, $error_data ) );
		}

		if ( empty( $_GET['code'] ) ) {
			$this->report_error( new WP_Error( 'invalid_code', esc_html__( 'A valid authorization code was not provided by the authroization server.', 'audiotheme-agent' ) ) );
		}

		$code  = sanitize_text_field( $_GET['code'] );
		$token = $this->authorize( $user_id, $code );
		$token = $this->save_token( $token );

		if ( is_wp_error( $token ) ) {
			$this->report_error( $token );
		}

		wp_safe_redirect( $this->get_application_url() );
		exit;
	}

	/**
	 * Redirect to the application URL with errors returned in the Authorization
	 * Code grant.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_Error $error Error.
	 */
	protected function report_error( WP_Error $error ) {
		$url = $this->get_application_url( array(
			'error'             => $error->get_error_code(),
			'error_description' => urlencode( $error->get_error_message() ),
			'error_uri'         => urlencode( $error->get_error_data( 'error_uri' ) ),
		) );

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Retrieve an error message from the query string.
	 *
	 * @since 1.0.0
	 *
	 * @return string|false
	 */
	public function get_error() {
		if ( isset( $_GET['error_description'] ) ) {
			return wp_kses( $_GET['error_description'], array() );
		}

		return false;
	}
}
