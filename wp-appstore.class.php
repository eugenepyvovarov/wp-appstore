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

if ( !function_exists('json_decode') ){
function json_decode($json)
{
    $comment = false;
    $out = '$x=';
  
    for ($i=0; $i<strlen($json); $i++)
    {
        if (!$comment)
        {
            if (($json[$i] == '{') || ($json[$i] == '['))       $out .= ' array(';
            else if (($json[$i] == '}') || ($json[$i] == ']'))   $out .= ')';
            else if ($json[$i] == ':')    $out .= '=>';
            else                         $out .= $json[$i];          
        }
        else $out .= $json[$i];
        if ($json[$i] == '"' && $json[($i-1)]!="\\")    $comment = !$comment;
    }
    eval($out . ';');
    return $x;
}
}
if ( !function_exists('json_encode') ){
function json_encode( $data ) {            
    if( is_array($data) || is_object($data) ) { 
        $islist = is_array($data) && ( empty($data) || array_keys($data) === range(0,count($data)-1) ); 
        
        if( $islist ) { 
            $json = '[' . implode(',', array_map('__json_encode', $data) ) . ']'; 
        } else { 
            $items = Array(); 
            foreach( $data as $key => $value ) { 
                $items[] = __json_encode("$key") . ':' . __json_encode($value); 
            } 
            $json = '{' . implode(',', $items) . '}'; 
        } 
    } elseif( is_string($data) ) { 
        # Escape non-printable or Non-ASCII characters. 
        # I also put the \\ character first, as suggested in comments on the 'addclashes' page. 
        $string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"'; 
        $json    = ''; 
        $len    = strlen($string); 
        # Convert UTF-8 to Hexadecimal Codepoints. 
        for( $i = 0; $i < $len; $i++ ) { 
            
            $char = $string[$i]; 
            $c1 = ord($char); 
            
            # Single byte; 
            if( $c1 <128 ) { 
                $json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1); 
                continue; 
            } 
            
            # Double byte 
            $c2 = ord($string[++$i]); 
            if ( ($c1 & 32) === 0 ) { 
                $json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128); 
                continue; 
            } 
            
            # Triple 
            $c3 = ord($string[++$i]); 
            if( ($c1 & 16) === 0 ) { 
                $json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128)); 
                continue; 
            } 
                
            # Quadruple 
            $c4 = ord($string[++$i]); 
            if( ($c1 & 8 ) === 0 ) { 
                $u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1; 
            
                $w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3); 
                $w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128); 
                $json .= sprintf("\\u%04x\\u%04x", $w1, $w2); 
            } 
        } 
    } else { 
        # int, floats, bools, null 
        $json = strtolower(var_export( $data, true )); 
    } 
    return $json; 
}
}
class WP_AppStore{
    public $installed_plugins;
    public $installed_themes;
    public $ini_files;
    public $formulas;
    public $dir;
    
