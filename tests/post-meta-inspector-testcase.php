<?php

/**
 * Base unit test class for Post Meta Inspector
 */
class PostMetaInspector_TestCase extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();

		global $post_meta_inspector;
		$this->_toc = $post_meta_inspector;
	}
}
