<?php

new Evidence_Hub_Shortcode_Hypothesis_Sankey();
class Evidence_Hub_Shortcode_Hypothesis_Sankey extends Evidence_Hub_Shortcode {
	var $shortcode = 'hypothesis_sankey';
	var $defaults = array('slug' => 'World');

	static $post_types_with_sessions = NULL;

	function content() {
		ob_start();
		extract($this->options);
				?>
		<script src="<?php echo plugins_url( 'js/sankey.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
        <script src="<?php echo plugins_url( 'js/sankey-main.js' , EVIDENCE_HUB_REGISTER_FILE )?>" type="text/javascript" charset="utf-8"></script>
        <div id="sankey-display"></div>
        <script> 
			function getPath(url) {
				var a = document.createElement('a');
				a.href = url;
				return a.pathname.charAt(0) != '/' ? '/' + a.pathname : a.pathname;
			}
			var MyAjax = {
				pluginurl: getPath('<?php echo EVIDENCE_HUB_URL; ?>'),
				ajaxurl: getPath('<?php echo admin_url();?>admin-ajax.php')
			};
			var graph = {};
			var SANKEY_MARGIN = {top: 1, right: 1, bottom: 1, left: 1},
			SANKEY_WIDTH = document.getElementById("content").offsetWidth,
			SANKEY_HEIGHT = 400;
			
			var svg = d3.select('#sankey-display').append('svg');
			san = svg
				.attr("height" , SANKEY_HEIGHT)
				.append('g')
				.attr('id', 'sandisplay');
			
			renderSankey('<?php echo $slug ?>');
		</script>
        
        <?php
		return ob_get_clean();
	}
}