<?php

/**
 * @author Arashi
 * @copyright 2011
 */
class WPAppStore_Upgrader_Skin {

	var $upgrader;
	var $done_header = false;
	var $result = false;

	function WP_Upgrader_Skin($args = array()) {
		return $this->__construct($args);
	}
	function __construct($args = array()) {
		$defaults = array( 'url' => '', 'nonce' => '', 'title' => '', 'context' => false );
		$this->options = wp_parse_args($args, $defaults);
	}

	function set_upgrader(&$upgrader) {
		if ( is_object($upgrader) )
			$this->upgrader =& $upgrader;
		$this->add_strings();
	}

	function add_strings() {
	}

	function set_result($result) {
		$this->result = $result;
	}

	function request_filesystem_credentials($error = false) {
		$url = $this->options['url'];
		$context = $this->options['context'];
		if ( !empty($this->options['nonce']) )
			$url = wp_nonce_url($url, $this->options['nonce']);
		return request_filesystem_credentials($url, '', $error, $context); //Possible to bring inline, Leaving as is for now.
	}

	function header() {
		if ( $this->done_header )
			return;
		$this->done_header = true;
		echo '<div class="wrap">';
		echo screen_icon();
		echo '<h2>' . $this->options['title'] . '</h2>';
	}
	function footer() {
		echo '</div>';
	}

	function error($errors) {
		if ( ! $this->done_header )
			$this->header();
		if ( is_string($errors) ) {
			$this->feedback($errors);
		} elseif ( is_wp_error($errors) && $errors->get_error_code() ) {
			foreach ( $errors->get_error_messages() as $message ) {
				if ( $errors->get_error_data() )
					$this->feedback($message . ' ' . $errors->get_error_data() );
				else
					$this->feedback($message);
			}
		}
	}

	function feedback($string) {
		if ( isset( $this->upgrader->strings[$string] ) )
			$string = $this->upgrader->strings[$string];

		if ( strpos($string, '%') !== false ) {
			$args = func_get_args();
			$args = array_splice($args, 1);
			if ( !empty($args) )
				$string = vsprintf($string, $args);
		}
		if ( empty($string) )
			return;
		show_message($string);
	}
	function before() {}
	function after() {}

}

class WPAppstore_Plugin_Installer_Skin extends WPAppStore_Upgrader_Skin {
	var $api;
	var $type;

	function Plugin_Installer_Skin($args = array()) {
		return $this->__construct($args);
	}

	function __construct($args = array()) {
		$defaults = array( 'type' => 'web', 'url' => '', 'plugin' => '', 'nonce' => '', 'title' => '' );
		$args = wp_parse_args($args, $defaults);

		$this->type = $args['type'];
		$this->api = isset($args['api']) ? $args['api'] : array();

		parent::__construct($args);
	}

	function before() {
		if ( !empty($this->api) )
			$this->upgrader->strings['process_success'] = sprintf( __('Successfully installed the plugin <strong>%s %s</strong>.'), $this->api->name, $this->api->version);
            $appstore = new WP_AppStore();
    }

