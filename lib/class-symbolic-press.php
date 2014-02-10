<?php
/**
 * Symbolic Press is a helper to help you use your WordPress plugins with Symbolic Links.
 *
 * Read more about it on:
 * @link http://www.gayadesign.com/diy/using-wordpress-plugins-as-symbolic-links/
 */
class Symbolic_Press {
	public $plugin_path;
	public $plugin_name;
	public $plugin_basename;

	/**
	 * The constructor to initiate the Symbolic Link Helper
	 *
	 * @param string $filepath base filepath
	 */
	public function __construct( $filepath ) {
		//set the plugin path for future use
		$this->plugin_path = $filepath;

		//get the filename without the extension
		$this->plugin_basename = $this->plugin_basename( $filepath );
		$this->plugin_name = $this->plugin_name_from_basename( $this->plugin_basename );

		//check if plugin is really in WP_PLUGIN_DIR
		if ( false == strstr( dirname( $this->plugin_path ), WP_PLUGIN_DIR ) ) {
			//add filter to get the correct url if asked
			add_filter( 'plugins_url', array( $this, "plugins_symbolic_filter" ) );
		}
	}

	/**
	 * Make sure the plugin works even with symbolic links
	 *
	 * @param string $url Input URL
	 *
	 * @return mixed Filted url
	 */
	public function plugins_symbolic_filter( $url ) {
		//set the path to the plugin file
		$path = dirname( $this->plugin_path );

		//get the basename of the path
		$basename = basename( $path );

		//check if this plugin is in the basename that is checked
		if ( preg_match( '/' . $this->plugin_name . '$/', $basename ) ) {
			$path = dirname( $path );
		}

		return str_replace( $path, "", $url );
	}

	/**
	 * Get the plugin name from the basename
	 *
	 * @param $basename the basename of the plugin
	 *
	 * @return string The plugin name
	 */
	public static function plugin_name_from_basename($basename) {
		return substr( basename( $basename ), 0, -4 );
	}

	/**
	 * @param $filepath The filepath to a plugin
	 *
	 * @return string The plugin name
	 */
	private function plugin_basename( $filepath ) {
		$file          = str_replace( '\\', '/', $filepath ); // sanitize for Win32 installs
		$file          = preg_replace( '|/+|', '/', $file ); // remove any duplicate slash
		$plugin_dir    = str_replace( '\\', '/', WP_PLUGIN_DIR ); // sanitize for Win32 installs
		$plugin_dir    = preg_replace( '|/+|', '/', $plugin_dir ); // remove any duplicate slash
		$mu_plugin_dir = str_replace( '\\', '/', WPMU_PLUGIN_DIR ); // sanitize for Win32 installs
		$mu_plugin_dir = preg_replace( '|/+|', '/', $mu_plugin_dir ); // remove any duplicate slash
		$sp_plugin_dir = dirname( $filepath );
		$sp_plugin_dir = dirname( $sp_plugin_dir );
		$sp_plugin_dir = preg_replace( '|/+|', '/', $sp_plugin_dir ); // remove any duplicate slash
		$file          = preg_replace( '#^' . preg_quote( $sp_plugin_dir, '#' ) . '/|^' . preg_quote( $plugin_dir, '#' ) . '/|^' . preg_quote( $mu_plugin_dir, '#' ) . '/#', '', $file ); // get relative path from plugins dir
		$file          = trim( $file, '/' );
		return $file;
	}

	/**
	 * Set the activation hook for a plugin.
	 *
	 * @param string $file
	 * @param mixed  $function
	 */
	public static function register_activation_hook( $file, $function ) {
		$plugin_basename = Symbolic_Press::plugin_basename( $file );

		//bind the activation action
		add_action( 'activate_' . $plugin_basename, $function );
	}

	/**
	 * Set the deactivation hook for a plugin.
	 *
	 * @param string $file
	 * @param mixed  $function
	 */
	public static function register_deactivation_hook( $file, $function ) {
		$plugin_basename = Symbolic_Press::plugin_basename( $file );

		//bind the deactivation action
		add_action( 'deactivate_' . $plugin_basename, $function );
	}

	/**
	 * Set the uninstallation hook for a plugin.
	 *
	 * @param string $file
	 * @param callback $callback The callback to run when the hook is called. Must be a static method or function.
	 */
	public static function register_uninstall_hook( $file, $callback ) {
		if ( is_array( $callback ) && is_object( $callback[0] ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Only a static class method or function can be used in an uninstall hook.' ), '3.1' );
			return;
		}

		// The option should not be autoloaded, because it is not needed in most
		// cases. Emphasis should be put on using the 'uninstall.php' way of
		// uninstalling the plugin.
		$uninstallable_plugins = (array) get_option('uninstall_plugins');
		$uninstallable_plugins[Symbolic_Press::plugin_name_from_basename($file)] = $callback;
		update_option('uninstall_plugins', $uninstallable_plugins);
	}
}