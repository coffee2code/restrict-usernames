<?php

defined( 'ABSPATH' ) or die();

class Restrict_Usernames_Test extends WP_UnitTestCase {

	public static function setUpBeforeClass() {
		c2c_RestrictUsernames::get_instance()->install();
	}

	public function setUp() {
		parent::setUp();
		c2c_RestrictUsernames::get_instance()->reset_options();

		$this->factory->user->create( array( 'user_login' => 'scott' ) );
	}

	public function tearDown() {
		parent::tearDown();

		// Reset options
		c2c_RestrictUsernames::get_instance()->reset_options();

		remove_filter( 'c2c_restrict_usernames-validate', array( $this, 'restrict_username' ), 10, 3 );
	}


	//
	//
	// DATA PROVIDERS
	//
	//


	public static function get_disallowed_usernames() {
		return array(
			array( 'administrator' ),
			array( 'support' ),
		);
	}

	public static function get_disallowed_partial_usernames() {
		return array(
			array( 'admin_test' ),
			array( 'test_admin_test' ),
			array( 'test_admin' ),
			array( 'admintest' ),
			array( 'ADMIN' ),
			array( 'admin test' ),
			array( 'xxx' ),
			array( 'xxxx' ),
		);
	}

	public static function get_required_partial_usernames() {
		return array(
			array( 'team1_bob' ),
			array( 'team1_alice' ),
			array( 'team2_eve' ),
			array( 'team1_ steve' ),
			array( 'sally_team1_' ),
			array( 'adam team2_' ),
			array( 'team1_' ),
			array( 'TEAM1_ polly')
		);
	}

	public static function get_basic_usernames() {
		return array(
			array( 'bob' ),
			array( 'alice' ),
			array( 'Eve' ),
			array( 'joe steve' ),
			array( 'sally' ),
			array( 'good_guy' ),
		);
	}


	//
	//
	// HELPER FUNCTIONS
	//
	//


	private function set_option( $settings = array() ) {
		$defaults = array(
			'disallow_spaces'   => false,
			'usernames'         => array(),
			'partial_usernames' => array(),
			'required_partials' => array(),
			'min_length'        => '',
			'max_length'        => '',
		);
		$settings = wp_parse_args( $settings, $defaults );
		c2c_RestrictUsernames::get_instance()->update_option( $settings, true );
	}

	public function restrict_username( $valid, $username, $options ) {
		return ( 'goodusername' === $username ) ? false : $valid;
	}


	//
	//
	// TESTS
	//
	//


	public function test_class_exists() {
		$this->assertTrue( class_exists( 'c2c_RestrictUsernames' ) );
	}

	public function test_plugin_framework_class_name() {
		$this->assertTrue( class_exists( 'c2c_RestrictUsernames_Plugin_048' ) );
	}

	public function test_plugin_framework_version() {
		$this->assertEquals( '048', c2c_RestrictUsernames::get_instance()->c2c_plugin_version() );
	}

	public function test_version() {
		$this->assertEquals( '3.7', c2c_RestrictUsernames::get_instance()->version() );
	}

	public function test_instance_object_is_returned() {
		$this->assertTrue( is_a( c2c_RestrictUsernames::get_instance(), 'c2c_RestrictUsernames' ) );
	}

	/**
	 * @dataProvider get_disallowed_usernames
	 */
	public function test_explicitly_disallowed_usernames_are_disallowed( $username ) {
		$this->set_option( array( 'usernames' => array( 'administrator', 'support' ) ) );

		$this->assertFalse( validate_username( $username ) );
	}

	public function test_allows_spaces_by_default() {
		$this->assertTrue( validate_username( 'space allowed' ) );
	}

	public function test_setting_disallow_spaces() {
		$this->set_option( array( 'disallow_spaces' => '1' ) );

		$this->assertFalse( validate_username( 'space disallowed' ) );
	}

