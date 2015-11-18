<?php

class MC4WP_Integration_Admin {

	/**
	 * @var MC4WP_Integration_Manager
	 */
	protected $integrations;

	/**
	 * @var MC4WP_MailChimp
	 */
	protected $mailchimp;

	/**
	 * @var MC4WP_Admin_Messages
	 */
	protected $messages;

	/**
	 * @param MC4WP_Integration_Manager $integrations
	 * @param MC4WP_MailChimp           $mailchimp
	 * @param MC4WP_Admin_Messages $messages
	 */
	public function __construct( MC4WP_Integration_Manager $integrations, MC4WP_Admin_Messages $messages, MC4WP_MailChimp $mailchimp ) {
		$this->integrations = $integrations;
		$this->mailchimp = $mailchimp;
		$this->messages = $messages;
	}

	/**
	 * Add hooks
	 */
	public function add_hooks() {
		add_action( 'admin_init', array( $this, 'register_setting' ) );
		add_action( 'mc4wp_admin_enqueue_assets', array( $this, 'enqueue_assets' ) );
		add_filter( 'mc4wp_admin_menu_items', array( $this, 'add_menu_item' ) );
	}

	/**
	 * Register settings
	 */
	public function register_setting() {
		register_setting( 'mc4wp_integrations_settings', 'mc4wp_integrations', array( $this, 'save_integration_settings' ) );
	}

	/**
	 * Enqueue assets
	 *
	 * @param $suffix
	 *
	 * @return bool
	 */
	public function enqueue_assets( $suffix ) {

		// only load on integrations pages
		if( ! isset( $_GET['page'] ) || $_GET['page'] !== 'mailchimp-for-wp-integrations' ) {
			return false;
		}

		wp_register_script( 'mc4wp-integrations-admin', MC4WP_PLUGIN_URL . 'assets/js/integrations-admin' . $suffix . '.js', array( 'mc4wp-admin' ), MC4WP_VERSION, true );
		wp_enqueue_script( 'mc4wp-integrations-admin');

		return true;
	}

	/**
	 * @param $items
	 *
	 * @return array
	 */
	public function add_menu_item( $items ) {
		$items['integrations'] = array(
			'title' => __( 'Integrations', 'mailchimp-for-wp' ),
			'text' => __( 'Integrations', 'mailchimp-for-wp' ),
			'slug' => 'integrations',
			'callback' => array( $this, 'show_integrations_page' ),
		);

		return $items;
	}

	/**
	 * @param array $new_settings
	 * @return array
	 */
	public function save_integration_settings( array $new_settings ) {

		$integrations = $this->integrations->get_all();
		$current_settings = (array) get_option( 'mc4wp_integrations', array() );
		$settings = array();

		foreach( $integrations as $slug => $integration ) {
			$settings[ $slug ] = $this->parse_integration_settings( $slug, $current_settings, $new_settings );
		}

		return $settings;
	}

	/**
	 * @internal
	 * @since 3.0
	 * @param $slug
	 * @param $current_settings
	 * @param $new_settings
	 *
	 * @return array
	 */
	protected function parse_integration_settings( $slug, $current_settings, $new_settings ) {
		$settings = array();

		// start with current settings
		if( ! empty( $current_settings[ $slug ] ) ) {
			$settings = $current_settings[ $slug ];
		}

		// then, merge with new settings
		if( ! empty( $new_settings[ $slug ] ) ) {
			// TODO sanitize new settings

			$settings = array_merge( $settings, $new_settings[ $slug ] );
		}

		return $settings;
	}

	/**
	 * Show the Integration Settings page
	 *
	 * @internal
	 */
	public function show_integrations_page() {

		if( ! empty( $_GET['integration'] ) ) {
			$this->show_integration_settings_page( $_GET['integration'] );
			return;
		}

		$integrations = $this->integrations->get_all();

		require dirname( __FILE__ ) . '/views/integrations.php';
	}

	/**
	 * @param string $slug
	 *
	 * @internal
	 */
	public function show_integration_settings_page( $slug ) {

		try {
			$integration = $this->integrations->get( $slug );
		} catch( Exception $e ) {
			echo sprintf( '<h3>Integration not found.</h3><p>No integration with slug <strong>%s</strong> was found.</p>', $slug );
			return;
		}

		$opts = $integration->options;
		$lists = $this->mailchimp->get_lists();

		require dirname( __FILE__ ) . '/views/integration-settings.php';
	}


}