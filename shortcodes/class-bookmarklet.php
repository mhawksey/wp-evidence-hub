<?php
/**
 * Bookmarklet Shortcode
 * 
 * Generates metadata bars for projects
 * Shortcode: [bookmarklet]
 * Options: url - string submit page
 *			text - string button text
 *
 * Based on shortcode class construction used in Conferencer http://wordpress.org/plugins/conferencer/.
 *
 * @since 0.1.1
 *
 * @package Evidence_Hub
 * @subpackage Evidence_Hub_Shortcode
 */
new Evidence_Hub_Shortcode_Bookmarklet();
// Base class 'Evidence_Hub_Shortcode' defined in 'shortcodes/class-shortcode.php'.
class Evidence_Hub_Shortcode_Bookmarklet extends Evidence_Hub_Shortcode {

	const SHORTCODE = 'bookmarklet';

	var $defaults = array(
		'url' => false,
		'text' => 'Bookmarklet',
	);

	/**
	* Generate post content. 
	*
	* @since 0.1.1
	* @return string.
	*/
	protected function content() {
		ob_start();
		extract($this->options);
		if (!($url)): 
			$out = 'To use this bookmarklet shortcode specify a target url e.g. [bookmarklet url="'.homeurl().'"]';
			return $out;
		else :
		?>
			<a class="ehbutton" href="javascript:(function(){var w=window,d=document,e=d.documentElement,g=d.getElementsByTagName('body')[0],x=w.innerWidth||e.clientWidth||g.clientWidth,y = w.innerHeight||e.clientHeight||g.clientHeight,f='<?php echo $url;?>?bookmarklet=true&url='+encodeURIComponent(w.location.href)+'&amp;title='+encodeURIComponent(d.title);a=function(){if(!w.open(f,'mightchange','location=yes,links=no,scrollbars=yes,toolbar=no,width=526,height='+y+',top=0, left='+(x-536))){location.href=f}};if(/Firefox/.test(navigator.userAgent)){setTimeout(a,0)}else{a()}})();"><?php echo $text; ?></a>
		<?php
			return ob_get_clean();
		endif;
	}
}