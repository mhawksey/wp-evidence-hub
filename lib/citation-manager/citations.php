<?php

/*
 Plugin Name: Citation Manager
 Version: 0.9.6
 Plugin URI: http://www.nostate.com/3782/citation-manager-for-wordpress-plugin/
 Description: Adds support for tracking and displaying external citations to WordPress content
 Author: Mike Gogulski
 Author URI: http://www.nostate.com/
 */

/*
 This is free and unencumbered software released into the public domain.

 Anyone is free to copy, modify, publish, use, compile, sell, or
 distribute this software, either in source code form or as a compiled
 binary, for any purpose, commercial or non-commercial, and by any
 means.

 In jurisdictions that recognize copyright laws, the author or authors
 of this software dedicate any and all copyright interest in the
 software to the public domain. We make this dedication for the benefit
 of the public at large and to the detriment of our heirs and
 successors. We intend this dedication to be an overt act of
 relinquishment in perpetuity of all present and future rights to this
 software under copyright law.

 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
 IN NO EVENT SHALL THE AUTHORS BE LIABLE FOR ANY CLAIM, DAMAGES OR
 OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE,
 ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 OTHER DEALINGS IN THE SOFTWARE.

 For more information, please refer to <http://unlicense.org/>
 */

// Portions modeled on and taken from the Podcasting plugin by TSG

define('CITATION_MANAGER_VERSION', '0.9.6');

// Register citation taxonomy
function build_taxonomies() {
	register_taxonomy('citations_format', 'custom_field');
}

add_action('init', 'build_taxonomies', 0);

// Set up post-install actions
register_activation_hook(__FILE__, 'citations_install');

function citationhtml($citation) {
	// TODO: Handle lots of errors
	$cit = unserialize(base64_decode($citation['meta_value']));
	$cit_author = htmlentities(stripslashes($cit['author']), ENT_QUOTES);
	$cit_title = stripslashes($cit['title']);
	$cit_publication = stripslashes($cit['publication']);
	$cit_where = htmlentities(stripslashes($cit['where']), ENT_QUOTES);
	$cit_date = htmlentities(stripslashes($cit['date']), ENT_QUOTES);
	$cit_url = htmlentities(stripslashes($cit['url']), ENT_QUOTES);
	$html = '<li class="citation">';
	if ($cit_author) {
		$html .= $cit_author . ', ';
	}
	// either title or publication is mandatory. TODO: enforce
	if ($cit_url) {
		$html .= '<a ';
		if (get_option('cit_targetblank') == 1)
			$html .= 'target="_blank" ';
		$html .= "href='" . $cit_url . "' title='" . (($cit_title) ? htmlspecialchars($cit_title, ENT_QUOTES) : htmlspecialchars($cit_publication, ENT_QUOTES)) . "'>";
	}
	$html .= (($cit_title) ? $cit_title : $cit_publication);
	if ($cit_url)
		$html .= '</a>';
	if ($cit_publication or $cit_where or $cit_date)
		$html .= ', ';
	if ($cit_publication)
		$html .= $cit_publication . (($cit_where or $cit_date) ? ', ' : '');
	if ($cit_where)
		$html .= $cit_where . ($cit_date ? ', ' : '');
	if ($cit_date)
		$html .= $cit_date;
	$html .= "</li>\n";
	return $html;
}

// generate unordered list of citations
function citation_manager_html() {
	global $wpdb, $post;
	$html = '';
	// check for presence of citations
	$citations = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$post->ID} AND meta_key = 'citation' ORDER BY meta_id " . get_option('cit_sortorder'), ARRAY_A);
	if ($citations != null and is_array($citations) and count($citations) > 0) {
		// add intro text
		$html .= '<br />';
		$html .= stripslashes(get_option('cit_introtext'));
		$html .= "\n" . '<ul class="citations">' . "\n";
		// build and add citation list
		foreach ($citations as $citation) {
			$html .= citationhtml($citation);
		}
		$html .= "</ul>\n";
		// add outro text
		$html .= stripslashes(get_option('cit_outrotext')) . "\n";
	}
	return $html;
}

// Give the display filter a high priority to ensure it comes before AddToAny and so forth
add_filter('the_content', 'citation_manager_display_hook', 2);
function citation_manager_display_hook($html) {
	if (is_single() or is_page())
		$html .= citation_manager_html();
	return $html;
}

// Shortcode handlers
// Output total number of citations on site
function citation_count_total() {
	global $wpdb;
	$citations = $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'citation' ORDER BY meta_id", ARRAY_A);
	return '' . $citations[0]['COUNT(*)'];
}
add_shortcode('citation-count-total', 'citation_count_total');

// output number of citations on this page/post
function citation_count() {
	global $wpdb, $post;
	$citations = $wpdb->get_results("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = {$post->ID} AND meta_key = 'citation' ORDER BY meta_id", ARRAY_A);
	return '' . $citations[0]['COUNT(*)'];
}
add_shortcode('citation-count', 'citation_count');

