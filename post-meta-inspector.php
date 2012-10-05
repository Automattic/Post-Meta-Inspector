<?php
/**
 * Plugin Name: Post Meta Inspector
 * Plugin URI: http://wordpress.org/extend/plugins/post-meta-inspector/
 * Description: Peer inside your post meta. Admins can view post meta for any post from a simple meta box.
 * Author: Daniel Bachhuber, Automattic
 * Version: 0.0
 * Author URI: http://automattic.com/
 */

define( 'POST_META_INSPECTOR_VERSION', '0.0' );

class Post_Meta_Inspector
{

	function __construct() {

		add_action( 'add_meta_boxes', array( $this, 'action_add_meta_boxes' ) );
	}

	/**
	 * Add the post meta box to view post meta if the user has permissions to
	 */
	function action_add_meta_boxes() {

		$this->view_cap = apply_filters( 'pmi_view_cap', 'manage_options' );
		if ( ! current_user_can( $this->view_cap ) || ! apply_filters( 'pmi_show_post_type', '__return_true', get_post_type() ) )
			return;

		add_meta_box( 'post-meta-inspector', __( 'Post Meta Inspector', 'post-meta-inspector' ), array( $this, 'post_meta_inspector' ), get_post_type() );
	}

	function post_meta_inspector() {

		?>
		<style>
			#post-meta-inspector table {
				text-align: left
			}
			#post-meta-inspector table .key-column {
				min-width: 200px;
			}
		</style>

		<?php $custom_fields = get_post_meta( get_the_ID() ); ?>
		<table>
			<thead>
				<tr>
					<th class="key-column"><?php _e( 'Key', 'post-meta-inspector' ); ?></th>
					<th class="value-column"><?php _e( 'Value', 'post-meta-inspector' ); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php foreach( $custom_fields as $key => $values ) : ?>
			<?php foreach( $values as $value ) : ?>
			<tr>
				<td class="key-column"><?php echo esc_html( $key ); ?></td>
				<td class="value-column"><code><?php echo esc_html( var_export( $value, true ) ); ?></code></td>
			</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
			</tbody>
		</table>

		<?php
	}

}

global $post_meta_inspector;
$post_meta_inspector = new Post_Meta_Inspector;