	public function test_filter_c2c_restrict_usernames_validate() {
		$this->assertTrue( validate_username( 'goodusername' ) );

		add_filter( 'c2c_restrict_usernames-validate', array( $this, 'restrict_username' ), 10, 3 );

		$this->assertFalse( validate_username( 'goodusername' ) );
	}

	/**
	 * @dataProvider get_basic_usernames
	 */
	public function test_accepts_usernames_not_containing_disallowed_partial_usernames( $username ) {
		$this->set_option( array( 'partial_usernames' => array( 'admin', 'xxx' ) ) );

		$this->assertTrue( validate_username( $username ) );
	}

	/**
	 * @dataProvider get_disallowed_partial_usernames
	 */
	public function test_rejects_usernames_containing_disallowed_partial_usernames( $username ) {
		$this->assertTrue( validate_username( $username ) );

		$this->set_option( array( 'partial_usernames' => array( 'admin', 'xxx' ) ) );

		$this->assertFalse( validate_username( $username ) );
	}

	/**
	 * @dataProvider get_disallowed_partial_usernames
	 */
	public function test_rejects_usernames_containing_disallowed_partial_usernames_is_case_insensitive( $username ) {
		$this->assertTrue( validate_username( $username ) );

		$this->set_option( array( 'partial_usernames' => array( 'Admin', 'XXX' ) ) );

		$this->assertFalse( validate_username( $username ) );
	}

	/**
	 * @dataProvider get_required_partial_usernames
	 */
	public function test_accepts_usernames_containing_required_partial_usernames( $username ) {
		$this->set_option( array( 'required_partials' => array( 'team1_', 'team2_' ) ) );

		$this->assertTrue( validate_username( $username ) );
	}

	public function test_requires_usernames_to_start_with_required_starting_partial_username() {
		$this->set_option( array( 'required_partials' => array( '^team1_', '^team2_' ) ) );

		$this->assertTrue( validate_username( 'team1_adam' ) );
		$this->assertTrue( validate_username( 'team1_ Bob' ) );

		$this->assertFalse( validate_username( '_team1_adam' ) );
		$this->assertFalse( validate_username( 'charlie team1_' ) );
	}

	public function test_requires_usernames_to_start_with_required_starting_partial_username_is_case_insensitive() {
		$this->set_option( array( 'required_partials' => array( '^Team1_', '^TEAM2_' ) ) );

		$this->assertTrue( validate_username( 'team1_adam' ) );
		$this->assertTrue( validate_username( 'team1_ Bob' ) );

		$this->assertFalse( validate_username( '_team1_adam' ) );
		$this->assertFalse( validate_username( 'charlie team1_' ) );
	}

	public function test_requires_usernames_to_end_with_required_ending_partial_username() {
		$this->set_option( array( 'required_partials' => array( '_team1^', '_team2^' ) ) );

		$this->assertTrue( validate_username( 'adam_team1' ) );
		$this->assertTrue( validate_username( 'bob _team2' ) );

		$this->assertFalse( validate_username( '_team1_charlie' ) );
		$this->assertFalse( validate_username( 'dave_team1_4' ) );
	}

	public function test_requires_usernames_to_end_with_required_ending_partial_username_is_case_insensitive() {
		$this->set_option( array( 'required_partials' => array( '_Team1^', '_TEAM2^' ) ) );

		$this->assertTrue( validate_username( 'adam_team1' ) );
		$this->assertTrue( validate_username( 'bob _team2' ) );

		$this->assertFalse( validate_username( '_team1_charlie' ) );
		$this->assertFalse( validate_username( 'dave_team1_4' ) );
	}

	/**
	 * @dataProvider get_basic_usernames
	 */
	public function test_rejects_usernames_missing_required_partial_usernames( $username ) {
		$this->assertTrue( validate_username( $username ) );

		$this->set_option( array( 'required_partials' => array( 'team1_', 'team2_' ) ) );

		$this->assertFalse( validate_username( $username ) );
	}

