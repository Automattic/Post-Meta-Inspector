<?php
/**
 * Plugin Name: Post Meta Inspector
 * Plugin URI: http://wordpress.org/extend/plugins/post-meta-inspector/
 * Description: Peer inside your post meta. Admins can view post meta for any post from a simple meta box.
 * Author: Daniel Bachhuber, Automattic
 * Version: 1.1.1
 * Author URI: http://automattic.com/
 *
 * @package post-meta-inspector
 */

define( 'POST_META_INSPECTOR_VERSION', '1.1.1' );

if ( ! class_exists( 'Post_Meta_Inspector' ) ) {
	require_once __DIR__ . '/class-post-meta-inspector.php';
}

/**
 * Kick off the post meta class.
 *
 * @return object
 */
function post_meta_inspector() {
	return Post_Meta_Inspector::instance();
}
add_action( 'plugins_loaded', 'post_meta_inspector' );