	function after() {

		$plugin_file = $this->upgrader->plugin_info();

		$install_actions = array();

		$from = isset($_GET['from']) ? stripslashes($_GET['from']) : 'plugins';

		if ( 'import' == $from )
			$install_actions['activate_plugin'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;from=import&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin') . '" target="_parent">' . __('Activate Plugin &amp; Run Importer') . '</a>';
		else
			$install_actions['activate_plugin'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin') . '" target="_parent">' . __('Activate Plugin') . '</a>';

		if ( is_multisite() && current_user_can( 'manage_network_plugins' ) ) {
			$install_actions['network_activate'] = '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;networkwide=1&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file) . '" title="' . esc_attr__('Activate this plugin for all sites in this network') . '" target="_parent">' . __('Network Activate') . '</a>';
			unset( $install_actions['activate_plugin'] );
		}

		if ( 'import' == $from )
			$install_actions['importers_page'] = '<a href="' . admin_url('import.php') . '" title="' . esc_attr__('Return to Importers') . '" target="_parent">' . __('Return to Importers') . '</a>';
		else if ( $this->type == 'web' )
			$install_actions['plugins_page'] = '<a href="' . admin_url('admin.php') . '?page=wp-appstore.php" title="' . esc_attr__('Return to WP AppStore') . '" target="_parent">' . __('Return to WP AppStore') . '</a>';
		else
			$install_actions['plugins_page'] = '<a href="' . self_admin_url('plugins.php') . '" title="' . esc_attr__('Return to Plugins page') . '" target="_parent">' . __('Return to Plugins page') . '</a>';


		if ( ! $this->result || is_wp_error($this->result) ) {
			unset( $install_actions['activate_plugin'] );
			unset( $install_actions['network_activate'] );
		}
		$install_actions = apply_filters('install_plugin_complete_actions', $install_actions, $this->api, $plugin_file);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
}
class WPAppstore_Plugin_Upgrader_Skin extends WPAppStore_Upgrader_Skin {
	var $plugin = '';
	var $plugin_active = false;
	var $plugin_network_active = false;

	function __construct($args = array()) {
		$defaults = array( 'url' => '', 'plugin' => '', 'nonce' => '', 'title' => __('Update Plugin') );
		$args = wp_parse_args($args, $defaults);

		$this->plugin = $args['plugin'];

		$this->plugin_active = is_plugin_active( $this->plugin );
		$this->plugin_network_active = is_plugin_active_for_network( $this->plugin );

		parent::__construct($args);
	}

	function after() {
		$this->plugin = $this->upgrader->plugin_info();
		if ( !empty($this->plugin) && !is_wp_error($this->result) && $this->plugin_active ){
			show_message(__('Reactivating the plugin&#8230;'));
			echo '<iframe style="border:0;overflow:hidden" width="100%" height="170px" src="' . wp_nonce_url('update.php?action=activate-plugin&networkwide=' . $this->plugin_network_active . '&plugin=' . $this->plugin, 'activate-plugin_' . $this->plugin) .'"></iframe>';
		}

		$update_actions =  array(
			'activate_plugin' => '<a href="' . wp_nonce_url('plugins.php?action=activate&amp;plugin=' . $this->plugin, 'activate-plugin_' . $this->plugin) . '" title="' . esc_attr__('Activate this plugin') . '" target="_parent">' . __('Activate Plugin') . '</a>',
			'plugins_page' => '<a href="' . self_admin_url('plugins.php') . '" title="' . esc_attr__('Go to plugins page') . '" target="_parent">' . __('Return to Plugins page') . '</a>',
            'return_to_appstore' => '<a href="' . admin_url('admin.php') . '?page=wp-appstore.php" title="' . esc_attr__('Return to WP AppStore') . '" target="_parent">' . __('Return to WP AppStore') . '</a>',
		);
		if ( $this->plugin_active )
			unset( $update_actions['activate_plugin'] );
		if ( ! $this->result || is_wp_error($this->result) )
			unset( $update_actions['activate_plugin'] );

		$update_actions = apply_filters('update_plugin_complete_actions', $update_actions, $this->plugin);
		if ( ! empty($update_actions) )
			$this->feedback(implode(' | ', (array)$update_actions));
	}

	function before() {
		if ( $this->upgrader->show_before ) {
			echo $this->upgrader->show_before;
			$this->upgrader->show_before = '';
		}
	}
}
class WPAppstore_Theme_Installer_Skin extends WPAppStore_Upgrader_Skin {
	var $api;
	var $type;
    
    function Theme_Installer_Skin($args = array()) {
		return $this->__construct($args);
	}

	function __construct($args = array()) {
		$defaults = array( 'type' => 'web', 'url' => '', 'theme' => '', 'nonce' => '', 'title' => '' );
		$args = wp_parse_args($args, $defaults);

		$this->type = $args['type'];
		$this->api = isset($args['api']) ? $args['api'] : array();

		parent::__construct($args);
	}

	function before() {
		if ( !empty($this->api) ) {
			/* translators: 1: theme name, 2: version */
			$this->upgrader->strings['process_success'] = sprintf( __('Successfully installed the theme <strong>%1$s %2$s</strong>.'), $this->api->name, $this->api->version);
		}
        $appstore = new WP_AppStore();
	}

	function after() {
		if ( empty($this->upgrader->result['destination_name']) )
			return;

		$theme_info = $this->upgrader->theme_info();
		if ( empty($theme_info) )
			return;
		$name = $theme_info['Name'];
		$stylesheet = $this->upgrader->result['destination_name'];
		$template = !empty($theme_info['Template']) ? $theme_info['Template'] : $stylesheet;

		$preview_link = htmlspecialchars( add_query_arg( array('preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'preview_iframe' => 1, 'TB_iframe' => 'true' ), trailingslashit(esc_url(get_option('home'))) ) );
		$activate_link = wp_nonce_url("themes.php?action=activate&amp;template=" . urlencode($template) . "&amp;stylesheet=" . urlencode($stylesheet), 'switch-theme_' . $template);

		$install_actions = array(
			'preview' => '<a href="' . $preview_link . '" class="thickbox thickbox-preview" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $name)) . '">' . __('Preview') . '</a>',
			'activate' => '<a href="' . $activate_link .  '" class="activatelink" title="' . esc_attr( sprintf( __('Activate &#8220;%s&#8221;'), $name ) ) . '">' . __('Activate') . '</a>'
							);

		if ( $this->type == 'web' )
			$install_actions['plugins_page'] = '<a href="' . admin_url('admin.php') . '?page=wp-appstore.php" title="' . esc_attr__('Return to WP AppStore') . '" target="_parent">' . __('Return to WP AppStore') . '</a>';
		else
			$install_actions['themes_page'] = '<a href="' . self_admin_url('themes.php') . '" title="' . esc_attr__('Themes page') . '" target="_parent">' . __('Return to Themes page') . '</a>';

		if ( ! $this->result || is_wp_error($this->result) || is_network_admin() )
			unset( $install_actions['activate'], $install_actions['preview'] );

		$install_actions = apply_filters('install_theme_complete_actions', $install_actions, $this->api, $stylesheet, $theme_info);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
}
class WPAppstore_Theme_Upgrader_Skin extends WPAppStore_Upgrader_Skin {
	var $theme = '';

	function __construct($args = array()) {
		$defaults = array( 'url' => '', 'theme' => '', 'nonce' => '', 'title' => __('Update Theme') );
		$args = wp_parse_args($args, $defaults);

		$this->theme = $args['theme'];

		parent::__construct($args);
	}

	function after() {

		$update_actions = array();
		if ( !empty($this->upgrader->result['destination_name']) &&
			($theme_info = $this->upgrader->theme_info()) &&
			!empty($theme_info) ) {

			$name = $theme_info['Name'];
			$stylesheet = $this->upgrader->result['destination_name'];
			$template = !empty($theme_info['Template']) ? $theme_info['Template'] : $stylesheet;

			$preview_link = htmlspecialchars( add_query_arg( array('preview' => 1, 'template' => $template, 'stylesheet' => $stylesheet, 'TB_iframe' => 'true' ), trailingslashit(esc_url(get_option('home'))) ) );
			$activate_link = wp_nonce_url("themes.php?action=activate&amp;template=" . urlencode($template) . "&amp;stylesheet=" . urlencode($stylesheet), 'switch-theme_' . $template);

			$update_actions['preview'] = '<a href="' . $preview_link . '" class="thickbox thickbox-preview" title="' . esc_attr(sprintf(__('Preview &#8220;%s&#8221;'), $name)) . '">' . __('Preview') . '</a>';
			$update_actions['activate'] = '<a href="' . $activate_link .  '" class="activatelink" title="' . esc_attr( sprintf( __('Activate &#8220;%s&#8221;'), $name ) ) . '">' . __('Activate') . '</a>';

			if ( ( ! $this->result || is_wp_error($this->result) ) || $stylesheet == get_stylesheet() )
				unset($update_actions['preview'], $update_actions['activate']);
		}

		$update_actions['themes_page'] = '<a href="' . self_admin_url('themes.php') . '" title="' . esc_attr__('Return to Themes page') . '" target="_parent">' . __('Return to Themes page') . '</a>';
        $update_actions['return'] = '<a href="' . admin_url('admin.php') . '?page=wp-appstore.php" title="' . esc_attr__('Return to WP AppStore') . '" target="_parent">' . __('Return to WP AppStore') . '</a>';
		$update_actions = apply_filters('update_theme_complete_actions', $update_actions, $this->theme);
		if ( ! empty($update_actions) )
			$this->feedback(implode(' | ', (array)$update_actions));
	}
}

class WP_AppStore_Bulk_Upgrader_Skin extends WP_Upgrader_Skin {
	var $in_loop = false;
	var $error = false;

	function __construct($args = array()) {
		$defaults = array( 'url' => '', 'nonce' => '' );
		$args = wp_parse_args($args, $defaults);

		parent::__construct($args);
	}

	function add_strings() {
		$this->upgrader->strings['skin_upgrade_start'] = __('The install process is starting. This process may take a while on some hosts, so please be patient.');
		$this->upgrader->strings['skin_update_failed_error'] = __('An error occurred while installing %1$s: <strong>%2$s</strong>.');
		$this->upgrader->strings['skin_update_failed'] = __('The install of %1$s failed.');
		$this->upgrader->strings['skin_update_successful'] = __('%1$s installed successfully.').' <a onclick="%2$s" href="#" class="hide-if-no-js"><span>'.__('Show Details').'</span><span class="hidden">'.__('Hide Details').'</span>.</a>';
		$this->upgrader->strings['skin_upgrade_end'] = __('All installations have been completed.');
	}

	function feedback($string) {
		if ( isset( $this->upgrader->strings[$string] ) )
			$string = $this->upgrader->strings[$string];

		if ( strpos($string, '%') !== false ) {
			$args = func_get_args();
			$args = array_splice($args, 1);
			if ( !empty($args) )
				$string = vsprintf($string, $args);
		}
		if ( empty($string) )
			return;
		if ( $this->in_loop )
			echo "$string<br />\n";
		else
			echo "<p>$string</p>\n";
	}

	function header() {
		// Nothing, This will be displayed within a iframe.
	}

	function footer() {
		// Nothing, This will be displayed within a iframe.
	}
	function error($error) {
		if ( is_string($error) && isset( $this->upgrader->strings[$error] ) )
			$this->error = $this->upgrader->strings[$error];

		if ( is_wp_error($error) ) {
			foreach ( $error->get_error_messages() as $emessage ) {
				if ( $error->get_error_data() )
					$messages[] = $emessage . ' ' . $error->get_error_data();
				else
					$messages[] = $emessage;
			}
			$this->error = implode(', ', $messages);
		}
		echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js($this->upgrader->update_current) . '\').hide();</script>';
	}

	function bulk_header() {
		$this->feedback('skin_upgrade_start');
	}

	function bulk_footer() {
		$this->feedback('skin_upgrade_end');
	}

	function before($title = '') {
		$this->in_loop = true;
		printf( '<h4>' . $this->upgrader->strings['skin_before_update_header'] . ' <img alt="" src="' . admin_url( 'images/wpspin_light.gif' ) . '" class="hidden waiting-' . $this->upgrader->update_current . '" style="vertical-align:middle;" /></h4>',  $title, $this->upgrader->update_current, $this->upgrader->update_count);
		echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js($this->upgrader->update_current) . '\').show();</script>';
		echo '<div class="update-messages hide-if-js" id="progress-' . esc_attr($this->upgrader->update_current) . '"><p>';
		$this->flush_output();
	}

	function after($title = '') {
		echo '</p></div>';
		if ( $this->error || ! $this->result ) {
			if ( $this->error )
				echo '<div class="error"><p>' . sprintf($this->upgrader->strings['skin_update_failed_error'], $title, $this->error) . '</p></div>';
			else
				echo '<div class="error"><p>' . sprintf($this->upgrader->strings['skin_update_failed'], $title) . '</p></div>';

			echo '<script type="text/javascript">jQuery(\'#progress-' . esc_js($this->upgrader->update_current) . '\').show();</script>';
		}
		if ( !empty($this->result) && !is_wp_error($this->result) ) {
			echo '<div class="updated"><p>' . sprintf($this->upgrader->strings['skin_update_successful'], $title, 'jQuery(\'#progress-' . esc_js($this->upgrader->update_current) . '\').toggle();jQuery(\'span\', this).toggle(); return false;') . '</p></div>';
			echo '<script type="text/javascript">jQuery(\'.waiting-' . esc_js($this->upgrader->update_current) . '\').hide();</script>';
		}

		$this->reset();
		$this->flush_output();
	}

	function reset() {
		$this->in_loop = false;
		$this->error = false;
	}

	function flush_output() {
		wp_ob_end_flush_all();
		flush();
	}
}


class WP_AppStore_Bundle_Installer_Skin extends WP_AppStore_Bulk_Upgrader_Skin {
	var $package_info = array(); // Plugin_Upgrader::bulk() will fill this in.

	function __construct($args = array()) {
		parent::__construct($args);
	}

	function add_strings() {
		parent::add_strings();
		$this->upgrader->strings['skin_before_update_header'] = __('Installing Package %1$s (%2$d/%3$d)');
	}

	function before() {
		parent::before($this->package_info['Title']);
	}

	function after() {
		parent::after($this->package_info['Title']);
	}
	function bulk_footer() {
		parent::bulk_footer();
		$install_actions =  array(
            'activate_plugins' => '<a href="' . admin_url('admin.php') . '?page=wp-appstore.php&screen=activate-bundle&bundle='.$this->options['bundle_slug'].'" title="' . esc_attr__('Activate Plugins') . '" target="_parent">' . __('Activate Plugins') . '</a>',
			'plugins_page' => '<a href="' . self_admin_url('plugins.php') . '" title="' . esc_attr__('Go to plugins page') . '" target="_parent">' . __('Return to Plugins page') . '</a>',
			'themes_page' => '<a href="' . self_admin_url('themes.php') . '" title="' . esc_attr__('Themes page') . '" target="_parent">' . __('Return to Themes page') . '</a>',
            'return_to_appstore' => '<a href="' . admin_url('admin.php') . '?page=wp-appstore.php" title="' . esc_attr__('Return to WP AppStore') . '" target="_parent">' . __('Return to WP AppStore') . '</a>',
		);
		$install_actions = apply_filters('update_bulk_plugins_complete_actions', $install_actions, $this->package_info);
		if ( ! empty($install_actions) )
			$this->feedback(implode(' | ', (array)$install_actions));
	}
}

/**
 * Plugin Upgrader class for WordPress Plugins, It is designed to upgrade/install plugins from a local zip, remote zip URL, or uploaded zip file.
 *
 * @TODO More Detailed docs, for methods as well.
 *
 * @package WordPress
 * @subpackage Upgrader
 * @since 2.8.0
 */
class WP_AppStore_Extension_Upgrader extends WP_Upgrader {

	var $result;
	var $bulk = false;
	var $show_before = '';

	function plugin_upgrade_strings() {
		$this->strings['up_to_date'] = __('The plugin is at the latest version.');
		$this->strings['no_package'] = __('Update package not available.');
		$this->strings['downloading_package'] = __('Downloading update from <span class="code">%s</span>&#8230;');
		$this->strings['unpack_package'] = __('Unpacking the update&#8230;');
		$this->strings['deactivate_plugin'] = __('Deactivating the plugin&#8230;');
		$this->strings['remove_old'] = __('Removing the old version of the plugin&#8230;');
		$this->strings['remove_old_failed'] = __('Could not remove the old plugin.');
		$this->strings['process_failed'] = __('Plugin update failed.');
		$this->strings['process_success'] = __('Plugin updated successfully.');
	}

	function plugin_install_strings() {
		$this->strings['no_package'] = __('Install package not available.');
		$this->strings['downloading_package'] = __('Downloading install package from <span class="code">%s</span>&#8230;');
		$this->strings['unpack_package'] = __('Unpacking the package&#8230;');
		$this->strings['installing_package'] = __('Installing the plugin&#8230;');
		$this->strings['process_failed'] = __('Plugin install failed.');
		$this->strings['process_success'] = __('Plugin installed successfully.');
	}
    
    function theme_upgrade_strings() {
		$this->strings['up_to_date'] = __('The theme is at the latest version.');
		$this->strings['no_package'] = __('Update package not available.');
		$this->strings['downloading_package'] = __('Downloading update from <span class="code">%s</span>&#8230;');
		$this->strings['unpack_package'] = __('Unpacking the update&#8230;');
		$this->strings['remove_old'] = __('Removing the old version of the theme&#8230;');
		$this->strings['remove_old_failed'] = __('Could not remove the old theme.');
		$this->strings['process_failed'] = __('Theme update failed.');
		$this->strings['process_success'] = __('Theme updated successfully.');
	}

	function theme_install_strings() {
		$this->strings['no_package'] = __('Install package not available.');
		$this->strings['downloading_package'] = __('Downloading install package from <span class="code">%s</span>&#8230;');
		$this->strings['unpack_package'] = __('Unpacking the package&#8230;');
		$this->strings['installing_package'] = __('Installing the theme&#8230;');
		$this->strings['process_failed'] = __('Theme install failed.');
		$this->strings['process_success'] = __('Theme installed successfully.');
	}
    
   	function run($options) {

		$defaults = array( 	'package' => '', //Please always pass this.
							'destination' => '', //And this
							'clear_destination' => false,
							'clear_working' => true,
							'is_multi' => false,
                            'source_slug' => '', //for non wp download source - make shure that package have a correct names
							'hook_extra' => array() //Pass any extra $hook_extra args here, this will be passed to any hooked filters.
						);

		$options = wp_parse_args($options, $defaults);
		extract($options);

		//Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR, $destination) );
		if ( ! $res ) //Mainly for non-connected filesystem.
			return false;

		if ( is_wp_error($res) ) {
			$this->skin->error($res);
			return $res;
		}

		if ( !$is_multi ) // call $this->header separately if running multiple times
			$this->skin->header();

		$this->skin->before();

		//Download the package (Note, This just returns the filename of the file if the package is a local file)
		$download = $this->download_package( $package );
		if ( is_wp_error($download) ) {
			$this->skin->error($download);
			$this->skin->after();
			return $download;
		}
        //after download we should sanitize folder names
        if ($source_slug) {
        $zip = new ZipArchive;
        $res = $zip->open( $download );
        if ($res === TRUE) {
            $folder = rtrim($source_slug, '/\\').'/';
            $root_element = $zip->statIndex(0);
            $search = $root_element['name'];
            if ($folder == $search || $root_element['size'] > 0) {
                $zip->close();
            }else{
                for($i = 0; $i < $zip->numFiles; $i++)
                 {  
                    $new_name = str_replace($search, $folder, $zip->getNameIndex($i));
                    $zip->renameIndex($i, $new_name);
                 } 
                $zip->close();
            }
        } else {
    			return new WP_Error('no_package', $this->strings['no_package']);
        }
        }
        
        //end
		$delete_package = ($download != $package); // Do not delete a "local" file

		//Unzips the file into a temporary directory
		$working_dir = $this->unpack_package( $download, $delete_package );
		if ( is_wp_error($working_dir) ) {
			$this->skin->error($working_dir);
			$this->skin->after();
			return $working_dir;
		}

		//With the given options, this installs it to the destination directory.
		$result = $this->install_package( array(
											'source' => $working_dir,
											'destination' => $destination,
											'clear_destination' => $clear_destination,
											'clear_working' => $clear_working,
											'hook_extra' => $hook_extra
										) );
		$this->skin->set_result($result);
		if ( is_wp_error($result) ) {
			$this->skin->error($result);
			$this->skin->feedback('process_failed');
		} else {
			//Install Succeeded
			$this->skin->feedback('process_success');
		}
		$this->skin->after();

		if ( !$is_multi )
			$this->skin->footer();

		return $result;
	}

	function plugin_install($package, $plugin_slug = '') {

		$this->init();
		$this->plugin_install_strings();

		add_filter('upgrader_source_selection', array(&$this, 'plugin_check_package') );

		$this->run(array(
					'package' => $package,
					'destination' => WP_PLUGIN_DIR,
					'clear_destination' => false, //Do not overwrite files.
					'clear_working' => true,
                    'source_slug' => $plugin_slug, 
					'hook_extra' => array()
					));

		remove_filter('upgrader_source_selection', array(&$this, 'plugin_check_package') );

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Force refresh of plugin update information
		delete_site_transient('update_plugins');

		return true;
	}
    
    function theme_install($package, $theme_slug ='') {

		$this->init();
		$this->theme_install_strings();

		add_filter('upgrader_source_selection', array(&$this, 'theme_check_package') );

		$options = array(
						'package' => $package,
						'destination' => WP_CONTENT_DIR . '/themes',
						'clear_destination' => false, //Do not overwrite files.
                        'source_slug' => $theme_slug,
						'clear_working' => true
						);

		$this->run($options);

		remove_filter('upgrader_source_selection', array(&$this, 'theme_check_package') );

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Force refresh of theme update information
		delete_site_transient('update_themes');

		return true;
	}

	function plugin_upgrade($plugin) {

		$this->init();
		$this->plugin_upgrade_strings();

		$current = get_option('wp_appstore_plugins_for_update');
		if ( !isset( $current[$plugin] ) ) {
			$this->skin->before();
			$this->skin->set_result(false);
			$this->skin->error('up_to_date');
			$this->skin->after();
			return false;
		}
        $update_data = $current[$plugin];
        $plugin = $update_data['file'];
		// Get the URL to the zip file
		$r = $update_data['object'];

		add_filter('upgrader_pre_install', array(&$this, 'deactivate_plugin_before_upgrade'), 10, 2);
		add_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'), 10, 4);

		$this->run(array(
					'package' => $r->package,
					'destination' => WP_PLUGIN_DIR,
					'clear_destination' => true,
					'clear_working' => true,
                    'source_slug' => $r->slug, 
					'hook_extra' => array(
								'plugin' => $plugin
					)
				));

		// Cleanup our hooks, in case something else does a upgrade on this connection.
		remove_filter('upgrader_pre_install', array(&$this, 'deactivate_plugin_before_upgrade'));
		remove_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'));

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Force refresh of plugin update information
		delete_site_transient('update_plugins');
        unset($current[$r->slug]);
        update_option('wp_appstore_plugins_for_update', $current);
	}
    
    function theme_upgrade($theme) {

		$this->init();
		$this->theme_upgrade_strings();

		// Is an update available?
	    $current = get_option('wp_appstore_themes_for_update');
		if ( !isset( $current[$theme] ) ) {
			$this->skin->before();
			$this->skin->set_result(false);
			$this->skin->error('up_to_date');
			$this->skin->after();
			return false;
		}

		$r = $current[$theme];

		add_filter('upgrader_pre_install', array(&$this, 'current_before'), 10, 2);
		add_filter('upgrader_post_install', array(&$this, 'current_after'), 10, 2);
		add_filter('upgrader_clear_destination', array(&$this, 'delete_old_theme'), 10, 4);

		$options = array(
						'package' => $r['package'],
						'destination' => WP_CONTENT_DIR . '/themes',
						'clear_destination' => true,
						'clear_working' => true,
                        'source_slug' => $theme, 
						'hook_extra' => array(
											'theme' => $theme
											)
						);

		$this->run($options);

		remove_filter('upgrader_pre_install', array(&$this, 'current_before'), 10, 2);
		remove_filter('upgrader_post_install', array(&$this, 'current_after'), 10, 2);
		remove_filter('upgrader_clear_destination', array(&$this, 'delete_old_theme'), 10, 4);

		if ( ! $this->result || is_wp_error($this->result) )
			return $this->result;

		// Force refresh of theme update information
		delete_site_transient('update_themes');
        unset($current[$theme]);
        update_option('wp_appstore_themes_for_update', $current);
		return true;
	}

	function plugin_bulk_upgrade($plugins) {

		$this->init();
		$this->bulk = true;
		$this->plugin_upgrade_strings();

		$current = get_site_transient( 'update_plugins' );

		add_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'), 10, 4);

		$this->skin->header();

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR, WP_PLUGIN_DIR) );
		if ( ! $res ) {
			$this->skin->footer();
			return false;
		}

		$this->skin->bulk_header();

		// Only start maintenance mode if running in Multisite OR the plugin is in use
		$maintenance = is_multisite(); // @TODO: This should only kick in for individual sites if at all possible.
		foreach ( $plugins as $plugin )
			$maintenance = $maintenance || (is_plugin_active($plugin) && isset($current->response[ $plugin ]) ); // Only activate Maintenance mode if a plugin is active AND has an update available
		if ( $maintenance )
			$this->maintenance_mode(true);

		$results = array();

		$this->update_count = count($plugins);
		$this->update_current = 0;
		foreach ( $plugins as $plugin ) {
			$this->update_current++;
			$this->skin->plugin_info = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin, false, true);

			if ( !isset( $current->response[ $plugin ] ) ) {
				$this->skin->set_result(false);
				$this->skin->before();
				$this->skin->error('up_to_date');
				$this->skin->after();
				$results[$plugin] = false;
				continue;
			}

			// Get the URL to the zip file
			$r = $current->response[ $plugin ];

			$this->skin->plugin_active = is_plugin_active($plugin);

			$result = $this->run(array(
						'package' => $r->package,
						'destination' => WP_PLUGIN_DIR,
						'clear_destination' => true,
						'clear_working' => true,
                        'source_slug' => $r->slug, 
						'is_multi' => true,
						'hook_extra' => array(
									'plugin' => $plugin
						)
					));

			$results[$plugin] = $this->result;

			// Prevent credentials auth screen from displaying multiple times
			if ( false === $result )
				break;
		} //end foreach $plugins

		$this->maintenance_mode(false);

		$this->skin->bulk_footer();

		$this->skin->footer();

		// Cleanup our hooks, in case something else does a upgrade on this connection.
		remove_filter('upgrader_clear_destination', array(&$this, 'delete_old_plugin'));

		// Force refresh of plugin update information
		delete_site_transient('update_plugins');

		return $results;
	}
    
    function count_extensions_to_install($bundle) {
        $count = 0;
        if (is_array($bundle['plugins'])) {
            foreach ($bundle['plugins'] as $plugin) {
                if($plugin['installed'] == false)
                    $count++;
            }
        }
        if (is_array($bundle['themes'])) {
            foreach ($bundle['themes'] as $theme) {
                if($theme['installed'] == false)
                    $count++;
            }
        }
        return $count;
    }    
        
	function bundle_install($bundle) {
        //import bundle slug
		$this->init();
		$this->bulk = true;
        //lets start with plugins
		$this->plugin_install_strings();
        $bundles = get_site_transient( 'wp_appstore_bundles' );
        if(!isset($bundles[$bundle])){
            $this->skin->set_result(false);
				$this->skin->before();
				$this->skin->error('process_failed');
				$this->skin->after();
                return false;
        }
  
		$this->skin->header();

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR, WP_PLUGIN_DIR) );
		if ( ! $res ) {
			$this->skin->footer();
			return false;
		}

		$this->skin->bulk_header();

		// Only start maintenance mode if running in Multisite OR the plugin is in use
		$maintenance = is_multisite(); // @TODO: This should only kick in for individual sites if at all possible.
		if ( $maintenance )
			$this->maintenance_mode(true);

		$results = array();

		$this->update_count = $this->count_extensions_to_install($bundles[$bundle]);
        
		$this->update_current = 0;
        if (is_array($bundles[$bundle]['plugins'])) {
    		foreach ( $bundles[$bundle]['plugins'] as $plugin_slug => $plugin ) {
    		      if($plugin['installed'])
                    continue;
    			$this->update_current++;
    			$this->skin->package_info['Title'] = $plugin['title'];
    
                add_filter('upgrader_source_selection', array(&$this, 'plugin_check_package') );
    
        		$this->run(array(
        					'package' => $plugin['link'],
        					'destination' => WP_PLUGIN_DIR,
        					'clear_destination' => false, //Do not overwrite files.
        					'clear_working' => true,
                            'clear_working' => true,
                            'source_slug' => $plugin_slug,
        					'hook_extra' => array()
        					));
                remove_filter('upgrader_source_selection', array(&$this, 'plugin_check_package') );
    			$results[$plugin_slug] = $this->result;
    
    			// Prevent credentials auth screen from displaying multiple times
    			if ( false === $result )
    				break;
    		} //end foreach $plugins
        }
        //lets start with themes
        if (is_array($bundles[$bundle]['themes'])) {
           	$this->theme_install_strings();
    		foreach ( $bundles[$bundle]['themes'] as $theme_slug => $theme ) {
                if($theme['installed'])
                    continue;
    			$this->update_current++;
    
    			$this->skin->package_info['Title'] = $theme['title'];
    
    			// Get the URL to the zip file
    			add_filter('upgrader_source_selection', array(&$this, 'theme_check_package') );
    
        		$options = array(
        						'package' => $theme['link'],
        						'destination' => WP_CONTENT_DIR . '/themes',
        						'clear_destination' => false, //Do not overwrite files.
                                'source_slug' => $theme_slug,
        						'clear_working' => true
        						);
        
        		$this->run($options);
        
        		remove_filter('upgrader_source_selection', array(&$this, 'theme_check_package') );
    
    			$results[$theme_slug] = $this->result;
    
    			// Prevent credentials auth screen from displaying multiple times
    			if ( false === $result )
    				break;
    		} //end foreach themes
        }
        
        
        
		$this->maintenance_mode(false);

		$this->skin->bulk_footer();

		$this->skin->footer();

		// Force refresh of plugin update information
		delete_site_transient('update_plugins');
        delete_site_transient('update_themes');
		return $results;
	}
        
    
    	function plugin_bulk_install($bundle) {
        //import bundle slug
		$this->init();
		$this->bulk = true;
		$this->plugin_install_strings();
        $bundles = get_site_transient( 'wp_appstore_bundles' );
        
        if(!isset($bundles[$bundle])){
            $this->skin->set_result(false);
				$this->skin->before();
				$this->skin->error('process_failed');
				$this->skin->after();
                return false;
        }
  
		$this->skin->header();

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR, WP_PLUGIN_DIR) );
		if ( ! $res ) {
			$this->skin->footer();
			return false;
		}

		$this->skin->bulk_header();

		// Only start maintenance mode if running in Multisite OR the plugin is in use
		$maintenance = is_multisite(); // @TODO: This should only kick in for individual sites if at all possible.
		if ( $maintenance )
			$this->maintenance_mode(true);

		$results = array();

		$this->install_count = count($plugins);
		$this->install_current = 0;
		foreach ( $bundles[$bundle]['plugins'] as $plugin_slug => $plugin ) {
			$this->install_current++;
			$this->skin->plugin_info['Title'] = $plugin['title'];

            add_filter('upgrader_source_selection', array(&$this, 'plugin_check_package') );

    		$this->run(array(
    					'package' => $plugin['link'],
    					'destination' => WP_PLUGIN_DIR,
    					'clear_destination' => false, //Do not overwrite files.
    					'clear_working' => true,
                        'clear_working' => true,
                        'source_slug' => $plugin_slug,
    					'hook_extra' => array()
    					));
            remove_filter('upgrader_source_selection', array(&$this, 'plugin_check_package') );
			$results[$plugin_slug] = $this->result;

			// Prevent credentials auth screen from displaying multiple times
			if ( false === $result )
				break;
		} //end foreach $plugins

		$this->maintenance_mode(false);

		$this->skin->bulk_footer();

		$this->skin->footer();

		// Force refresh of plugin update information
		delete_site_transient('update_plugins');

		return $results;
	}