	public function test_no_default_min_length() {
		$this->assertTrue( validate_username( 'u' ) );
		$this->assertTrue( validate_username( 'us' ) );
		$this->assertTrue( validate_username( 'use' ) );
		$this->assertTrue( validate_username( 'user' ) );
	}

	public function test_rejects_names_shorter_than_min_length() {
		$this->set_option( array( 'min_length' => '4' ) );

		$this->assertFalse( validate_username( 'u' ) );
		$this->assertFalse( validate_username( 'us' ) );
		$this->assertFalse( validate_username( 'use' ) );
		$this->assertTrue( validate_username( 'user' ) );
		$this->assertTrue( validate_username( 'users' ) );
	}

	public function test_no_default_max_length() {
		$this->assertTrue( validate_username(
			'abcdefghijklmnopqrstuvxxyz0123456789abcdefghijklmnopqrstuvxxyz0123456789abcdefghijklmnopqrstuvxxyz0123456789abcdefghijklmnopqrstuvxxyz0123456789'
		) );
	}

	public function test_rejects_names_longer_than_max_length() {
		$this->set_option( array( 'max_length' => '6' ) );

		$this->assertTrue( validate_username( 'u' ) );
		$this->assertTrue( validate_username( 'us' ) );
		$this->assertTrue( validate_username( 'use' ) );
		$this->assertTrue( validate_username( 'user' ) );
		$this->assertTrue( validate_username( 'users' ) );
		$this->assertTrue( validate_username( 'users0' ) );
		$this->assertFalse( validate_username( 'users01' ) );
		$this->assertFalse( validate_username( 'users012' ) );
	}

	/*
	  bp_members_validate_user_signup()
	 */

	public function test_bp_members_validate_user_signup_leaves_existing_error() {
		$errors    = new WP_Error();
		$error_msg = 'Usernames can only contain lowercase letters (a-z) and numbers.';
		$errors->add( 'user_name', $error_msg );

		$result = array(
			'user_name'  => 'administrator', // A disallowed username
			'user_email' => 'user@example.com',
			'errors'     => $errors,
		);

		$res = c2c_RestrictUsernames::get_instance()->bp_members_validate_user_signup( $result );

		$this->assertEquals( array( 'user_name' => array( $error_msg ) ), $errors->errors );
	}

	public function test_bp_members_validate_user_signup_proceeds_if_no_existing_error() {
		$errors    = new WP_Error();
		$error_msg = 'Sorry, this username is invalid. Please choose another.';
		$this->set_option( array( 'usernames' => array( 'administrator', 'support' ) ) );

		$result = array(
			'user_name'  => 'administrator', // A disallowed username
			'user_email' => 'user@example.com',
			'errors'     => $errors,
		);

		$res = c2c_RestrictUsernames::get_instance()->bp_members_validate_user_signup( $result );

		$this->assertEquals( array( 'user_name' => array( $error_msg ) ), $errors->errors );
	}

	/*
	 * Setting handling
	 */

	public function test_does_not_immediately_store_default_settings_in_db() {
		$option_name = c2c_RestrictUsernames::SETTING_NAME;
		// Get the options just to see if they may get saved.
		$options     = c2c_RestrictUsernames::get_instance()->get_options();

		$this->assertFalse( get_option( $option_name ) );
	}

	public function test_uninstall_deletes_option() {
		$option_name = c2c_RestrictUsernames::SETTING_NAME;
		$options     = c2c_RestrictUsernames::get_instance()->get_options();

		// Explicitly set an option to ensure options get saved to the database.
		$this->set_option( array( 'min_length' => 4 ) );

		$this->assertNotEmpty( $options );
		$this->assertNotFalse( get_option( $option_name ) );

		c2c_RestrictUsernames::uninstall();

		$this->assertFalse( get_option( $option_name ) );
	}

}
