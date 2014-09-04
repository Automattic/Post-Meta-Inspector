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

	public $ver = '1.1.1';

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
		add_action( 'admin_enqueue_scripts', array( self::$instance, 'enqueue_admin_stylescripts' ) );
		add_action( 'add_meta_boxes', array( self::$instance, 'action_add_meta_boxes' ) );
		add_action( 'wp_ajax_inspect_meta_modal', array( self::$instance, 'inspect_modal' ) );

		add_filter( 'post_row_actions', array( self::$instance, 'meta_view_row_action' ), 10, 2 );
		add_filter( 'page_row_actions', array( self::$instance, 'meta_view_row_action' ), 10, 2 );
	}

	/**
	 * Init i18n files
	 */
	public function action_init() {
		load_plugin_textdomain( 'post-meta-inspector', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * scripts & styles for modal
	 */
	public function enqueue_admin_stylescripts(){

		$this->view_cap = apply_filters( 'pmi_view_cap', 'manage_options' );
		if ( ! current_user_can( $this->view_cap ) || ! apply_filters( 'pmi_show_post_type', '__return_true', get_post_type() ) )
			return;

		$screen = get_current_screen();
		if( in_array( $screen->base, array( 'edit' ) ) ){
			wp_enqueue_style( 'pmi-modal-style', plugin_dir_url(__FILE__) . 'assets/inspector.css' );
			wp_enqueue_script( 'pmi-baldrick', plugin_dir_url(__FILE__) . 'assets/jquery.baldrick.js', array('jquery') );
			wp_enqueue_script( 'pmi-trigger', plugin_dir_url(__FILE__) . 'assets/trigger.js', array('jquery'), $this->ver, true );
		}
	}

	/**
	 * Build the modal content
	 */
	public function inspect_modal(){

		$this->view_cap = apply_filters( 'pmi_view_cap', 'manage_options' );
		if ( ! current_user_can( $this->view_cap ) || ! apply_filters( 'pmi_show_post_type', '__return_true', get_post_type( get_post( $_POST['id'] ) ) ) )
			exit;

		$meta = get_post_meta( $_POST['id'] );

		ksort( $meta );
		?>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'Key', 'post-meta-inspector'); ?></th>
					<th><?php _e( 'Value', 'post-meta-inspector'); ?></th>
				</tr>
			</thead>
			<tbody>
		<?php
		$class = 'alternate';
		foreach( (array) $meta as $meta_key => $meta_data ){
			
			if( $class == 'alternate'){
				$class = '';
			}else{
				$class = 'alternate';
			}

			echo "	<tr class=\"" . ( $class == 'alternate' ? '' : 'alternate' ) . "\">\r\n";
			echo "		<td>" . esc_html( $meta_key ) . "</td>\r\n";
			echo "		<td>";
			foreach( (array) $meta_data as $meta_value ){
				$value = maybe_unserialize( $meta_value );
				if( is_array( $value ) || is_object( $value ) ){
					echo '<pre>';
					ob_start();
					print_r( $value );
					echo esc_html( ob_get_clean() );
					echo '</pre>';
				}else{
					echo esc_html( $value );
				}
			}
			echo "</tr>\r\n";
		}
		?>
			</tbody>
		</table>
		<br>
		<?php
		exit;
	}

	/**
	 * Place in the "Inspect Metadata" row action
	 */
	public function meta_view_row_action( $actions, $object ){
		
		$this->view_cap = apply_filters( 'pmi_view_cap', 'manage_options' );
		if ( ! current_user_can( $this->view_cap ) || ! apply_filters( 'pmi_show_post_type', '__return_true', get_post_type( $object ) ) )
			exit;;

		if( isset( $object->data ) ){
			$title 	= $object->data->user_login;
			$type 	= 'user';
		}else{
			$title 	= $object->post_title;
			$type 	= 'post';
		}

		$actions['inspect'] = '<a href="#inspect-' . $object->ID . '" data-id="' . $object->ID . '" data-object="' . $type . '" data-modal-width="700px" data-modal-buttons="' . __('Close') . '|dismiss" data-modal-title="' . $title . '" class="inspect-trigger" data-modal="modal-anchor-' . $object->ID . '">' . __( 'Inspect Meta' , 'post-meta-inspector') .'</a><span class="inspector-modal" id="modal-anchor-' . $object->ID . '"></span>';
		return $actions;
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
				<td class="value-column"><?php if( $toggled ) echo $toggle_el; ?><code <?php if( $toggled ) echo ' style="display: none;"'; ?>><?php echo esc_html( $value ); ?></code></td>
			</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>
			</tbody>
		</table>
		<script>
		jQuery(document).ready(function() {
			jQuery('.pmi_toggle').click( function(e){
				jQuery('+ code', this).show();
				jQuery(this).hide();
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