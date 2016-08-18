<?php

class BaseTest extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'JPry_Post_Metabox') );
	}
	
	function test_get_instance() {
		$this->assertTrue( jpry_post_metabox() instanceof JPry_Post_Metabox );
	}
}
