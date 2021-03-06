<?php
/**
* A base class defining various utilities and 
* wrappers around common WordPress functions.
*/


class Evidence_Hub_Base {

	const LOC_DOMAIN = 'wp-evidence-hub';

	protected $_messages = array();


	/** Require a PHP script or array of scripts.
	*/
	protected function _require( $paths ) {
		if (is_string( $paths )) {
			require_once EVIDENCE_HUB_PATH .'/'. $paths;
		} else {
			foreach ($paths as $path) {
				require_once EVIDENCE_HUB_PATH .'/'. $path;
			}
		}
	}

	/** Hooks a function or functions to corresponding WP actions.
	*
	* @link http://codex.wordpress.org/Function_Reference/add_action
	*/
	protected function add_actions( $hooks, $function = NULL, $priority = 10, $accepted_args = 1 ) {
		if (is_string( $hooks )) {
			add_action( $hooks, array( &$this, $function ), $priority, $accepted_args );
		} else {
			foreach ($hooks as $hook) {
				$function = isset($hook[ 1 ]) ? $hook[ 1 ] : NULL;
				$priority = isset($hook[ 2 ]) ? $hook[ 2 ] : 10;
				add_action( $hook[ 0 ], array( &$this, $function), $priority );
			}
		}
	}


	/** Utilities.
	*/

	protected static function error( $obj ) {
		if (headers_sent()): ?>
		<script>window.console && console.log(<?php echo json_encode($obj)?>)</script>
<?php
		endif;
	}

	/** Quick and dirty variable 'dump' within a HTML comment.
	*/
	protected function debug( $text, $label = NULL ) {
		if (headers_sent()) {
			echo "\n<!--$label:"; var_dump( $text ); echo "-->\n";
		}
		return $this->message( $text, 'debug' );
	}

	protected function message( $text, $type = 'ok' ) {
		$message_r = array( 'type' => $type, 'msg' => $text );
		$this->_messages[] = $message_r;
		@header('X-Wp-Evidence-Hub-'. sprintf( '%02d',
			count($this->_messages)) .': '. json_encode( $message_r ));
	}

	/** Get a WP configuration option from a PHP define() or the database.
	* @return string
	*/
	protected function get_option( $option, $default = NULL ) {
		$KEY = strtoupper( $option );
		return defined( $KEY ) ? constant( $KEY ) : get_option( $option, $default );
	}

	/** JSON decode a configuration option. */
	protected function decode_option( $option, $default = null ) {
		return json_decode($this->get_option( $option, $default ));
	}

	/** Use the term "Hypothesis" (default) or "Proposition" - LACE [Bug: #39] */
	protected function is_proposition() {
		return $this->get_option( 'wp_evidence_hub_is_proposition' );
	}
}

