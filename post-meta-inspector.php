<?php
/**
 * Plugin Name: Post Meta Inspector
 * Plugin URI: http://wordpress.org/extend/plugins/post-meta-inspector/
 * Description: Peer inside your post meta. Admins can view post meta for any post from a simple meta box.
 * Author: Daniel Bachhuber, Automattic
 * Version: 1.1.1
 * Author URI: http://automattic.com/
 */

define( 'POST_META_INSPECTOR_VERSION', '1.1.1' );

class Post_Meta_Inspector
{

	private static $instance;

	public $view_cap;

	public static function instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new Post_Meta_Inspector;
			self::setup_actions();
		}
		return self::$instance;
	}

	private function __construct() {
		/** Do nothing **/
	}

	private static function setup_actions() {

		add_action( 'init', array( self::$instance, 'action_init') );
		add_action( 'add_meta_boxes', array( self::$instance, 'action_add_meta_boxes' ) );
		add_action( 'wp_ajax_pmi_update_post_meta', array( self::$instance, 'action_pmi_update_post_meta' ) );
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
		if ( ! current_user_can( $this->view_cap ) || ! apply_filters( 'pmi_show_post_type', '__return_true', get_post_type() ) )
			return;

		add_meta_box( 'post-meta-inspector', __( 'Post Meta Inspector', 'post-meta-inspector' ), array( self::$instance, 'post_meta_inspector' ), get_post_type() );
	}

	/**
	 * Edit post meta via AJAX
	 */
	public function action_pmi_update_post_meta() {
		extract( $_POST );

		if( isset( $pmi_meta_name ) && isset( $pmi_meta_value ) && isset( $post_id ) ) {
			if( $pmi_meta_name == '' ) return;

			update_post_meta( $post_id, $pmi_meta_name, $pmi_meta_value );

			var_export( $pmi_meta_value );
		}

		exit;
	}

	public function post_meta_inspector() {
		$toggle_length = apply_filters( 'pmi_toggle_long_value_length', 0 );
		$toggle_length = max( intval($toggle_length), 0);
		$toggle_el = '<a href="javascript:void(0);" class="pmi_toggle">' . __( 'Click to show&hellip;', 'post-meta-inspector' ) . '</a>';
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
			#post-meta-inspector input {
				max-width: 100%;
			}
		</style>

		<?php $custom_fields = get_post_meta( get_the_ID() ); ?>
		<table id="pmi-table">
			<thead>
				<tr>
					<th class="key-column"><?php _e( 'Key', 'post-meta-inspector' ); ?></th>
					<th class="value-column"><?php _e( 'Value', 'post-meta-inspector' ); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php foreach( $custom_fields as $key => $values ) :
				if ( apply_filters( 'pmi_ignore_post_meta_key', false, $key ) )
					continue;
		?>
			<?php foreach( $values as $value ) : ?>
			<?php
				$value = var_export( $value, true );
				$toggled = $toggle_length && strlen($value) > $toggle_length;
			?>
			<tr>
				<td class="key-column"><?php echo esc_html( $key ); ?></td>
				<td class="value-column"><?php if( $toggled ) echo $toggle_el; ?><code <?php if( $toggled ) echo ' style="display: none;"'; ?> class="pmi_code"><?php echo esc_html( $value ); ?></code></td>
			</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
				<tr class="pmi-add-row">
					<td class="key-column"><input type="text" name="pmi_meta_name"></td>
					<td class="value-column"><input type="text" name="pmi_meta_value"> <button class="button pmi_submit"><?php _e( 'Add new', 'post-meta-inspector' ) ?></button></td>
				</tr>
			</tbody>
		</table>
		<script>
		jQuery(document).ready(function($) {
			$( '#pmi-table' )
				.on( 'click', '.pmi_toggle', function(e) {
					$( '+ code', this ).show();
					$( this ).hide();
				})
				.on( 'click', '.pmi_submit', function(e) {
					e.preventDefault();

					var post_id	= $( '#post_ID' ).val(),
						$_pmi_meta_name = $( '#pmi-table' ).find( '.pmi-add-row .key-column input' ),
						$_pmi_meta_value = $( '#pmi-table' ).find( '.pmi-add-row .value-column input' );

					$.post( "<?php echo admin_url( 'admin-ajax.php' ) ?>", { 'post_id' : post_id, 'pmi_meta_name' : $_pmi_meta_name.val(), 'pmi_meta_value' : $_pmi_meta_value.val(), 'action' : 'pmi_update_post_meta' }, function(response) {
						if( response == '' ) return;

						$( '#pmi-table .pmi-add-row' ).before( '<tr><td class="key-column">'+ $_pmi_meta_name.val() +'</td><td class="value-column"><code>'+ response +'</code></td></tr>' )

						$_pmi_meta_name.val('');
						$_pmi_meta_value.val('');
					});
				})
				.on( 'click', '.pmi_code', function(e) {
					e.preventDefault();

					var new_value = prompt( "<?php _e( 'Enter new value', 'post-meta-inspector' ) ?>" );

					if( new_value != null ) {
						var post_id	= $( '#post_ID' ).val(),
							$_this = $( this );
							$_edit_meta_name = $( this ).closest( 'tr' ).find( '.key-column' );

						$.post( "<?php echo admin_url( 'admin-ajax.php' ) ?>", { 'post_id' : post_id, 'pmi_meta_name' : $_edit_meta_name.text(), 'pmi_meta_value' : new_value, 'action' : 'pmi_update_post_meta' }, function(response) {
							if( response == '' ) return;

							$_this.html( response );
						});
					}
				});
		});
		</script>
		<?php
	}

}

function Post_Meta_Inspector() {
	return Post_Meta_Inspector::instance();
}
add_action( 'plugins_loaded', 'Post_Meta_Inspector' );