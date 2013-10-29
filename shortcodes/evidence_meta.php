<?php

new Evidence_Hub_Shortcode_Evidence_Meta();
class Evidence_Hub_Shortcode_Evidence_Meta extends Evidence_Hub_Shortcode {
	var $shortcode = 'evidence_meta';
	var $defaults = array(
		'post_id' => false,
		'post_ids' => false,
		'title' => false,
		'location' => 'header',
		'header_terms' => 'polarity,sector,country,hypothesis',
		'footer_terms' => 'resource_link,citation',
		'no_evidence_message' => "There is no meta data for this evidence",
		'title_tag' => 'h4',
	);

	

	static $post_types_with_evidence = array('evidence');
	
	function add_to_page($content) {
		if (in_array(get_post_type(), self::$post_types_with_evidence)) {
			$content = do_shortcode('[evidence_meta location="header"]').$content.do_shortcode('[evidence_meta location="footer"]');
		}
		return $content;
	}
	
	function prep_options() {
		// Turn csv into array
		if (!is_array($this->options['post_ids'])) $this->options['post_ids'] = array();
		if (!empty($this->options['post_ids'])) $this->options['post_ids'] = explode(',', $this->options['post_ids']);

		// add post_id to post_ids and get rid of it
		if ($this->options['post_id']) $this->options['post_ids'] = array_merge($this->options['post_ids'], explode(',', $this->options['post_id']));
		unset($this->options['post_id']);
		
		// fallback to current post if nothing specified
		if (empty($this->options['post_ids']) && $GLOBALS['post']->ID) $this->options['post_ids'] = array($GLOBALS['post']->ID);
		
		// unique list
		$this->options['post_ids'] = array_unique($this->options['post_ids']);
	}

	function content() {
		ob_start();
		extract($this->options);
		
		$errors = array();

		if (empty($post_ids)) $errors[] = "No posts ID provided";
		
		foreach ($post_ids as $post_id) {
			$post = Evidence_Hub::add_meta(get_post($post_id));
			if (!$post) {
				$errors[] = "$post_id is not a valid post ID";
			} else if (!in_array(get_post_type($post), self::$post_types_with_evidence)) {
				$errors[] = "<a href='".get_permalink($post->ID)."'>$post->post_title</a> is not the correct type of post";
			} else if ($location=="header") { 
				$this->meta_bar($post, $header_terms);
			} else if ($location=="footer") { 
	  			$this->meta_bar($post, $footer_terms);
			}
		}
		
		if (count($errors)) return "[Shortcode errors (".$this->shortcode."): ".implode(', ', $errors)."]";
		
		return ob_get_clean();
	}
	
	function meta_bar($post, $options){
		$out = array();
		foreach (explode(',', $options) as $type) {
			$type = trim($type);
			$slug = $type."_slug";
			//print_r("<!-- ".json_encode($post)." -->");
			print_r("<!-- ".json_encode($fields)." -->");
			if (!is_wp_error($slug_url = get_term_link($post->$slug,"evidence_hub_".$type)) || ($type=="citation" && $post->$type)){
				if ($type=="citation"){
					$slug_url = $post->$type;
				}
				$out[] = __(sprintf('<span class="meta_label">%s</span>: <a href="%s">%s</a>', ucwords(str_replace("_", " ",$type)),$slug_url , $post->$type));
			} else if ($post->$type) {
				$out[] = __(sprintf('<span class="meta_label">%s</span>: %s', ucwords(str_replace("_", " ",$type)),$post->$type));
			} else if ($type == 'hypothesis') {
				$out[] =  __(sprintf('<span class="meta_label">%s</span>: <a href="%s">%s</a>', ucwords($type),get_permalink($post->evidence_hub_hypothesis_id) , get_the_title($post->evidence_hub_hypothesis_id)));
			}
		}
		if(!empty($out)){ 
			echo '<div id="evidence-meta">'.implode(" | ", $out).'</div>';
       }	
	}
}