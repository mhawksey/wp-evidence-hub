<?php
/**
* A base class defining various utilities and 
* wrappers around common WordPress functions.
*/

class Evidence_Hub_Base {

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

}

