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


/**
 * Ignore the _crdt_document meta key due it's size and unhelpfulness to the user.
 *
 * The value of the _crdt_document is a very long string that breaks the UI of the post meta inspector when output. It
 * is added in WordPress 7.0 to track the document state of collaborative editing.
 *
 * @see https://github.com/WordPress/wordpress-develop/blob/7.0/src/wp-includes/post.php#L8679
 *
 * @param bool   $ignore Whether to ignore the meta key.
 * @param string $meta_key The meta key being evaluated.
 * 
 * @return bool Whether to ignore the meta key.
 */
function pmi_ignore_crdt_document( $ignore, $meta_key ) {
	if ( $meta_key === '_crdt_document' ) {
		return true;
	}

	return $ignore;
}
add_filter( 'pmi_ignore_post_meta_key', 'pmi_ignore_crdt_document', 10, 2 );

