<?php

class BaseTest extends WP_UnitTestCase {

	function test_sample() {
		// replace this with some actual testing code
		$this->assertTrue( true );
	}

	function test_class_exists() {
		$this->assertTrue( class_exists( 'JPry_Translation_Propagation') );
	}
	
	function test_get_instance() {
		$this->assertTrue( jpry_translation_propagation() instanceof JPry_Translation_Propagation );
	}
}
