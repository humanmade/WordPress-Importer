<?php

require_once dirname( __FILE__ ) . '/base.php';

/**
 * @group import
 */
class WXR_Tests_Unknown_WXR_Version_Import extends WXR_Import_UnitTestCase {
	function setUp() {
		parent::setUp();

		if ( ! defined( 'WP_IMPORTING' ) )
			define( 'WP_IMPORTING', true );

		if ( ! defined( 'WP_LOAD_IMPORTERS' ) )
			define( 'WP_LOAD_IMPORTERS', true );

		add_filter( 'import_allow_create_users', '__return_true' );

		global $wpdb;

		// crude but effective: make sure there's no residual data in the main tables
		foreach ( array('posts', 'postmeta', 'comments', 'terms', 'term_taxonomy', 'term_relationships', 'users', 'usermeta') as $table)
			$wpdb->query("DELETE FROM {$wpdb->$table}");
	}

	function tearDown() {
		remove_filter( 'import_allow_create_users', '__return_true' );

		parent::tearDown();
	}

	/**
	 * the small_import test from trunk tests/phpunit/tests/import/import.php
	 */
	function test_unknown_wxr_version_import() {
		global $wpdb;

		$authors = array( 'admin' => false, 'editor' => false, 'author' => false );
		$this->_import_wp( __DIR__ . '/data/export/unknown-wxr-version.xml', $authors );

		$user_count = count_users();
		$this->assertEquals( 0, $user_count['total_users'] );

		foreach ( array( 'post', 'page' ) as $post_type ) {
			$post_count = wp_count_posts( $post_type );
			foreach ( get_object_vars( $post_count ) as $count ) {
				$this->assertEquals( 0, $count );
			}
		}

		foreach ( array( 'category', 'post_tag' ) as $tax ) {
			$terms = get_terms( array( 'taxonomy' => $tax ) );
			$this->assertEquals( 0, count( $terms ) );
		}
	}
}