	function plugin_check_package($source) {
		global $wp_filesystem;

		if ( is_wp_error($source) )
			return $source;

		$working_directory = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit(WP_CONTENT_DIR), $source);
		if ( ! is_dir($working_directory) ) // Sanity check, if the above fails, lets not prevent installation.
			return $source;

		// Check the folder contains at least 1 valid plugin.
		$plugins_found = false;
		foreach ( glob( $working_directory . '*.php' ) as $file ) {
			$info = get_plugin_data($file, false, false);
			if ( !empty( $info['Name'] ) ) {
				$plugins_found = true;
				break;
			}
		}

		if ( ! $plugins_found )
			return new WP_Error( 'incompatible_archive', $this->strings['incompatible_archive'], __('No valid plugins were found.') );

		return $source;
	}

	//return plugin info.
	function plugin_info() {
		if ( ! is_array($this->result) )
			return false;
		if ( empty($this->result['destination_name']) )
			return false;

		$plugin = get_plugins('/' . $this->result['destination_name']); //Ensure to pass with leading slash
		if ( empty($plugin) )
			return false;

		$pluginfiles = array_keys($plugin); //Assume the requested plugin is the first in the list

		return $this->result['destination_name'] . '/' . $pluginfiles[0];
	}

	//Hooked to pre_install
	function deactivate_plugin_before_upgrade($return, $plugin) {

		if ( is_wp_error($return) ) //Bypass.
			return $return;

		$plugin = isset($plugin['plugin']) ? $plugin['plugin'] : '';
		if ( empty($plugin) )
			return new WP_Error('bad_request', $this->strings['bad_request']);

		if ( is_plugin_active($plugin) ) {
			$this->skin->feedback('deactivate_plugin');
			//Deactivate the plugin silently, Prevent deactivation hooks from running.
			deactivate_plugins($plugin, true);
		}
	}

	//Hooked to upgrade_clear_destination
	function delete_old_plugin($removed, $local_destination, $remote_destination, $plugin) {
		global $wp_filesystem;

		if ( is_wp_error($removed) )
			return $removed; //Pass errors through.

		$plugin = isset($plugin['plugin']) ? $plugin['plugin'] : '';
		if ( empty($plugin) )
			return new WP_Error('bad_request', $this->strings['bad_request']);

		$plugins_dir = $wp_filesystem->wp_plugins_dir();
		$this_plugin_dir = trailingslashit( dirname($plugins_dir . $plugin) );

		if ( ! $wp_filesystem->exists($this_plugin_dir) ) //If its already vanished.
			return $removed;

		// If plugin is in its own directory, recursively delete the directory.
		if ( strpos($plugin, '/') && $this_plugin_dir != $plugins_dir ) //base check on if plugin includes directory separator AND that its not the root plugin folder
			$deleted = $wp_filesystem->delete($this_plugin_dir, true);
		else
			$deleted = $wp_filesystem->delete($plugins_dir . $plugin);

		if ( ! $deleted )
			return new WP_Error('remove_old_failed', $this->strings['remove_old_failed']);

		return true;
	}
    
        function theme_bulk_install($bundle) {

		$this->init();
		$this->bulk = true;
		$this->theme_install_strings();

		$bundles = get_site_transient( 'wp_appstore_bundles' );
        
        if(!isset($bundles[$bundle])){
            $this->skin->set_result(false);
				$this->skin->before();
				$this->skin->error('process_failed');
				$this->skin->after();
                return false;
        }

		$this->skin->header();

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR) );
		if ( ! $res ) {
			$this->skin->footer();
			return false;
		}

		$this->skin->bulk_header();

		// Only start maintenance mode if running in Multisite OR the theme is in use
		$maintenance = is_multisite(); // @TODO: This should only kick in for individual sites if at all possible.
		if ( $maintenance )
			$this->maintenance_mode(true);

		$results = array();
        

		$this->install_count = count($themes);
		$this->install_current = 0;
		foreach ( $bundles[$bundle]['themes'] as $theme_slug => $theme ) {
			$this->install_current++;

			$this->skin->theme_info['Title'] = $theme['title'];

			// Get the URL to the zip file
			add_filter('upgrader_source_selection', array(&$this, 'theme_check_package') );

    		$options = array(
    						'package' => $theme['link'],
    						'destination' => WP_CONTENT_DIR . '/themes',
    						'clear_destination' => false, //Do not overwrite files.
                            'source_slug' => $theme_slug,
    						'clear_working' => true
    						);
    
    		$this->run($options);
    
    		remove_filter('upgrader_source_selection', array(&$this, 'theme_check_package') );

			$results[$theme_slug] = $this->result;

			// Prevent credentials auth screen from displaying multiple times
			if ( false === $result )
				break;
		} //end foreach themes

		$this->maintenance_mode(false);

		$this->skin->bulk_footer();

		$this->skin->footer();

		// Force refresh of theme update information
		delete_site_transient('update_themes');

		return $results;
	}
    
    function theme_bulk_upgrade($themes) {

		$this->init();
		$this->bulk = true;
		$this->theme_upgrade_strings();

		$current = get_site_transient( 'update_themes' );

		add_filter('upgrader_pre_install', array(&$this, 'current_before'), 10, 2);
		add_filter('upgrader_post_install', array(&$this, 'current_after'), 10, 2);
		add_filter('upgrader_clear_destination', array(&$this, 'delete_old_theme'), 10, 4);

		$this->skin->header();

		// Connect to the Filesystem first.
		$res = $this->fs_connect( array(WP_CONTENT_DIR) );
		if ( ! $res ) {
			$this->skin->footer();
			return false;
		}

		$this->skin->bulk_header();

		// Only start maintenance mode if running in Multisite OR the theme is in use
		$maintenance = is_multisite(); // @TODO: This should only kick in for individual sites if at all possible.
		foreach ( $themes as $theme )
			$maintenance = $maintenance || $theme == get_stylesheet() || $theme == get_template();
		if ( $maintenance )
			$this->maintenance_mode(true);

		$results = array();

		$this->update_count = count($themes);
		$this->update_current = 0;
		foreach ( $themes as $theme ) {
			$this->update_current++;

			if ( !isset( $current->response[ $theme ] ) ) {
				$this->skin->set_result(false);
				$this->skin->before();
				$this->skin->error('up_to_date');
				$this->skin->after();
				$results[$theme] = false;
				continue;
			}

			$this->skin->theme_info = $this->theme_info($theme);

			// Get the URL to the zip file
			$r = $current->response[ $theme ];

			$options = array(
							'package' => $r['package'],
							'destination' => WP_CONTENT_DIR . '/themes',
							'clear_destination' => true,
							'clear_working' => true,
                            'source_slug' => $r->slug, 
							'hook_extra' => array(
												'theme' => $theme
												)
							);

			$result = $this->run($options);

			$results[$theme] = $this->result;

			// Prevent credentials auth screen from displaying multiple times
			if ( false === $result )
				break;
		} //end foreach $plugins

		$this->maintenance_mode(false);

		$this->skin->bulk_footer();

		$this->skin->footer();

		// Cleanup our hooks, in case something else does a upgrade on this connection.
		remove_filter('upgrader_pre_install', array(&$this, 'current_before'), 10, 2);
		remove_filter('upgrader_post_install', array(&$this, 'current_after'), 10, 2);
		remove_filter('upgrader_clear_destination', array(&$this, 'delete_old_theme'), 10, 4);

		// Force refresh of theme update information
		delete_site_transient('update_themes');

		return $results;
	}

	function theme_check_package($source) {
		global $wp_filesystem;

		if ( is_wp_error($source) )
			return $source;

		// Check the folder contains a valid theme
		$working_directory = str_replace( $wp_filesystem->wp_content_dir(), trailingslashit(WP_CONTENT_DIR), $source);
		if ( ! is_dir($working_directory) ) // Sanity check, if the above fails, lets not prevent installation.
			return $source;

		if ( ! file_exists( $working_directory . 'style.css' ) ) // A proper archive should have a style.css file in the single subdirectory
			return new WP_Error( 'incompatible_archive', $this->strings['incompatible_archive'], __('The theme is missing the <code>style.css</code> stylesheet.') );

		$info = get_theme_data( $working_directory . 'style.css' );
		if ( empty($info['Name']) )
			return new WP_Error( 'incompatible_archive', $this->strings['incompatible_archive'], __("The <code>style.css</code> stylesheet doesn't contain a valid theme header.") );

		if ( empty($info['Template']) && ! file_exists( $working_directory . 'index.php' ) ) // If no template is set, it must have at least an index.php to be legit.
			return new WP_Error( 'incompatible_archive', $this->strings['incompatible_archive'], __('The theme is missing the <code>index.php</code> file.') );

		return $source;
	}

	function current_before($return, $theme) {

		if ( is_wp_error($return) )
			return $return;

		$theme = isset($theme['theme']) ? $theme['theme'] : '';

		if ( $theme != get_stylesheet() ) //If not current
			return $return;
		//Change to maintenance mode now.
		if ( ! $this->bulk )
			$this->maintenance_mode(true);

		return $return;
	}

	function current_after($return, $theme) {
		if ( is_wp_error($return) )
			return $return;

		$theme = isset($theme['theme']) ? $theme['theme'] : '';

		if ( $theme != get_stylesheet() ) //If not current
			return $return;

		//Ensure stylesheet name hasnt changed after the upgrade:
		// @TODO: Note, This doesn't handle the Template changing, or the Template name changing.
		if ( $theme == get_stylesheet() && $theme != $this->result['destination_name'] ) {
			$theme_info = $this->theme_info();
			$stylesheet = $this->result['destination_name'];
			$template = !empty($theme_info['Template']) ? $theme_info['Template'] : $stylesheet;
			switch_theme($template, $stylesheet, true);
		}

		//Time to remove maintenance mode
		if ( ! $this->bulk )
			$this->maintenance_mode(false);
		return $return;
	}

	function delete_old_theme($removed, $local_destination, $remote_destination, $theme) {
		global $wp_filesystem;

		$theme = isset($theme['theme']) ? $theme['theme'] : '';

		if ( is_wp_error($removed) || empty($theme) )
			return $removed; //Pass errors through.

		$themes_dir = $wp_filesystem->wp_themes_dir();
		if ( $wp_filesystem->exists( trailingslashit($themes_dir) . $theme ) )
			if ( ! $wp_filesystem->delete( trailingslashit($themes_dir) . $theme, true ) )
				return false;
		return true;
	}

	function theme_info($theme = null) {

		if ( empty($theme) ) {
			if ( !empty($this->result['destination_name']) )
				$theme = $this->result['destination_name'];
			else
				return false;
		}
		return get_theme_data(WP_CONTENT_DIR . '/themes/' . $theme . '/style.css');
	}
    
}
?>