// dump a nested unordered list of all citationson the site, ordered by post, meta_id (citation addition sequence)
function citation_dump() {
	global $wpdb;
	//select ID, post_title, meta_value, meta_id from fbtestwp1_posts inner join fbtestwp1_postmeta on (fbtestwp1_posts.ID=fbtestwp1_postmeta.post_id) where meta_key='citation' order by ID, meta_id;
	//$citations = $wpdb->get_results("SELECT ID, post_title, meta_value, meta_id FROM {$wpdb->posts} INNER JOIN {$wpdb->postmeta} ON ({$wpdb->posts}.ID={$wpdb->postmeta}.post_id) WHERE meta_key = 'citation' ORDER BY ID, meta_id", ARRAY_A);
	$querystr = "" .
		"SELECT ID, post_title, meta_value, meta_id " .
		"FROM $wpdb->posts posts " .
		"INNER JOIN $wpdb->postmeta meta " .
		"ON (posts.ID=meta.post_id) " .
		"WHERE meta_key = 'citation' " .
		"ORDER BY ID " . get_option('cit_sortorder') . ", meta_id " . get_option('cit_sortorder');

	$citations = $wpdb->get_results($querystr, ARRAY_A);
	$curpost = 0;
	if (count($citations) < 1)
		return "";
	$html = '<ul class="citation-dump">' . "\n";
	foreach ($citations as $citation) {
		if ($citation['ID'] != $curpost) {
			if ($curpost != 0) {
				$html .= '</ul></li>' . "\n";
			}
			$html .= '<li class="citation-post-title">';
			$html .= '<a href="' . get_permalink($citation['ID']) . '" title="' . htmlspecialchars($citation['post_title']) . '">' . $citation['post_title'] . '</a>' . "\n";
			$html .= '<ul class="citation-list">' . "\n";
		}
		$html .= citationhtml($citation);
		$curpost = $citation['ID'];
	}
	$html .= '</ul></li></ul>' . "\n";
	return $html;
}
add_shortcode('citation-dump', 'citation_dump');

// Post-install procedures
function citations_install() {
	// Add citations options to the database
	add_option('cit_introtext', 'Citations to this article:');
	add_option('cit_outrotext', '');
	add_option('cit_targetblank', '0');
	add_option('cit_sortorder', 'DESC');
}

// Add CSS to admin section
function citations_admin_css() {
	echo '<link rel="stylesheet" href="' . plugins_url("/citation-manager/citations-admin.css") . '" type="text/css" />';
}

// Include admin CSS
add_action('admin_head', 'citations_admin_css');

// Add CSS for display
function citations_css() {
	echo '<link rel="stylesheet" href="' . plugins_url("/citation-manager/citations.css") . '" type="text/css" />';
}
add_action('wp_head', 'citations_css');

add_action('plugin_action_links_' . plugin_basename(__FILE__), 'citations_filter_plugin_actions');

// Add settings option
function citations_filter_plugin_actions($links) {
	$new_links = array();
	$new_links[] = '<a href="options-general.php?page=citations-settings.php">Settings</a>';
	return array_merge($links, $new_links);
}

add_filter('plugin_row_meta', 'citations_filter_plugin_links', 10, 2);

// Add FAQ and support information
function citations_filter_plugin_links($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$links[] = '<a href="http://www.nostate.com/3782/citation-manager-for-wordpress-plugin/">Support</a>';
		$links[] = '<a href="http://www.nostate.com/support-nostatecom/">Donate</a>';
	}
	return $links;
}

/**
 * Take a potentially invalid URL and corrects it
 * @param p_url - the url
 * @return a valid URL
 */
function citations_urlencode($p_url) {
	$ta = parse_url($p_url);
	if (!empty($ta[scheme])) {
		$ta[scheme] .= '://';
	}
	if (!empty($ta[pass]) and !empty($ta[user])) {
		$ta[user] .= ':';
		$ta[pass] = rawurlencode($ta[pass]) . '@';
	} elseif (!empty($ta[user])) {
		$ta[user] .= '@';
	}
	if (!empty($ta[port]) and !empty($ta[host])) {
		$ta[host] = '' . $ta[host] . ':';
	} elseif (!empty($ta[host])) {
		$ta[host] = $ta[host];
	}
	if (!empty($ta[path])) {
		$tu = '';
		$tok = strtok($ta[path], "\\/");
		while (strlen($tok)) {
			$tu .= rawurlencode($tok) . '/';
			$tok = strtok("\\/");
		}
		$ta[path] = '/' . trim($tu, '/');
	}
	if (!empty($ta[query])) {
		$ta[query] = '?' . $ta[query];
	}
	if (!empty($ta[fragment])) {
		$ta[fragment] = '#' . $ta[fragment];
	}

	return implode('', array(
		$ta[scheme],
		$ta[user],
		$ta[pass],
		$ta[host],
		$ta[port],
		$ta[path],
		$ta[query],
		$ta[fragment]
	));
}

// Include settings code
include_once('citations-settings.php');

// Include meta box code
include_once('citations-metabox.php');
?>