    function WP_AppStore(){
        $this->installed_plugins = $this->get_installed_plugins();
        $this->get_installed_themes();
        $this->set_formulas();
    }
    function set_formulas(){
        $this->get_formulas_from_db();
        if ( (get_option('wp_appstore_formulas_rescan')) || (!$this->formulas) ) {
            $this->dir = rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR . 'formulas';
            $this->ini_files = array();
            $this->recurse_directory($this->dir);
            $this->store();
            $this->check_for_plugins_updates();
            $this->check_for_themes_updates();
       }
    }
    function get_installed_plugins() {
    	$dir = WP_PLUGIN_DIR;
		$plugins_dir = @ opendir( $dir );
		if ( $plugins_dir ) {
			while (($file = readdir( $plugins_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				$plugin_files[] = plugin_basename("$dir/$file");
			}
			@closedir( $plugins_dir );
		}

    	return $plugin_files;
    }
    function get_installed_themes() {
        
        $this->installed_themes = array();
        $themes = get_themes();
        foreach ($themes as $theme) {
            $this->installed_themes[] = $theme['Stylesheet'];
        }
    }
    public function recurse_directory( $dir ) {
		if ( $handle = @opendir( $dir ) ) {
			while ( false !== ( $file = readdir( $handle ) ) ) {
				if ( $file != '.' && $file != '..' ) {
					$file = $dir . DIRECTORY_SEPARATOR . $file;
					if ( is_dir( $file ) ) {
						$this->recurse_directory( $file );
					} elseif ( is_file( $file ) ) {
						$this->ini_files[] = $file;
					}
				}
			}
			closedir( $handle );
		}
	}
    public function check_for_themes_updates(){
        global $wpdb;
        $themes = get_themes();
        $for_update = get_option('wp_appstore_themes_for_update');
        $current = get_site_transient( 'update_themes' );
        
        if (!is_object($current)) {
            wp_update_themes();
            $current = get_site_transient( 'update_themes' );
        }
        foreach ($themes as $theme) {
            $query = $wpdb->prepare("SELECT `version`, `link`, `homepage`, `title` FROM ".$wpdb->prefix."appstore_themes WHERE slug LIKE %s", $theme['Stylesheet']);
            $stored_themes_result = $wpdb->get_results($query);
            if ($stored_themes_result) {
                $repo_ver = $this->str_to_float($stored_themes_result[0]->version);
                $curr_ver = $this->str_to_float($theme['Version']);
                $dump[$theme['Stylesheet']] = array($repo_ver, $curr_ver);
                if ($repo_ver > $curr_ver) {
                        $api = array();
                        $api['new_version'] = $stored_themes_result[0]->version;
                        $api['url'] = $stored_themes_result[0]->homepage;
                        $api['package'] = $stored_themes_result[0]->link;
                $for_update[$theme['Stylesheet']] = array('file' => $theme['Stylesheet'], 'object' => $api, 'title' => $stored_themes_result[0]->title);
                }
            }
        }
        update_option('wp_appstore_themes_for_update', $for_update);
    }
    
    function check_for_plugins_updates(){
        global $wpdb;
        
        $plugins = get_plugins();
        $for_update = get_option('wp_appstore_plugins_for_update');
        $current = get_site_transient( 'update_plugins' );
        if (!is_object($current)) {
            wp_update_plugins();
            $current = get_site_transient( 'update_plugins' );
        }
        
        foreach ($plugins as $key => $value) {
            $exploded_path = explode('/', $key);
            if(preg_match('|\.php$|', $exploded_path[0])){
                $ext = strrchr($exploded_path[0], '.'); 
                
                if($ext !== false) 
                    $exploded_path[0] = substr($exploded_path[0], 0, -strlen($ext));
            }
            $query = $wpdb->prepare("SELECT `title`, `version`, `slug`, `id`, `link`, `homepage`, `title` FROM ".$wpdb->prefix."appstore_plugins WHERE slug LIKE %s", $exploded_path[0]);
            $stored_plugins_result = $wpdb->get_results($query);
            if ($stored_plugins_result) {
                $repo_ver = $this->str_to_float($stored_plugins_result[0]->version);
                $curr_ver = $this->str_to_float($value['Version']);
                if ($repo_ver > $curr_ver) {
                        $api = new StdClass;
                        $api->id = $stored_plugins_result[0]->id;
                        $api->slug = $stored_plugins_result[0]->slug;
                        $api->new_version = $stored_plugins_result[0]->version;
                        $api->url = $stored_plugins_result[0]->homepage;
                        $api->package = $stored_plugins_result[0]->link;
                $for_update[$stored_plugins_result[0]->slug] = array('file' => $key, 'object' => $api, 'title' => $stored_plugins_result[0]->title);
                }
            }
        }
        update_option('wp_appstore_plugins_for_update', $for_update);
    }
    
    function convert_escaped_quotes($str){
        $str = str_replace("&#039;", "'", $str);
        $str = str_replace("&quot;", '"', $str);
        $str = str_replace('\[', "[", $str);
        $str = str_replace('\]', "]", $str);
        return $str;
    }
    
    function str_to_float($str){
        $version = explode('-', $str);
        $version = explode('.', $version[0]);
        $ver = $version[0].'.';
        array_shift($version);
        $ver = $ver . implode($version);
        return floatval($ver);
    }
    
    function check_compatibility($ver){
        global $wp_version;
        if (strlen ($ver) <= 1) {
            return false;
        }        
        
        $current = $this->str_to_float($wp_version);
        $requird = $this->str_to_float($ver);
        
        if ($current >= $requird) {
            return true;
        }else{
            return false;
        }
    }
    
    public function get_stats(){
        global $wpdb;
        $last_update = get_option('wp_appstore_last_lib_update');
        $stats['last_update'] = $last_update ? 'Last update: '.date('m / d / Y - G:i:s') : 'No updates since installation!';
        $sql = "SELECT COUNT(*) as count FROM ".$wpdb->prefix."appstore_plugins";
        $stats['plugins'] = $wpdb->get_results($sql, ARRAY_N);
        $stats['plugins'] = $stats['plugins'][0][0];
        $sql = "SELECT COUNT(*) as count FROM ".$wpdb->prefix."appstore_themes";
        $stats['themes'] = $wpdb->get_results($sql, ARRAY_N);
        $stats['themes'] = $stats['themes'][0][0];
        return $stats;
    }
    
    function get_formulas_from_db(){
        global $wpdb;
        
        $query = "SELECT * FROM ".$wpdb->prefix."appstore_plugins";
        $stored_plugins_result = $wpdb->get_results($query, ARRAY_A);
        foreach ($stored_plugins_result as $tmp) {
            $query = "SELECT `screenshot` FROM ".$wpdb->prefix."appstore_screenshots WHERE plugin_id =".$tmp['id'];
            $screenshots = $wpdb->get_results($query);
            if (sizeof($screenshots) > 0) {
                foreach ($screenshots as $ss) {
                    $tmp['screenshots'][] = $ss->screenshot;
                }
            }
            
            $query = "SELECT `tag` FROM ".$wpdb->prefix."appstore_plugins_tags WHERE plugin_id =".$tmp['id'];
            $tags = $wpdb->get_results($query);
            if (sizeof($tags)>0) {
                foreach ($tags as $tag) {
                    $tmp['tags'][] = $tag->tag;
                }
            }
            
            $this->formulas['plugin'][$tmp['id']] = (object)$tmp;
            unset($screenshots, $tags, $ss, $tag);
        }
        
        
        $query = "SELECT * FROM ".$wpdb->prefix."appstore_themes";
        $stored_themes_result = $wpdb->get_results($query, ARRAY_A);
        foreach ($stored_themes_result as $tmp) {
            $query = "SELECT `screenshot` FROM ".$wpdb->prefix."appstore_screenshots WHERE theme_id =".$tmp['id'];
            $screenshots = $wpdb->get_results($query);
            if (sizeof($screenshots) > 0) {
                foreach ($screenshots as $ss) {
                    $tmp['screenshots'][] = $ss->screenshot;
                }
            }
            $query = "SELECT `tag` FROM ".$wpdb->prefix."appstore_themes_tags WHERE theme_id =".$tmp['id'];
            $tags = $wpdb->get_results($query);
            if (sizeof($tags)>0) {
                foreach ($tags as $tag) {
                    $tmp['tags'][] = $tag->tag;
                }
            }
            
            $this->formulas['theme'][$tmp['id']] = (object)$tmp;
            unset($screenshots, $tags, $ss, $tag);
        } 
    }
//set formulas to db    
    function store(){
        global $wpdb;
        if (sizeof($this->ini_files) > 0) {
            //start
            //get existing extensions
            $query = "SELECT `id`, `slug` FROM ".$wpdb->prefix."appstore_plugins";
            $stored_plugins_result = $wpdb->get_results($query, ARRAY_A);
            foreach ($stored_plugins_result as $tmp) {
                $stored_plugins[$tmp['slug']] = $tmp['id'];
            }
            $query = "SELECT `id`, `slug` FROM ".$wpdb->prefix."appstore_themes";
            $stored_themes_result = $wpdb->get_results($query, ARRAY_A);
            foreach ($stored_themes_result as $tmp) {
                $stored_themes[$tmp['slug']] = $tmp['id'];
            }
            include_once WP_PLUGIN_DIR."/wp-appstore/tools/markdown.php";
            //start ini files loop
            foreach ($this->ini_files as $file) {
                if (preg_match('|\.ini$|', $file)) {
                    $tm = parse_ini_file($file);
                    if ( ($tm['type'] == 'theme') || ($this->check_compatibility($tm['requires']))) {
                        $tm['description'] = Markdown($this->convert_escaped_quotes($tm['description']));
                        $tm['author'] = $this->convert_escaped_quotes($tm['author']);
                        
                        $tags = $tm['tags'];
                        unset($tm['tags']);
                        $type = $tm['type'];
                        $screenshots = array();
                        if (isset($tm['screenshots'])) {
                            $screenshots = $tm['screenshots'];
                        }
                        unset($tm['screenshots']);
                        
//Insert new themes                        
                        if (($tm['type'] == 'theme') && (!isset($stored_themes[$tm['slug']]))) {
                            unset($tm['type']);
                            $mold = array('title'=>'', 'slug'=>'', 'description'=>'', 'author'=>'', 'version'=>'', 'updated'=>'', 'category_slug'=>'', 'category_name'=>'', 'link'=>'', 'icon'=>'', 'preview_url'=>'', 'homepage'=>'', 'featured'=>'', 'rating'=>'', 'votes'=>'', 'downloaded'=>'', 'price'=>'');
                            $tm = array_merge($mold, $tm);
                            $wpdb->insert(  
                                $wpdb->prefix."appstore_themes",  
                                $tm,  
                                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' )  
                            );
                            
                            $theme_id = $wpdb->insert_id;
                            $dump[] = $theme_id;
                            $wpdb->flush();
                            if (is_array($screenshots)) {
                                foreach($screenshots as $screenshot){
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_screenshots "
                                    ."(theme_id, screenshot) VALUES "
                                    ."(%d, %s)", $theme_id, $screenshot  
                                    );
                                    $wpdb->query($query);
                                    
                                }
                            }
                            if (is_array($tags)) {
                                foreach ($tags as $tag) {
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_themes_tags "
                                    ."(theme_id, tag) VALUES "
                                    ."(%d, %s)", $theme_id, $tag  
                                    );
                                    $wpdb->query($query);
                                }
                            }
                        }
//Update existing themes
                        if (($tm['type'] == 'theme') && (isset($stored_themes[$tm['slug']]))) {
                            $theme_id = $stored_themes[$tm['slug']];
                            unset($stored_themes[$tm['slug']]);
                            unset($tm['type']);
                            $mold = array('title'=>'', 'slug'=>'', 'description'=>'', 'author'=>'', 'version'=>'', 'updated'=>'', 'category_slug'=>'', 'category_name'=>'', 'link'=>'', 'icon'=>'', 'preview_url'=>'', 'homepage'=>'', 'featured'=>'', 'rating'=>'', 'votes'=>'', 'downloaded'=>'', 'price'=>'');
                            $tm = array_merge($mold, $tm);
                                $wpdb->update(
                                    $wpdb->prefix."appstore_themes",  
                                    $tm,  
                                    array( 'id' => $theme_id),  
                                    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' ),  
                                    array( '%d' )  
                                );
                                
                             if (is_array($screenshots)) {
                                $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_screenshots WHERE theme_id=%d", $theme_id);
                                $wpdb->query($query);
                                foreach($screenshots as $screenshot){
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_screenshots "
                                    ."(theme_id, screenshot) VALUES "
                                    ."(%d, %s)", $theme_id, $screenshot  
                                    );
                                    $wpdb->query($query);
                                    
                                }
                            }
                            if (is_array($tags)) {
                                $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_themes_tags WHERE theme_id=%d", $theme_id);
                                $wpdb->query($query);
                                foreach ($tags as $tag) {
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_themes_tags "
                                    ."(theme_id, tag) VALUES "
                                    ."(%d, %s)", $theme_id, $tag  
                                    );
                                    $wpdb->query($query);
                                }
                            } 
                        }

//Insert new plugins                        
                        if (($tm['type'] == 'plugin') && (!isset($stored_plugins[$tm['slug']]))) {
                            unset($tm['type']);
                            $mold = array('title'=>'', 'slug'=>'', 'description'=>'', 'author'=>'', 'version'=>'', 'updated'=>'', 'added'=>'', 'requires'=>'', 'tested'=>'', 'category_slug'=>'', 'category_name'=>'', 'link'=>'', 'icon'=>'', 'homepage'=>'', 'featured'=>'', 'rating'=>'', 'votes'=>'', 'downloaded'=>'', 'price'=>'');
                            $tm = array_merge($mold, $tm);
                            $wpdb->insert(  
                                $wpdb->prefix."appstore_plugins",  
                                $tm,  
                                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d' )  
                            );
                            $plugin_id = $wpdb->insert_id;
                            $wpdb->flush(); 
                            if (is_array($screenshots)) {
                                foreach($screenshots as $screenshot){
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_screenshots "
                                    ."(plugin_id, screenshot) VALUES "
                                    ."(%d, %s)", $plugin_id, $screenshot  
                                    );
                                    $wpdb->query($query);
                                    
                                }
                            }
                            if (is_array($tags)) {
                                foreach ($tags as $tag) {
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_plugins_tags "
                                    ."(plugin_id, tag) VALUES "
                                    ."(%d, %s)", $plugin_id, $tag  
                                    );
                                    $wpdb->query($query);
                                }
                            }
                        }
//Update existing plugins                        
                        if (($tm['type'] == 'plugin') && (isset($stored_plugins[$tm['slug']]))) {
                            $plugin_id = $stored_plugins[$tm['slug']];
                            unset($stored_plugins[$tm['slug']]);
                            unset($tm['type']);
                            $mold = array('title'=>'', 'slug'=>'', 'description'=>'', 'author'=>'', 'version'=>'', 'updated'=>'', 'added'=>'', 'requires'=>'', 'tested'=>'', 'category_slug'=>'', 'category_name'=>'', 'link'=>'', 'icon'=>'', 'homepage'=>'', 'featured'=>'', 'rating'=>'', 'votes'=>'', 'downloaded'=>'', 'price'=>'');
                            $tm = array_merge($mold, $tm);
                                $wpdb->update(
                                    $wpdb->prefix."appstore_plugins",  
                                    $tm,
                                    array( 'id' => $plugin_id),  
                                    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d' ),  
                                    array( '%d' )  
                                );   
                            if (is_array($screenshots)) {
                                $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_screenshots WHERE plugin_id=%d", $plugin_id);
                                $wpdb->query($query);
                                foreach($screenshots as $screenshot){
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_screenshots "
                                    ."(plugin_id, screenshot) VALUES "
                                    ."(%d, %s)", $plugin_id, $screenshot  
                                    );
                                    $wpdb->query($query);
                                    
                                }
                            }
                            if (is_array($tags)) {
                                $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_plugins_tags WHERE plugin_id=%d", $plugin_id);
                                $wpdb->query($query);
                                foreach ($tags as $tag) {
                                    $query = $wpdb->prepare(
                                    "INSERT IGNORE INTO ".$wpdb->prefix."appstore_plugins_tags "
                                    ."(plugin_id, tag) VALUES "
                                    ."(%d, %s)", $plugin_id, $tag  
                                    );
                                    $wpdb->query($query);
                                }
                            }  
                        }
                        
                             
                        $id = $type.'_id';
                        $tm['id'] = $$id;
                        $tm['screenshots'] = $screenshots;
                        $tm['tags'] = $tags;
                        $this->formulas[$type][$$id] = (object)$tm;
                    }
                }
            }
