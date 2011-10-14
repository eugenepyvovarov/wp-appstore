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
    public $ini_files;
    public $formulas;
    public $dir;
    
    function WP_AppStore(){
        $this->http = new WP_Http();
        $this->installed_plugins = $this->get_installed_plugins();
        $this->dir = rtrim(dirname(__FILE__), '/\\') . DIRECTORY_SEPARATOR . 'formulas';
        $this->recurse_directory($this->dir);
        $this->read_formulas();
        //$this->store();
    }
    function store() {
        if (sizeof($this->ini_files) > 0) {
            update_option('wp_appstore_ini_cashe', $this->ini_files);
        }
        if (sizeof($this->formulas) > 0) {
            update_option('wp_appstore_formulas_cashe', $this->formulas);
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
    
    public function read_formulas() {
        if (sizeof($this->ini_files) > 0) {
            foreach ($this->ini_files as $file) {
                $tm = parse_ini_file($file);
                $this->formulas[$tm['type']][$tm['id']] = (object)$tm; // Actually, we should make a dynamic id based on array key
            }
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
                            <span class="buyoptions"><a href="#TB_inline?height=200&width=400&inlineId=buysorrymessage" class="thickbox button rbutton" title="Buy It Now">$<?php echo $one->price; ?> BUY</a></span>
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
                        <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_plugins)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Buy It Now"><?php if(in_array($plugin_info->slug, $appstore->installed_plugins)){echo "INSTALLED"; } else {echo "GET FREE";}?></a></span>
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
    $pages = array('store','view-plugin', 'view-theme', 'install-plugin', 'install-theme');
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
    }
}
function wp_appstore_myaccount() {
    echo "456";
}

add_action('admin_init', 'wp_appstore_admin_init');
add_action('admin_menu', 'wp_appstore_admin_menu');
?>