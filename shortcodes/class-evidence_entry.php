<?php
/**
 * Construct a frontend data entry form
 *
 * Shortcode: [evidence_entry]
 * Options: do_cache - boolean to disable cache option default: *false*
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
new Evidence_Hub_Shortcode_Evidence_Entry();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Evidence_Entry extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_entry';
	var $options = array('do_cache' => false);
	
	/**
	* Construct the plugin object.
	*
	* @since 0.1.1
	*/
	function content() {
		ob_start();
		extract($this->options);
		
		// display login form if not signed in
		if (!is_user_logged_in()) {
		?>
        <div id="login">
            <div class="login_form">
                <?php wp_login_form(); ?>
            </div>
        </div>
		<?php
		// if user can submit posts render form
		} elseif (current_user_can('evidence_edit_posts')) {
			$json = file_get_contents(site_url().'/'.get_option('json_api_base', 'api').'/get_nonce/?controller=posts&method=create_post');
			?>
            <script> 
			function getPath(url) {
				var a = document.createElement('a');
				a.href = url;
				return a.pathname.charAt(0) != '/' ? '/' + a.pathname : a.pathname;
			}
			var MyAjax = {
				apiurl: '<?php echo site_url().'/'.get_option('json_api_base', 'api');?>',
				ajaxurl: getPath('<?php echo admin_url();?>admin-ajax.php')
			};
			</script>
            <div id="evidence_hub_tabs">
                <ul>
                    <li><a href="#tab-1">Main</a></li>
                    <li><a href="#tab-2">Tab 2</a></li>
                </ul>
                <form id="user_entry">
                <div id="tab-1">
                    <div>
                        <input type="hidden" value="" id="nonce" name="nonce" />
                        <div>
                        <label for="type">Type</label>
                        <?php
                        /**
                        From:
                         * Plugin Name: Post Type Switcher
                         * Plugin URI:  http://wordpress.org/extend/post-type-switcher/
                         * Description: Allow switching of a post type while editing a post (in post publish section)
                         * Version:     1.2
                         * Author:      johnjamesjacoby
                         * Author URI:  http://johnjamesjacoby.com
                         */
                        
                            $args = (array) apply_filters( 'pts_post_type_filter', array(
                                    'public'  => true,
                                    'show_ui' => true
                                ) );
                            $post_types = get_post_types( $args, 'objects' ); ?>
                            <div>
                                <select name="type" id="type">
                                    <?php foreach ( $post_types as $post_type => $pt ) : ?>
                                        <?php if ( ! current_user_can( $pt->cap->edit_post )) continue; ?>
                                        <option value="<?php echo esc_attr( $pt->name ); ?>" <?php selected( get_post_type(), $post_type ); ?>><?php echo esc_html( $pt->labels->singular_name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="title">Title</label>
                            <div>
                                <input type="text" id="titlr" name='title' placeholder="Add a title" />
                            </div>
                        </div>
                        <div>
                            <label for="evidence_hub_citation">URL</label>
                            <div>
                                <input type="text" id="evidence_hub_citation" name='evidence_hub_citation' placeholder="http://..." value="" />
                            </div>
                        </div>
                        <div>
                            <input type="hidden" id="content" name="content" />
                            <label for="post_content">Description</label>
                            <div>
                                <?php wp_editor( '', 'post_content', array(
                                    'media_buttons' => false,
                                )); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tab-2">
                </div>
                </form>
                </div>
                <button type="submit" id="add_data">Submit</button>
                
            <?php
			print_r(Evidence_Hub::$post_type_fields);
		}
		?>
        <?php 
		return ob_get_clean();
	}
}