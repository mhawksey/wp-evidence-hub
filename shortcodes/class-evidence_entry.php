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
		if (!is_user_logged_in()) : ?>

<div id="login">
  <div class="login_form">
    <?php wp_login_form(); ?>
  </div>
</div>
<?php
		// if user can submit posts render form
		elseif (current_user_can('edit_evidence')) :
			if(isset($_GET['bookmarklet'])){
				echo '<style>#site-title-wrapper h2, #site-navigation, #secondary { display:none; }</style>';	
			}
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
			jQuery.noConflict();
			 
			jQuery( document ).ready(function( $ ) {
				//$( "form[id^=content_for]" ).hide();
				//$( "#content_for_evidence" ).show();
				$( ".modal" ).hide();
				
			});
			
			// custom call back from wp_editor/tinymce to hide only after rendered to preserve height
			function myCustomInitInstance(ed){
				console.log("hide post_types");
				var what = ed.id.split("_").pop().trim();
				if (what !== "evidence"){
					jQuery("#content_for_"+what).hide();
				}
			}	
			</script>
            
            <div id="eh-entry">
            	<div class="modal"></div>
                <form id="common_entry" >
                    <input type="hidden" value="" id="nonce" name="nonce" />
                    <input type="hidden" id="content" name="content" />
                    <input type="hidden" id="tags" name="tags" />
                    <div class="eh_block">
                        <label for="type" class="typeselect">Type: </label>
                        <?php $post_types = array('evidence', 'policy', 'project'); ?>
                      
                        <select name="type" id="type">
                            <?php foreach ( $post_types as $post_type ) : ?>
                            <option value="<?php echo $post_type;?>"><?php echo ucwords($post_type) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="eh_block">
                        <div class="eh_input eh_evidence_hub_title">
                            <label for="title" class="eh_label">Title<span class="required">*</span>: </label> 	
                            <span class="eh_input_field">
                                <input class="text" type="text" name="title" id="title" value="<?php if (isset($_GET['title'])) echo $_GET['title'];?>">
                            </span>
                        </div>
                    </div>
                </form>
                    <?php 
                        foreach ( $post_types as $post_type ) :
                            echo '<form id="content_for_'.$post_type.'"><div class="eh_block">';
								$sub_options = Evidence_Hub::filterOptions(Evidence_Hub::$post_type_fields[$post_type], 'position', 'bottom');
								include(sprintf("%s/post-types/custom_post_metaboxes.php", EVIDENCE_HUB_PATH));
								?>
								</div>
								<div class="eh_input eh_evidence_hub_content_<?php echo $post_type;?>">
                                	<?php do_action('media_buttons', 'post_content_'.$post_type); ?>
									<?php wp_editor( '', 'post_content_'.$post_type, array(
																'media_buttons' => true,
																'textarea_rows' => 20,
															)); ?>
								</div>
								<div class="eh_block">
								<?php 
								$sub_options = Evidence_Hub::filterOptions(Evidence_Hub::$post_type_fields[$post_type], 'position', 'side');
								include(sprintf("%s/post-types/custom_post_metaboxes.php", EVIDENCE_HUB_PATH)); ?>
								</div>
                            </form>
					<?php
                        endforeach; ?>
                        <div class="eh_block">
                			<div id="tagsdiv-post_tag" class="postbox">
                                <label for="newtag">Tags</label>
                                <div class="inside">
                                    <div class="tagsdiv" id="post_tag">
                                        <div class="jaxtag">
                                            <label class="screen-reader-text" for="newtag">Tags</label>
                                            <input type="hidden" name="tax_input[post_tag]" class="the-tags" id="tax-input[post_tag]" value="">
                                            <div class="ajaxtag">
                                                <input type="text" name="newtag[post_tag]" class="newtag form-input-tip" size="16" autocomplete="off" value="">
                                                <input type="button" class="button tagadd" value="Add">
                                            </div>
                                        </div>
                                        <div class="tagchecklist"></div>
                                    </div>
                                    <p class="tagcloud-link"><a href="#titlediv" class="tagcloud-link" id="link-post_tag">Choose from the most used tags</a></p>
                                </div>
                            </div>
                        </div>
                        
						<form id="pgm_map">
                            <div class="eh_block">
                            <?php
                            $sub_options = array('pgm' => array('type' => 'pgm'));
                            include(sprintf("%s/post-types/custom_post_metaboxes.php", EVIDENCE_HUB_PATH));
                            ?>
                            </div>
                        </form>
                <div class="action-area">
                	<div id="status"></div>
                	<button type="submit" id="add_data">Submit for Review</button>
                    <span class="spinner"></span>
                    <br style="clear:both"/>
                </div>
            </div> <!-- EOF eh-entry -->
            
			<?php
			//print_r(Evidence_Hub::$post_type_fields);
		endif; // user can see edit form 
		return ob_get_clean();
	}
}