//delete deprecated themes and plugins
            if (sizeof($stored_plugins) > 0) {
                foreach ($stored_plugins as $id) {
                    $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_plugins WHERE id=%d", $id);
                    $wpdb->query($query);
                    $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_screenshots WHERE plugin_id=%d", $id);
                    $wpdb->query($query);
                    $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_plugins_tags WHERE plugin_id=%d", $id);
                    $wpdb->query($query);
                }
            }
            if (sizeof($stored_themes) > 0) {
                foreach ($stored_themes as $id) {
                    $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_themes WHERE id=%d", $id);
                    $wpdb->query($query);
                    $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_screenshots WHERE theme_id=%d", $id);
                    $wpdb->query($query);
                    $query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."appstore_themes_tags WHERE theme_id=%d", $id);
                    $wpdb->query($query);
                }
            }
        update_option('wp_appstore_formulas_rescan', false);
        update_option('wp_appstore_frontend_rescan', true);       
        }
    }
    
    function admin_url( $args = null ) {
        
        // $url = menu_page_url( basename( __FILE__ ), false );
    	$url = get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wp-appstore.php';
    	if ( is_array( $args ) )
    		$url = add_query_arg( $args, $url );
    	return $url;
    }
    public function get_plugins(){
        return $this->formulas['plugin'];
    }
    public function get_plugin($id){
        return $this->formulas['plugin'][$id];
    }
    public function get_themes(){
        return $this->formulas['theme'];
    }
    public function get_theme($id){
        return $this->formulas['theme'][$id];
    }
    function mass_in_array($needle, $haystack){
        if (!is_array($needle))
            return false;
        foreach ($needle as $key) {
            if (isset($haystack[$key]))
                $return[$key] = $haystack[$key];
        }
        return $return;
    }
    public function search($keyword, $item_type = ''){
        global $wpdb;
        if (strlen($keyword)<=1) {
            return false;
        }
        
        $query = $wpdb->prepare("SELECT `id` FROM ".$wpdb->prefix."appstore_themes WHERE title LIKE %s", '%'.$keyword.'%');
        $themes_ids = $wpdb->get_col($query);
        
        $query = $wpdb->prepare("SELECT `id` FROM ".$wpdb->prefix."appstore_plugins WHERE title LIKE %s", '%'.$keyword.'%');
        $plugins_ids = $wpdb->get_col($query);
        
        switch ($item_type) {
           case 'plugin' :
                            $return['plugin'] = $this->mass_in_array($plugins_ids, $this->formulas['plugin']);
           break;
           case 'theme' :
                            $return['theme'] = $this->mass_in_array($themes_ids, $this->formulas['theme']);
           break;
           default :
                            $return['plugin'] = $this->mass_in_array($plugins_ids, $this->formulas['plugin']);
                            $return['theme'] = $this->mass_in_array($themes_ids, $this->formulas['theme']);
           break;
        }
        return $return;
    }
    public function get_items_by_tag($tag, $item_type = ''){
        global $wpdb;
        if (strlen($tag)<=1) {
            return false;
        }
        
        $query = $wpdb->prepare("SELECT `theme_id` FROM ".$wpdb->prefix."appstore_themes_tags WHERE tag LIKE %s", $tag.'%');
        $themes_ids = $wpdb->get_col($query);
        
        $query = $wpdb->prepare("SELECT `plugin_id` FROM ".$wpdb->prefix."appstore_plugins_tags WHERE tag LIKE %s", $tag.'%');
        $plugins_ids = $wpdb->get_col($query);
        
        switch ($item_type) {
           case 'plugin' :
                            $return['plugin'] = $this->mass_in_array($plugins_ids, $this->formulas['plugin']);
           break;
           case 'theme' :
                            $return['theme'] = $this->mass_in_array($themes_ids, $this->formulas['theme']);
           break;
           default :
                            $return['plugin'] = $this->mass_in_array($plugins_ids, $this->formulas['plugin']);
                            $return['theme'] = $this->mass_in_array($themes_ids, $this->formulas['theme']);
           break;
        }
        return $return;
    }
    public function get_tags($type, $sort = true, $limit = 50){
        if (($type != 'plugin') && ($type != 'theme')) {
            return false;
        }
        $sql_limit = '';
        if($limit)
            $sql_limit = ' LIMIT 0 , '.$limit;
        global $wpdb;
        switch ($type) {
           case 'plugin' :
                            $table = $wpdb->prefix."appstore_plugins_tags";
                            $field = 'plugin_id';
           break;
           case 'theme':
                            $table = $wpdb->prefix."appstore_themes_tags";
                            $field = 'theme_id';
           break;
        }
        $query = $wpdb->prepare("SELECT tag FROM $table GROUP BY tag ORDER BY COUNT( * ) DESC".$sql_limit);
        $tags = $wpdb->get_col($query);
        if($sort)
            natsort($tags);
        return $tags;
    }
    public function sort_item_tags($item_object, $type){
        if (!is_object($item_object))
            return false;
        if (!is_array($item_object->tags))
            return false;
        if (($type != 'plugin') && ($type != 'theme'))
            return false;
        $matrix = $this->get_tags($type, false, false);
        foreach ($matrix as $tag) {
            if (in_array($tag, $item_object->tags)) {
                $result[] = $tag;
            }
        }
        return $result;
    }
    public function get_featured($type, $skip_installed = true, $limit = 6){
        if (($type != 'plugin') && ($type != 'theme')) {
            return false;
        }
        $sql_limit = '';
        $sql_where = '';
        if($limit)
            $sql_limit = ' LIMIT 0 , '.$limit;
        global $wpdb;
        switch ($type) {
           case 'plugin' :
                            if(!$this->installed_plugins)
                                $this->get_installed_plugins();
                            if($skip_installed)
                                $sql_where = " AND slug NOT IN ('".implode('\', \'',$this->installed_plugins)."')";
                            $query = $wpdb->prepare(
                            "SELECT id FROM ".$wpdb->prefix."appstore_plugins WHERE featured = 1".$sql_where." ORDER BY rating DESC".$sql_limit
                            );
           break;
           case 'theme':
                            if (!$this->installed_themes) {
                                $this->get_installed_themes();
                            }
                            if($skip_installed)
                                $sql_where = " AND slug NOT IN ('".implode('\', \'',$this->installed_themes)."')";
                            $query = $wpdb->prepare(
                            "SELECT id FROM ".$wpdb->prefix."appstore_themes WHERE featured = 1".$sql_where." ORDER BY rating DESC".$sql_limit
                            );
           break;
        }
        $ids = $wpdb->get_col($query);
        return $this->mass_in_array($ids, $this->formulas[$type]);
    }
    public function get_installed($type){
        if (($type != 'plugin') && ($type != 'theme')) {
            return false;
        }
        
        global $wpdb;
        switch ($type) {
           case 'plugin' :
                            if (!$this->installed_plugins) {
                                $this->get_installed_plugins();
                            }
                            $query = $wpdb->prepare(
                            "SELECT id FROM ".$wpdb->prefix."appstore_plugins WHERE slug IN ('".implode('\', \'',$this->installed_plugins)."') ORDER BY  featured DESC, rating DESC"
                            );
           break;
           case 'theme':
                            if (!$this->installed_themes) {
                                $this->get_installed_themes();
                            }
                            $query = $wpdb->prepare(
                            "SELECT id FROM ".$wpdb->prefix."appstore_themes WHERE slug IN ('".implode('\', \'',$this->installed_themes)."') ORDER BY  featured DESC, rating DESC"
                            );
           break;
        }
        $ids = $wpdb->get_col($query);
        return $this->mass_in_array($ids, $this->formulas[$type]);
    }
    public function get_lastest($type, $skip_installed = true, $limit = 6){
        if (($type != 'plugin') && ($type != 'theme')) {
            return false;
        }
        $sql_limit = '';
        $sql_where = '';
        if($limit)
            $sql_limit = ' LIMIT 0 , '.$limit;
        global $wpdb;
        switch ($type) {
           case 'plugin' :
                            if(!$this->installed_plugins)
                                $this->get_installed_plugins();
                            if($skip_installed)
                                $sql_where = " AND slug NOT IN ('".implode('\', \'',$this->installed_plugins)."')";
                            $query = $wpdb->prepare(
                            "SELECT id FROM ".$wpdb->prefix."appstore_plugins WHERE featured = 0".$sql_where." ORDER BY updated, rating DESC".$sql_limit
                            );
           break;
           case 'theme':
                            if (!$this->installed_themes) {
                                $this->get_installed_themes();
                            }
                            if($skip_installed)
                                $sql_where = " AND slug NOT IN ('".implode('\', \'',$this->installed_themes)."')";
                            $query = $wpdb->prepare(
                            "SELECT id FROM ".$wpdb->prefix."appstore_themes WHERE featured = 0".$sql_where." ORDER BY updated, rating DESC".$sql_limit
                            );
           break;
        }
        $ids = $wpdb->get_col($query);
        return $this->mass_in_array($ids, $this->formulas[$type]);
    }
    
}

?>