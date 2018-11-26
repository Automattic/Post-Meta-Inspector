<?php // phpcs:ignore
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

/**
 * Post Meta Inspector
 */
class Post_Meta_Inspector {


	/**
	 * Post_Meta_Inspector class
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Does user have the cap to view post meta?
	 *
	 * @var bool
	 */
	public $view_cap;

	/**
	 * Kick off the instance.
	 *
	 * @return object
	 */
	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new Post_Meta_Inspector();
			self::setup_actions();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		/** Do nothing */
	}


	/**
	 * Setup on init, add the metaboxes
	 *
	 * @return void
	 */
	private static function setup_actions() {
		add_action( 'init', array( self::$instance, 'action_init' ) );
		add_action( 'add_meta_boxes', array( self::$instance, 'action_add_meta_boxes' ) );
		add_action( 'wp_ajax_update_post_meta_inspector', array( self::$instance, 'render_table' ) );
	}

	/**
	 * Init i18n files
	 */
	public function action_init() {
		load_plugin_textdomain( 'post-meta-inspector', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Add the post meta box to view post meta if the user has permissions to
	 */
	public function action_add_meta_boxes() {

		$this->view_cap = apply_filters( 'pmi_view_cap', 'manage_options' );
		if ( ! current_user_can( $this->view_cap ) || ! apply_filters( 'pmi_show_post_type', '__return_true', get_post_type() ) ) {
			return;
		}

		add_meta_box( 'post-meta-inspector', __( 'Post Meta Inspector', 'post-meta-inspector' ), array( self::$instance, 'post_meta_inspector' ), get_post_type() );
	}

	/**
	 * Output the post meta in metabox.
	 *
	 * @return void
	 */
	public function post_meta_inspector() {
		$post_id = isset( $_GET['post'] ) ? (int) $_GET['post'] : get_the_ID();
		?>
		<style>
			#post-meta-inspector table {
				text-align: left;
				width: 100%;
			}
			#post-meta-inspector table .key-column {
				display: inline-block;
				width: 20%;
			}
			#post-meta-inspector table .value-column {
				display: inline-block;
				width: 79%;
			}
			#post-meta-inspector code {
				word-wrap: break-word;
			}
		</style>
		<?php self::render_table(); ?>
		<script>
			jQuery(document).ready(function() {
				jQuery('.pmi_toggle').click( function(e){
					jQuery('+ code', this).show();
					jQuery(this).hide();
				});

				<?php if ( function_exists( 'is_gutenberg_page' ) && is_gutenberg_page() ) : ?>
				var editPost = wp.data.select( 'core/edit-post' ), lastIsSaving = false;

				wp.data.subscribe( function() {
					var isSaving = editPost.isSavingMetaBoxes();
					if ( isSaving !== lastIsSaving && ! isSaving ) {
						lastIsSaving = isSaving;
						// Gutenberg Post Saving has finished!
						var data = {
							'action': 'update_post_meta_inspector',
							'nonce': <?php echo wp_json_encode( wp_create_nonce( 'update_post_meta_inspector' ) ); ?>,
							'post': <?php echo wp_json_encode( $post_id ); ?>
						};

						jQuery.get( ajaxurl, data, function( response ) {
							jQuery( '#post_meta_inspector' ).html( response );
						} );
					}

					lastIsSaving = isSaving;
				} );
				<?php endif; ?>
			});
		</script>
		<?php
	}

	public function render_table() {
		$post_id       = isset( $_GET['post'] ) ? (int) $_GET['post'] : get_the_ID();
		$custom_fields = get_post_meta( $post_id );
		$toggle_length = apply_filters( 'pmi_toggle_long_value_length', 0 );
		$toggle_length = max( intval($toggle_length), 0);
		$toggle_el_escaped = '<a href="javascript:void(0);" class="pmi_toggle">' . esc_html__( 'Click to show&hellip;', 'post-meta-inspector' ) . '</a>';

		if ( wp_doing_ajax() ) {
			check_ajax_referer( 'update_post_meta_inspector', 'nonce' );
		}
		?>
		<table id="post_meta_inspector">
			<thead>
				<tr>
					<th class="key-column"><?php esc_html_e( 'Key', 'post-meta-inspector' ); ?></th>
					<th class="value-column"><?php esc_html_e( 'Value', 'post-meta-inspector' ); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php foreach( $custom_fields as $key => $values ) :
				if ( apply_filters( 'pmi_ignore_post_meta_key', false, $key ) ) {
					continue;
				}
		?>
			<?php foreach( $values as $value ) : ?>
			<?php
				$value = var_export( $value, true );
				$toggled = $toggle_length && strlen($value) > $toggle_length;
			?>
			<tr>
				<td class="key-column"><?php echo esc_html( $key ); ?></td>
				<td class="value-column"><?php if( $toggled ) echo $toggle_el_escaped; ?><code <?php if( $toggled ) echo ' style="display: none;"'; ?>><?php echo esc_html( $value ); ?></code></td>
			</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		if ( wp_doing_ajax() ) {
			exit;
		}
	}
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
