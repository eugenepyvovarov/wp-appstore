<?php
/*
Plugin Name: WP AppStore
Plugin URI: http://wp-appstore.com
Description: Premium plugins and themes
Author: Lifeisgoodlabs   
Version: 0.7.1
Author URI: http://www.ultimateblogsecurity.com
*/

 require_once(ABSPATH . 'wp-admin/includes/plugin.php');
if ( ! class_exists('WP_Upgrader') )
 include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
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
    public $http;
    public $installed_plugins;
    public $installed_themes;
    public $ini_files;
    public $formulas;
    public $dir;
    
    function WP_AppStore(){
        $this->http = new WP_Http();
        $this->installed_plugins = $this->get_installed_plugins();
        $this->get_installed_themes();
        $this->set_formulas();
    }
    function set_formulas(){

        if ( get_option('wp_appstore_formulas_rescan', true) ) {
            $this->dir = rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR . 'formulas';
            $this->ini_files = array();
            $this->recurse_directory($this->dir);
            $this->store();
            $this->check_for_plugins_updates();
        }else{
            $this->get_formulas_from_db();
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
            $this->installed_themes[] = $theme['Template'];
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
    
    function check_for_plugins_updates(){
        global $wpdb;
        
        $plugins = get_plugins();
        $for_update = get_option('wp_appstore_for_update');
        $current = get_site_transient( 'update_plugins' );
        if (!is_object($current)) {
            wp_update_plugins();
            $current = get_site_transient( 'update_plugins' );
        }
        foreach ($plugins as $key => $value) {
            $query = "SELECT `version`, `slug`, `id`, `link` FROM ".$wpdb->prefix."appstore_plugins WHERE title LIKE \"".$value['Name']."\"";
            $stored_plugins_result = $wpdb->get_results($query);
            
            if ($stored_plugins_result) {
                $repo_ver = $this->str_to_float($stored_plugins_result[0]->version);
                $curr_ver = $this->str_to_float($value['Version']);
                if ($repo_ver > $curr_ver) {
                    if (!isset($current->response[$key])) {
                        $api = new StdClass;
                        $api->id = $stored_plugins_result[0]->id;
                        $api->slug = $stored_plugins_result[0]->slug;
                        $api->new_version = $stored_plugins_result[0]->version;
                        $api->url = $stored_plugins_result[0]->link;
                        $api->package = $stored_plugins_result[0]->link;
                        $current->response[$key] = $api;
                        $updated = 1;
                    }
                $for_update[$stored_plugins_result[0]->slug] = $key;
                }
            }
        }
        update_option('wp_appstore_for_update', $for_update);
        if ($updated) {
            $current->last_checked = time();
            set_site_transient('update_plugins', $current);  //whether to actually check for updates, so we reset it to zero.
        }
    }
    
    function convert_escaped_quotes($str){
        $str = str_replace("&#039;", "'", $str);
        $str = str_replace("&quot;", '"', $str);
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
        return $out;  
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
            
            //start ini files loop
            foreach ($this->ini_files as $file) {
                if (preg_match('|\.ini$|', $file)) {
                    $tm = parse_ini_file($file);
                    if ( ($tm['type'] == 'theme') || ($this->check_compatibility($tm['requires']))) {
                        $tm['description'] = $this->convert_escaped_quotes($tm['description']);
                        $tm['author'] = $this->convert_escaped_quotes($tm['author']);
                        
                        $tags = $tm['tags'];
                        unset($tm['tags']);
                        $screenshots = $tm['screenshots'];
                        unset($tm['screenshots']);
                        
//Insert new themes                        
                        if (($tm['type'] == 'theme') && (!isset($stored_themes[$tm['slug']]))) {
                            unset($tm['type']);
                            $wpdb->insert(  
                                $wpdb->prefix."appstore_themes",  
                                $tm,  
                                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' )  
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
                                $wpdb->update(
                                    $wpdb->prefix."appstore_themes",  
                                    $tm,  
                                    array( 'id' => $theme_id),  
                                    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' ),  
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
                            $wpdb->insert(  
                                $wpdb->prefix."appstore_plugins",  
                                $tm,  
                                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' )  
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
                                $wpdb->update(
                                    $wpdb->prefix."appstore_plugins",  
                                    $tm,
                                    array( 'id' => $plugin_id),  
                                    array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d' ),  
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
                        
                             
                        $id = $tm['type'].'_id';
                        $tm['id'] = $$id;
                        $this->formulas[$tm['type']][$$id] = (object)$tm;
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
        }
        return $dump;
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
}
function wp_appstore_admin_init() {
    wp_enqueue_style( 'wp-appstore-css', plugins_url( basename( dirname( __FILE__ ) ) . '/wp-appstore.css' ), false, '20110322' );
    wp_enqueue_style( 'wp-appstore-slider', plugins_url( basename( dirname( __FILE__ ) ) . '/nivo-slider/nivo-slider.css' ), false, '20110322' );
    wp_enqueue_style( 'wp-appstore-slider1', plugins_url( basename( dirname( __FILE__ ) ) . '/nivo-slider/demo/style.css' ), false, '20110322' );
    wp_enqueue_style( 'thickbox' );
    wp_enqueue_script( 'wp-appstore-slider-js', plugins_url( basename( dirname( __FILE__ ) )  . '/nivo-slider/jquery.nivo.slider.js'), array( 'jquery'), '20110322' );
    wp_enqueue_script('thickbox');
}
function wp_appstore_admin_menu() {
    add_menu_page( 'WP Appstore', 'WP AppStore', 'activate_plugins', basename( __FILE__ ), 'wp_appstore_main', null, 61 );
    add_submenu_page( basename( __FILE__ ), 'Store', 'Store', 'activate_plugins', basename( __FILE__ ), 'wp_appstore_main' );
    // add_submenu_page( basename( __FILE__ ), 'My account', 'My account', 'activate_plugins', 'wp_appstore_myaccount', 'wp_appstore_myaccount' );
}
function wp_appstore_page_store(){
    $appstore = new WP_AppStore();
    $plugins = $appstore->get_plugins();
    $themes = $appstore->get_themes();


    var_dump(get_site_transient( 'update_plugins' ));
    //wp_appstore_update_formulas();
    ?>

    <div class="wrap">
        <?php screen_icon( 'plugins' );?>
        <h2>WP AppStore
			<span style="position:absolute;padding-left:15px;">
            <a href="http://www.facebook.com/pages/WP-AppStore/147376395324061" target="_blank"><img src="<?php echo plugins_url( 'images/facebook.png', __FILE__ ); ?>" alt="" /></a>
            <a href="http://twitter.com/wpappstore" target="_blank"><img src="<?php echo plugins_url( 'images/twitter.png', __FILE__ ); ?>" alt="" /></a>
            <a href="http://wp-appstore.com/" target="_blank"><img src="<?php echo plugins_url( 'images/rss.png', __FILE__ ); ?>" alt="" /></a>
            </span>
		</h2>
        <div id="poststuff" class="metabox-holder has-right-sidebar" style="max-width:950px;min-width:640px;">
        <div id="side-info-column" class="inner-sidebar draggable">
            <?php if(get_option('wp_appstore_autoupdate_request')): ?>
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>Update Avaliable!</span></h3>
                <div class="inside">
                    <p>New update for this plugin now avaliable!</p>
                    <p style="text-align: center;">
                    <span class="buyoptions"><a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'autoupdate')));?>" class="button rbutton" title="Update It Now">Get Update Now!</a></span>
                    </p>
                </div>
            </div>  
            <?php endif; ?>
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>WELCOME!</span></h3>
                <div class="inside">
                    <p>Welcome to the first place where you can get professional plugins and the best themes.</p>
                    <p>This store is just a preview of what's to come.  Please send feedback to <a href="mailto:eugene@lifeisgoodlabs.com">eugene@lifeisgoodlabs.com</a></p>
                </div>
            </div>            
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>Special Offer!</span></h3>
                <div class="inside" style="text-align:center;">
                    <a href="http://themefuse.com" target="_blank"><img src="<?php echo plugins_url( 'images/themefuse.png', __FILE__ ); ?>" alt="ThemeFuse offer" /></a>
                </div>
            </div>            
            <!-- <div id="" class="postbox " style="">
                <h3 class="hndle"><span>WP APPSTORE QUICK LINKS</span></h3>
                <div class="inside">
                    <p>
                        <ul style="margin-left:10px;font-weight:bold;">
                            <li><a href="#">About the Wp Appstore</a></li>
                            <li><a href="#">How does it work?</a></li>
                            <li><a href="#">FAQ</a></li>
                            <li><a href="http://www.facebook.com/pages/WP-AppStore/147376395324061">WP AppStore on Facebook</a></li>
                            <li><a href="http://twitter.com/wpappstore">WP AppStore on Twitter</a></li>
                        </ul>
                    </p>
                </div>
            </div> -->
            <script src="http://widgets.twimg.com/j/2/widget.js"></script>
            <script>
            new TWTR.Widget({
              version: 2,
              type: 'profile',
              rpp: 5,
              interval: 6000,
              width: 280,
              height: 300,
              theme: {
                shell: {
                  background: '#e6e6e6',
                  color: '#303030'
                },
                tweets: {
                  background: '#ffffff',
                  color: '#5c5c5c',
                  links: '#56748f'
                }
              },
              features: {
                scrollbar: false,
                loop: false,
                live: true,
                hashtags: true,
                timestamp: false,
                avatars: false,
                behavior: 'all'
              }
            }).render().setUser('wpappstore').start();
            </script>
            <div id="" class="postbox " style="margin-top:20px;">
                <h3 class="hndle"><span>SUBSCRIBE FOR UPDATES</span></h3>
                <div class="inside">
                    <!-- Begin MailChimp Signup Form -->
                    <!--[if IE]>
                    <style type="text/css" media="screen">
                    	#mc_embed_signup fieldset {position: relative;}
                    	#mc_embed_signup legend {position: absolute; top: -1em; left: .2em;}
                    </style>
                    <![endif]--> 
                    <!--[if IE 7]>
                    <style type="text/css" media="screen">
                    	.mc-field-group {overflow:visible;}
                    </style>
                    <![endif]-->

                    <div id="mc_embed_signup">
                    <form action="http://lifeisgoodlabs.us1.list-manage2.com/subscribe/post?u=382bf9a62c1627d0e7dd2cb42&amp;id=da67cb7263" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" style="font: normal 100% Arial, sans-serif;font-size: 10px;">
                    	<fieldset style="padding-top: 1.5em;background-color: #fff;color: #000;text-align: left;">


                    <div class="mc-field-group" style="margin: .3em 5%;clear: both;overflow: hidden;">
                    <label for="mce-EMAIL" style="display: block;line-height: 1.5em;font-weight: bold;">Email Address <strong class="note-required">*</strong>
                    </label>
                    <input type="text" value="" name="EMAIL" class="required email" id="mce-EMAIL" style="margin-right: 1.5em;padding: .2em .3em;width: 95%;float: left;z-index: 999;">
                    </div>
                    		<div id="mce-responses" style="float: left;top: -1.4em;padding: 0em .5em 0em .5em;overflow: hidden;width: 90%;margin: 0 5%;clear: both;">
                    			<div class="response" id="mce-error-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: FBE3E4;color: #D12F19;"></div>
                    			<div class="response" id="mce-success-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: #E3FBE4;color: #529214;"></div>
                    		</div>
                    		<div>
                    		<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button" style="clear: both;width: auto;display: block;margin: 1em 0 1em 5%;"></div>
                    	</fieldset>	
                    	<a href="#" id="mc_embed_close" class="mc_embed_close" style="display: none;">Close</a>
                    </form>
                    </div>

                    <!--End mc_embed_signup-->
                </div>
            </div>
        </div>

        <div id="post-body">
            <div id="post-body-content">
                <div id="namediv" class="stuffbox">
                    <h3><label for="link_name">Featured Plugins</label></h3>
                    <div class="inside">
                        <?php foreach($plugins as $one): ?>
                        <div class="plugin">
                            <img class="logo" src="<?php echo $one->icon; ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));?>"><?php echo $one->title;?></a>
                            <span class="category"><?php echo $one->category->title; ?></span>
                            <span class="rating star<?php echo $one->rating;?>"><?php echo $one->votes;?> ratings</span>
                            <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_plugins)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Buy It Now"><?php if(in_array($one->slug, $appstore->installed_plugins)){echo "INSTALLED"; } else {echo "GET FREE";}?></a></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div id="addressdiv" class="stuffbox">
                    <h3><label for="link_url">Featured Themes</label></h3>
                    <div class="inside">
                        <?php foreach($themes as $one): ?>
                        <div class="theme">
                            <img class="scrot" width="280px" src="<?php echo $one->icon; ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));?>"><?php echo $one->title; ?></a>
                            <span class="category"><?php echo $one->category->title; ?></span>
                            <!--
<span class="buyoptions"><a href="#TB_inline?height=200&width=400&inlineId=buysorrymessage" class="thickbox button rbutton" title="Buy It Now">$<?php echo $one->price; ?> BUY</a></span>
                            
-->
                            <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_themes)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Buy It Now"><?php if(in_array($one->slug, $appstore->installed_themes)){echo "INSTALLED"; } else {echo "GET FREE";}?></a></span>
                            <span class="rating star<?php echo $one->rating; ?>"><?php echo $one->votes; ?> ratings</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!--
                <div id="addressdiv" class="stuffbox">
                    <h3><label for="link_url">Popular</label></h3>
                    <div class="inside">
                        <p>Another text here too</p>
                    </div>
                </div>
                <div id="addressdiv" class="stuffbox">
                    <h3><label for="link_url">Top Plugins</label></h3>
                    <div class="inside">
                        <p>Another text here too</p>
                    </div>
                </div>
                <div id="addressdiv" class="stuffbox">
                    <h3><label for="link_url">Top Themes</label></h3>
                    <div class="inside">
                        <p>Another text here too</p>
                    </div>
                </div>
                -->
            </div>
        </div>
        </div>
        
    </div>
    <div id="buysorrymessage" style="display:none;">
        <p style="margin-top:50px;margin-left:20px;margin-right:30px;">Sorry, but this functionality is not yet available. 
Please enter your email below and we will notify you when you can download an update.</p>
        <p>
        <form action="http://lifeisgoodlabs.us1.list-manage2.com/subscribe/post?u=382bf9a62c1627d0e7dd2cb42&amp;id=da67cb7263" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" style="font: normal 100% Arial, sans-serif;font-size: 10px;">
        	<fieldset style="background-color: #fff;color: #000;text-align: left;">


        <div class="mc-field-group" style="margin: .3em 5%;clear: both;overflow: hidden;">
        <label for="mce-EMAIL" style="display: block;line-height: 1.5em;font-weight: bold;">Email Address <strong class="note-required">*</strong>
        </label>
        <input type="text" value="" name="EMAIL" class="required email" id="mce-EMAIL" style="margin-right: 1.5em;padding: .2em .3em;width: 95%;float: left;z-index: 999;">
        </div>
        		<div id="mce-responses" style="float: left;top: -1.4em;padding: 0em .5em 0em .5em;overflow: hidden;width: 90%;margin: 0 5%;clear: both;">
        			<div class="response" id="mce-error-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: FBE3E4;color: #D12F19;"></div>
        			<div class="response" id="mce-success-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: #E3FBE4;color: #529214;"></div>
        		</div>
        		<div>
        		<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button" style="clear: both;width: auto;display: block;margin: 1em 0 1em 5%;"></div>
        	</fieldset>	
        	<a href="#" id="mc_embed_close" class="mc_embed_close" style="display: none;">Close</a>
        </form>
        </p>
    </div>
    <?php
}

function wp_appstore_page_view_plugin($plugin_info){
    $appstore = new WP_AppStore();
    ?>
        <div id="buysorrymessage" style="display:none;">
            <p style="margin-top:50px;margin-left:20px;margin-right:30px;">Sorry, but this functionality is not yet available. 
Please enter your email below and we will notify you when you can download an update.</p>
            <p>
            <form action="http://lifeisgoodlabs.us1.list-manage2.com/subscribe/post?u=382bf9a62c1627d0e7dd2cb42&amp;id=da67cb7263" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" style="font: normal 100% Arial, sans-serif;font-size: 10px;">
            	<fieldset style="background-color: #fff;color: #000;text-align: left;">


            <div class="mc-field-group" style="margin: .3em 5%;clear: both;overflow: hidden;">
            <label for="mce-EMAIL" style="display: block;line-height: 1.5em;font-weight: bold;">Email Address <strong class="note-required">*</strong>
            </label>
            <input type="text" value="" name="EMAIL" class="required email" id="mce-EMAIL" style="margin-right: 1.5em;padding: .2em .3em;width: 95%;float: left;z-index: 999;">
            </div>
            		<div id="mce-responses" style="float: left;top: -1.4em;padding: 0em .5em 0em .5em;overflow: hidden;width: 90%;margin: 0 5%;clear: both;">
            			<div class="response" id="mce-error-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: FBE3E4;color: #D12F19;"></div>
            			<div class="response" id="mce-success-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: #E3FBE4;color: #529214;"></div>
            		</div>
            		<div>
            		<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button" style="clear: both;width: auto;display: block;margin: 1em 0 1em 5%;"></div>
            	</fieldset>	
            	<a href="#" id="mc_embed_close" class="mc_embed_close" style="display: none;">Close</a>
            </form>
            </p>
        </div>
    <div class="wrap">
        <?php screen_icon( 'plugins' );?>
        <h2>WP AppStore</h2>
        
            <div id="poststuff" class="metabox-holder has-right-sidebar" style="max-width:950px;min-width:640px;">
            <div id="side-info-column" class="inner-sidebar draggable">
                <div id="" class="postbox " style="margin-top:50px;">
                    <h3 class="hndle"><span>Information</span></h3>
                    <div class="inside">
                        <p>
                            <ul style="margin-left:10px;font-weight:bold;">
                                <li>Released: <?php echo date('d M, Y', strtotime($plugin_info->updated));?></li>
                                <li>Version: <?php echo $plugin_info->version; ?></li>
                                <li>Category: <a href="#"><?php echo $plugin_info->category_title; ?></a></li>
                                <li>Rating: <span class="star<?php echo $plugin_info->rating; ?>" style="padding-left:95px;color:#999;"><?php echo $plugin_info->votes; ?> ratings</span></li>
                                <li></li>
                            </ul>
                        </p>
                    </div>
                </div>
                <div id="" class="postbox " style="">
                    <h3 class="hndle"><span>About Developer</span></h3>
                    <div class="inside">
                        <p>
                            <ul style="margin-left:10px;font-weight:bold;">
                                <li>Developer: <?php echo $plugin_info->author; ?></li>
                            </ul>
                        </p>
                    </div>
                </div>
            </div>

            <div id="post-body">
                <div id="post-body-content">
                    <div class="general_info">
                        <div class="logo_and_buy_button" >
                        <img class="logo" src="<?php echo $plugin_info->icon; ?>" alt="" />
                        <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_plugins)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-plugin','plugin_name'=>$plugin_info->slug,'plugin_id'=>$plugin_info->id)));}else{echo "#";}?>" class="button rbutton" title="Buy It Now"><?php if(in_array($plugin_info->slug, $appstore->installed_plugins)){echo "INSTALLED"; } else {echo "GET FREE";}?></a></span>
                        </div>
                        <h2><?php echo $plugin_info->title; ?></h2>
                        <div class="description">
							<?php echo $plugin_info->description; ?>
                        </div>
                        <div class="latest_changes"></div>
                    </div>
                    <?php if(count($plugin_info->screenshots)>0): ?>
                    <div id="namediv" class="stuffbox">
                        <h3><label for="link_name">Screenshots</label></h3>
                        <div class="inside">
                        <div id="slider" class="nivoSlider">
                            <?php foreach($plugin_info->screenshots as $one): ?>
                            <img src="<?php echo $one ?>" alt="" />
                        <?php endforeach; ?>
                        </div>
                        <div id="htmlcaption" class="nivo-html-caption">
                            <strong>This</strong> is an example of a <em>HTML</em> caption with <a href="#">a link</a>.
                        </div>
                        </div>
                        <script type="text/javascript">
                        jQuery(window).load(function() {
                            jQuery('#slider').nivoSlider({effect:'sliceDownRight',animSpeed:500, pauseTime:7500});
                        });
                        </script>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
    </div>
    <?php
}

function wp_appstore_page_view_theme($theme_info){
    ?>
        <div id="buysorrymessage" style="display:none;">
            <p style="margin-top:50px;margin-left:20px;margin-right:30px;">Sorry, but this functionality is not yet available. 
Please enter your email below and we will notify you when you can download an update.</p>
            <p>
            <form action="http://lifeisgoodlabs.us1.list-manage2.com/subscribe/post?u=382bf9a62c1627d0e7dd2cb42&amp;id=da67cb7263" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" style="font: normal 100% Arial, sans-serif;font-size: 10px;">
            	<fieldset style="background-color: #fff;color: #000;text-align: left;">


            <div class="mc-field-group" style="margin: .3em 5%;clear: both;overflow: hidden;">
            <label for="mce-EMAIL" style="display: block;line-height: 1.5em;font-weight: bold;">Email Address <strong class="note-required">*</strong>
            </label>
            <input type="text" value="" name="EMAIL" class="required email" id="mce-EMAIL" style="margin-right: 1.5em;padding: .2em .3em;width: 95%;float: left;z-index: 999;">
            </div>
            		<div id="mce-responses" style="float: left;top: -1.4em;padding: 0em .5em 0em .5em;overflow: hidden;width: 90%;margin: 0 5%;clear: both;">
            			<div class="response" id="mce-error-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: FBE3E4;color: #D12F19;"></div>
            			<div class="response" id="mce-success-response" style="display: none;margin: 1em 0;padding: 1em .5em .5em 0;font-weight: bold;float: left;top: -1.5em;z-index: 1;width: 80%;background: #E3FBE4;color: #529214;"></div>
            		</div>
            		<div>
            		<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" class="button" style="clear: both;width: auto;display: block;margin: 1em 0 1em 5%;"></div>
            	</fieldset>	
            	<a href="#" id="mc_embed_close" class="mc_embed_close" style="display: none;">Close</a>
            </form>
            </p>
        </div>
    <div class="wrap">
        <?php screen_icon( 'plugins' );?>
        <h2>WP AppStore</h2>
        
            <div id="poststuff" class="metabox-holder has-right-sidebar" style="max-width:950px;min-width:640px;">
            <div id="side-info-column" class="inner-sidebar draggable">
                <div id="" class="postbox " style="margin-top:50px;">
                    <h3 class="hndle"><span>Information</span></h3>
                    <div class="inside">
                        <p>
                            <ul style="margin-left:10px;font-weight:bold;">
                                <li>Released: <?php echo date('d M, Y', strtotime($theme_info->updated));?></li>
                                <li>Version: <?php echo $theme_info->version;?></li>
                                <li>Category: <a href="#"><?php echo $theme_info->category_title;?></a></li>
                                <li>Rating: <span class="star<?php echo $theme_info->rating;?>" style="padding-left:95px;color:#999;"><?php echo $theme_info->votes;?> ratings</span></li>
                                <li></li>
                            </ul>
                        </p>
                    </div>
                </div>
                <div id="" class="postbox " style="">
                    <h3 class="hndle"><span>About Developer</span></h3>
                    <div class="inside">
                        <p>
                            <ul style="margin-left:10px;font-weight:bold;">
                                <li>Developer: <?php echo $theme_info->author;?></li>
                                <li><a href="<?php echo $theme_info->link;?>">Theme Website</a></li>
                            </ul>
                        </p>
                    </div>
                </div>
            </div>

            <div id="post-body">
                <div id="post-body-content">
                    <div class="general_info">
                        <div class="logo_and_buy_button" >
                        <span class="buyoptions" style="display:block;margin-top:45px;"><a href="#TB_inline?height=200&width=400&inlineId=buysorrymessage" class="thickbox button rbutton" title="Buy It Now">$<?php echo $theme_info->price;?> BUY</a></span>
                        </div>
                        <h2><?php echo $theme_info->title;?></h2>
                        <div class="description">
                            <?php echo $theme_info->description;?>
                        </ul>
                            
                        </p>
                        </div>
                        <div class="latest_changes"></div>
                    </div>
                    <?php if(count($theme_info->screenshots)>0): ?>
                    <div id="namediv" class="stuffbox">
                        <h3><label for="link_name">Screenshots</label></h3>
                        <div class="inside">
                        <div id="nivo-slider" class="nivoSlider">
                            <?php foreach($theme_info->screenshots as $one): ?>
                            <img src="<?php echo $one ?>" alt="" />
                        <?php endforeach; ?>
                        </div>
                        <div id="htmlcaption" class="nivo-html-caption">
                            <strong>This</strong> is an example of a <em>HTML</em> caption with <a href="#">a link</a>.
                        </div>
                        </div>
                        <script type="text/javascript">
                        jQuery(window).load(function() {
                            jQuery('#nivo-slider').nivoSlider({effect:'sliceDownRight',animSpeed:500, pauseTime:7500});
                        });
                        </script>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
    </div>
    <?php
}

function wp_appstore_main() {
    $pages = array('store','view-plugin', 'view-theme', 'install-plugin', 'install-theme', 'autoupdate', 'plugin-update');
    $page = '';
    if(!isset($_GET['screen']) || !in_array($_GET['screen'],$pages)){
        $page = 'store';
    } else {
        $page = $_GET['screen'];
    }
    switch($page){
        case 'store':
            wp_appstore_page_store();
            break;
        case 'view-plugin':
            if(isset($_GET['plugin_id']) && (intval($_GET['plugin_id']) > 0)){
                $appstore = new WP_AppStore();
                $plugin_info = $appstore->get_plugin(intval($_GET['plugin_id']));
                if($plugin_info != null){
                    wp_appstore_page_view_plugin($plugin_info);
                } else {
                    wp_appstore_page_store();
                }
            } else {
                wp_appstore_page_store();
            }
            break;
        case 'view-theme':
            if(isset($_GET['theme_id']) && (intval($_GET['theme_id'])>0)){
                $appstore = new WP_AppStore();
                $theme_info = $appstore->get_theme(intval($_GET['theme_id']));
                if($theme_info != null){
                    wp_appstore_page_view_theme($theme_info);
                } else {
                    wp_appstore_page_store();
                }
            } else {
                wp_appstore_page_store();
            }
            break;
        case 'install-plugin':
            if ( ! current_user_can('install_plugins') )
    			wp_die(__('You do not have sufficient permissions to install plugins for this site.'));
            if(isset($_GET['plugin_id']) && (intval($_GET['plugin_id']) > 0)){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                $appstore = new WP_AppStore();
                $plugin_info = $appstore->get_plugin(intval($_GET['plugin_id']));
                if($plugin_info === null){
                    wp_appstore_page_store();
                } else {
                    // $api = plugins_api('plugin_information', array('slug' => $plugin, 'fields' => array('sections' => false) ) ); //Save on a bit of bandwidth.
                    $api = new StdClass;
                    $api->id = $plugin_info->id;
                    $api->name = $plugin_info->title;
                    $api->slug = $plugin_info->slug;
                    $api->version = $plugin_info->version;
                    $api->author = $plugin_info->author;
                    $api->required = '2.5';
                    $api->tested = '3.1.1';
                    $api->download_link = $plugin_info->link;
                    
            		if ( is_wp_error($api) )
            	 		wp_die($api);

            		$title = sprintf( __('Installing Plugin: %s'), $api->name . ' ' . $api->version );
            		$nonce = 'install-plugin_' . $api->slug;
            		$url = 'admin.php?page=wp-appstore.php&screen=install-plugin&plugin_name='. $api->slug . '&plugin_id='. $api->id ;
                    // if ( isset($_GET['from']) )
                    //     $url .= '&from=' . urlencode(stripslashes($_GET['from']));

            		$type = 'web'; //Install plugin type, From Web or an Upload.

            		$upgrader = new Plugin_Upgrader( new WPAppstore_Plugin_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
            		$upgrader->install($api->download_link);
        		}
            }
            break;
        case 'install-theme':
                if ( ! current_user_can('install_themes') )
    			wp_die(__('You do not have sufficient permissions to install themes for this site.'));
                if(isset($_GET['theme_id']) && (intval($_GET['theme_id']) > 0)){
                    $appstore = new WP_AppStore();
                    $theme_info = $appstore->get_theme(intval($_GET['theme_id']));
                    if($theme_info === null){
                        wp_appstore_page_store();
                    } else {
                        $api = new StdClass;
                        $api->id = $theme_info->id;
                        $api->name = $theme_info->title;
                        $api->slug = $theme_info->slug;
                        $api->version = $theme_info->version;
                        $api->author = $theme_info->author;
                        $api->download_link = $theme_info->link;
                        
                        $title = sprintf( __('Installing Theme: %s'), $api->name . ' ' . $api->version );
                		$nonce = 'install-theme_' . $api->slug;
                		$url = 'admin.php?page=wp-appstore.php&screen=install-theme&theme_name='. $api->slug . '&theme_id='. $api->id ;
                        
                        $type = 'web';
                        
                        $upgrader = new Theme_Upgrader( new WPAppstore_Theme_Installer_Skin( compact('title', 'url', 'nonce', 'plugin', 'api') ) );
                        $upgrader->install($api->download_link);

                    }
                }
            break;
        case 'autoupdate':
            if ( ! current_user_can('update_plugins') )
    			wp_die(__('You do not have sufficient permissions to update plugins for this site.'));
            if(get_option('wp_appstore_autoupdate_request')){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                    
                    $plugin = 'wp-appstore/wp-appstore.php';
            		$title = sprintf( __('Update Plugin: %s'), 'WP Appstore');
                    
                    $parent_file = 'plugins.php';
                    $submenu_file = 'plugins.php';
                    require_once(ABSPATH . 'wp-admin/admin-header.php');
                    
            		$nonce = 'upgrade-plugin_' . $plugin;
                    $url = 'update.php?action=upgrade-plugin&plugin=' . $plugin;

            		$upgrader = new Plugin_Upgrader( new Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
            		$upgrader->install($plugin);
                    
                    include(ABSPATH . 'wp-admin/admin-footer.php');
            }
            break;
        case 'plugin-update':
            if ( ! current_user_can('update_plugins') )
    			wp_die(__('You do not have sufficient permissions to update plugins for this site.'));
             if(isset($_GET['plugin']) && (strlen($_GET['plugin']) > 1)){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                    $plugin = $_GET['plugin'];
                    $current = get_site_transient( 'update_plugins' );
                    if (is_object($current) && isset($current->response[$plugin])) {
                    
                    }
          			$title = __('Update Plugin');
                    
                    $parent_file = 'plugins.php';
                    $submenu_file = 'plugins.php';
                    
            		$nonce = 'upgrade-plugin_' . $plugin;
                    $url = 'update.php?action=upgrade-plugin&plugin=' . $plugin;

            		$upgrader = new Plugin_Upgrader( new Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
            		$upgrader->install($plugin);
                    $for_update = get_option('wp_appstore_for_update');
                    if ($key = array_search($plugin, $for_update)) {
                        unset($for_update[$key]);
                        update_option('wp_appstore_for_update', $for_update);
                    }
            }
            break;
    }
}
function wp_appstore_myaccount() {
    echo "456"; 
}

function get_tmp_path(){
    if ( defined('WP_TEMP_DIR') ){
        $path = rtrim(WP_TEMP_DIR, '/');
		return $path.'/';
    }

	$temp = ini_get('upload_tmp_dir');
	if ( is_dir($temp) && @is_writable($temp) ){
		$path = rtrim($temp, '/');
		return $path.'/';
    }
    
    $temp = WP_CONTENT_DIR . '/';
	if ( is_dir($temp) && @is_writable($temp) )
		return $temp;
    return false;
}

function wp_appstore_update_formulas() {
    if (!get_tmp_path()) {
        wp_die(__('You do not have sufficient permissions to update formulas on this site.'));
    }
    $tmp_file_name = get_tmp_path().'tmp.zip';
    //$download_url = "https://github.com/bsn/wp-appstore/zipball/master";
    $download_url = "https://github.com/bsn/wp-appstore/zipball/DeV";
    $file = file_get_contents($download_url);
    file_put_contents($tmp_file_name, $file);
    
    $path = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'formulas';
    
    $wp_appstore_plugin = get_tmp_path().'wp_appstore_plg.php';
    
    $zip = zip_open($tmp_file_name);
    if (is_resource($zip)) {
        
      while ($zip_entry = zip_read($zip)) {
        if (zip_entry_open($zip, $zip_entry, "r")) {
          $buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
          if(preg_match('|\wp-appstore.php$|', zip_entry_name($zip_entry))){
            $filename = strrchr(zip_entry_name($zip_entry),'/');
            @file_put_contents($wp_appstore_plugin,$buf);
            
            if (is_file($wp_appstore_plugin)) {
                $repo_plugin_headers = get_plugin_data($wp_appstore_plugin);
                $site_plugin_headers = get_plugin_data($path = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'wp-appstore.php');
                $repo_ver = WP_AppStore::str_to_float($repo_plugin_headers['Version']);
                $site_ver = WP_AppStore::str_to_float($site_plugin_headers['Version']);
                if ($repo_ver > $site_ver) {
                    /*zip_entry_close($zip_entry);
                    zip_close($zip);
                    unlink($tmp_file_name);
                    unlink($wp_appstore_plugin);
                    exit();
                    we can use this later*/
                    
                    $api = new StdClass;
                    $api->id = 1;
                    $api->slug = 'wp-appstore';
                    $api->new_version = $repo_plugin_headers['Version'];
                    $api->url = $repo_plugin_headers['PluginURI'];
                    $api->package = $download_url;
                    
                   
                    $current = get_site_transient( 'update_plugins' );
                    $current->response['wp-appstore/wp-appstore.php'] = $api;
                    $current->last_checked = time();
                    set_site_transient('update_plugins', $current);  //whether to actually check for updates, so we reset it to zero.
                    
                    
                    update_option('wp_appstore_autoupdate_request', true);
                }
            }
          }
          
          if(preg_match('|\.ini$|', zip_entry_name($zip_entry))){
            $filename = strrchr(zip_entry_name($zip_entry),'/');
            @file_put_contents($path.$filename,$buf);
          }
           
          zip_entry_close($zip_entry);
        }
      }
      zip_close($zip);
    }else{
        wp_die(__('We have an error while updating formulas files.'));
    }
    unlink($tmp_file_name);
    unlink($wp_appstore_plugin);
    update_option('wp_appstore_formulas_rescan', true);
}

function wp_appstore_set_db_tables(){
    global $wpdb;
      
$sql = "
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}appstore_plugins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `version` varchar(10) NOT NULL,
  `updated` varchar(50) NOT NULL,
  `added` varchar(50) NOT NULL,
  `requires` varchar(10) NOT NULL,
  `tested` varchar(10) NOT NULL,
  `link` varchar(255) NOT NULL,
  `homepage` varchar(255) NOT NULL,
  `rating` varchar(10) NOT NULL,
  `votes` int(11) NOT NULL,
  `downloaded` int(11) NOT NULL,
  `price` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
$wpdb->query($sql);
$sql = "
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}appstore_plugins_tags` (
  `plugin_id` int(11) NOT NULL,
  `tag` varchar(255) NOT NULL,
  UNIQUE KEY `plugin_id` (`plugin_id`,`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$wpdb->query($sql);
$sql = "
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}appstore_screenshots` (
  `plugin_id` int(11) NOT NULL,
  `theme_id` int(11) NOT NULL,
  `screenshot` varchar(255) NOT NULL,
  UNIQUE KEY `screenshot_id` (`plugin_id`,`theme_id`,`screenshot`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$wpdb->query($sql);
$sql = "
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}appstore_themes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `author` varchar(255) NOT NULL,
  `version` varchar(10) NOT NULL,
  `updated` varchar(50) NOT NULL,
  `link` varchar(255) NOT NULL,
  `preview_url` varchar(255) NOT NULL,
  `homepage` varchar(255) NOT NULL,
  `rating` varchar(10) NOT NULL,
  `votes` int(11) NOT NULL,
  `downloaded` int(11) NOT NULL,
  `price` float NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
$wpdb->query($sql);
$sql = "
CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}appstore_themes_tags` (
  `theme_id` int(11) NOT NULL,
  `tag` varchar(255) NOT NULL,
  UNIQUE KEY `theme_id` (`theme_id`,`tag`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
$wpdb->query($sql);
}


function wp_appstore_activation() {
    wp_appstore_set_db_tables();
    wp_appstore_update_formulas();
    $a = new WP_AppStore;
	wp_schedule_event(time(), 'daily', 'wp_appstore_daily_event');
}
function wp_appstore_deactivation() {
	wp_clear_scheduled_hook('wp_appstore_daily_event');
}
function wp_appstore_uninstall() {
    global $wpdb;
    wp_clear_scheduled_hook('wp_appstore_daily_event');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_plugins');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_plugins_tags');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_screenshots');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_themes');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_themes_tags');
    delete_option('wp_appstore_formulas_rescan');
    delete_option('wp_appstore_autoupdate_request');
    delete_option('wp_appstore_for_update');
}
add_action('admin_init', 'wp_appstore_admin_init');
add_action('admin_menu', 'wp_appstore_admin_menu');
add_action('wp_appstore_daily_event', 'wp_appstore_update_formulas');
register_activation_hook(__FILE__, 'wp_appstore_activation');
register_deactivation_hook(__FILE__, 'wp_appstore_deactivation');
register_uninstall_hook(__FILE__, 'wp_appstore_uninstall')
?>