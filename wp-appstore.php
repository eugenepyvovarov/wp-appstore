<?php
/*
Plugin Name: WP AppStore
Plugin URI: http://wp-appstore.com
Description: Premium plugins and themes
Author: Lifeisgoodlabs   
Version: 1.0.5
Author URI: http://www.wp-appstore.com
*/


if ( ! class_exists('WP_Upgrader') )
 include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
 
include_once 'wp-appstore.class.php';
if(file_exists(WP_PLUGIN_DIR."/wp-appstore/tools/config.php"))
    include_once WP_PLUGIN_DIR."/wp-appstore/tools/config.php";
if (! function_exists('get_plugin_data'))
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');

 
function wp_appstore_admin_init() {
    wp_enqueue_style( 'wp-appstore-css', plugins_url( basename( dirname( __FILE__ ) ) . '/wp-appstore.css' ), false, '20110322' );
    wp_enqueue_style( 'wp-appstore-slider', plugins_url( basename( dirname( __FILE__ ) ) . '/assets/scrollable-horizontal.css' ), false, '20110322' );
    wp_enqueue_style( 'wp-appstore-slider-buttons', plugins_url( basename( dirname( __FILE__ ) ) . '/assets/scrollable-buttons.css' ), false, '20110322' );
    wp_enqueue_style( 'thickbox' );
    wp_enqueue_script( 'wp-appstore-slider-js', plugins_url( basename( dirname( __FILE__ ) )  . '/assets/jquery.tools.min.js'), array( 'jquery'), '20110322' );
    wp_enqueue_script('thickbox');
    if(get_option('wp_appstore_frontend_rescan') && function_exists('wp_appstore_frontend'))
        wp_appstore_frontend();
}
function wp_appstore_admin_menu() {
    add_menu_page( 'WP Appstore', 'WP AppStore', 'manage_options', basename( __FILE__ ), 'wp_appstore_main', null, 61 );
    add_submenu_page( basename( __FILE__ ), 'All Plugins', 'All Plugins', 'manage_options', basename( __FILE__ ).'&screen=all-plugins', 'wp_appstore_main' );
    add_submenu_page( basename( __FILE__ ), 'All Themes', 'All Themes', 'manage_options', basename( __FILE__ ).'&screen=all-themes', 'wp_appstore_main' );
    add_submenu_page( basename( __FILE__ ), 'Installed', 'Installed', 'manage_options', basename( __FILE__ ).'&screen=installed', 'wp_appstore_main' );
}
function wp_appstore_page_store($msg = false){
    $appstore = new WP_AppStore();
    $featured_plugins = $appstore->get_featured('plugin');
    $featured_themes = $appstore->get_featured('theme');
    
    $latest_plugins = $appstore->get_lastest('plugin');
    $latest_themes = $appstore->get_lastest('theme');
    $updates = get_option('wp_appstore_plugins_for_update', array());
    if (is_array($updates) && isset($updates['wp-appstore']))
        unset($updates['wp-appstore']);
    $stats = $appstore->get_stats();
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
        <?php if($msg) echo $msg; ?>
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
                    <p>Please send feedback to <a href="mailto:eugene@lifeisgoodlabs.com">eugene@lifeisgoodlabs.com</a></p>
                    <p><strong>Submit your plugins on <a href="https://github.com/bsn/wp-appstore" target="_blank">our github page</a>!</strong></p>
                </div>
            </div> 
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>Search</span></h3>
                <div class="inside">
                <form method="get" enctype="text/plain" action="">
                <input type="hidden" value="wp-appstore.php" name="page" />
                <input type="hidden" value="search" name="screen" />
                    <p class="searchbox">
	                   <label for="plugin-search-input" class="screen-reader-text">Search Formulas Library:</label>
	                   <input type="text" value="" name="s" id="plugin-search-input" style="margin-right: 1.5em;padding: .2em .3em;width: 95%;float: left;z-index: 999;"/>
	                   <ul class="search-options">
                            <li><input type="checkbox" value="1" checked="checked" name="plugin" title="Search Plugins" />Search Plugins</li>
                            <li><input type="checkbox" value="1" name="theme" title="Search Themes" />Search Themes</li>
                       </ul>
                       <input type="submit" value="Search" class="button" id="search-submit" name="" />
                    </p>
                </form>
                </div>
            </div>  
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>WP AppStore stats</span></h3>
                <div class="inside">
                    <?php if (get_option('wp_appstore_file_permissions_denied')):?>
                    <p>Automatic formulas update blocked on your site. Try to do it manually switching folder permissions to 0777 or let us try to do it</p>
                    <p><span class="buyoptions"><a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'force-formulas-update')));?>" class="button rbutton" title="Update It Now">Get Update Now!</a></span></p>
                    <?php else: ?>
                    <p><?php echo $stats['last_update']; ?></a></p>
                    <p>Plugin formulas: <?php echo $stats['plugins']; ?></p>
                    <p>Theme formulas: <?php echo $stats['themes']; ?></p>
                    <?php endif; ?>
					<?php if (defined('WP_APPSTORE_DEV') && WP_APPSTORE_DEV == true):?>
					<p>
						<a class="button rbutton" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'force-update','formulas'=>'true')));?>">Update Formulas</a>
                    	<a class="button rbutton" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'force-update','autoupdate'=>'true')));?>">Update WP AppStore</a>
					</p>
					<?php endif; ?>
                </div>
            </div>
            <?php if(sizeof($updates) > 0): ?>
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>NEW VERSIONS OF PLUGINS AVALIABLE!</span></h3>
                <div class="inside">
                    <p>We just received info about updates of next plugins:</p>
                    <ul>
                    <?php foreach($updates as $update): ?>
                        <li><a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-plugin','plugin_name'=>$update['object']->slug,'plugin_id'=>$update['object']->id)));?>" title="View Plugin Page"><?php echo $update['title'].' : Updated to version '.$update['object']->new_version; ?></a></li>
                    <?php endforeach; ?>
                    </ul>
                    </p>
                </div>
            </div>  
            <?php endif; ?>             

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
                <?php if(is_array($featured_plugins)): ?>
                <div id="namediv" class="stuffbox">
                    <h3><label for="link_name">Featured Plugins</label> - <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'featured-plugins')));?>" title="All Featured Plugins">All Featured Plugins</a></h3>
                    <div class="inside">
                        <?php foreach($featured_plugins as $one): ?>
                        <div class="plugin">
                            <img class="logo" src="<?php echo icon_path($one); ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));?>"><?php echo $one->title;?></a>
                            <span class="category"><?php echo $one->category_name ?></span>
                            <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_plugins)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Get It Now"><?php if(in_array($one->slug, $appstore->installed_plugins)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(is_array($latest_plugins)): ?>
                <div id="namediv1" class="stuffbox">
                    <h3><label for="link_name">Lastest Plugins</label> - <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'all-plugins')));?>" title="All Plugins">All Plugins</a></h3>
                    <div class="inside">
                        <?php foreach($latest_plugins as $one): ?>
                        <div class="plugin">
                            <img class="logo" src="<?php echo icon_path($one); ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));?>"><?php echo $one->title;?></a>
                            <span class="category"><?php echo $one->category_name ?></span>
                            <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_plugins)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Get It Now"><?php if(in_array($one->slug, $appstore->installed_plugins)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(is_array($featured_themes)): ?>
                <div id="addressdiv" class="stuffbox">
                    <h3><label for="link_url">Featured Themes</label> - <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'featured-themes')));?>" title="All Featured Themes">All Featured Themes</a></h3>
                    <div class="inside">
                        <?php foreach($featured_themes as $one): ?>
                        <div class="theme">
                            <img class="scrot" width="280px" src="<?php echo icon_path($one); ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));?>"><?php echo $one->title; ?></a>
                            <span class="category"><?php echo $one->category_name; ?></span>

                            <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_themes)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Get It Now"><?php if(in_array($one->slug, $appstore->installed_themes)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(is_array($latest_themes)): ?>
                <div id="addressdiv1" class="stuffbox">
                    <h3><label for="link_url">Lastest Themes</label> - <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'all-themes')));?>" title="All Themes">All Themes</a></h3>
                    <div class="inside">
                        <?php foreach($latest_themes as $one): ?>
                        <div class="theme">
                            <img class="scrot" width="280px" src="<?php echo icon_path($one); ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));?>"><?php echo $one->title; ?></a>
                            <span class="category"><?php echo $one->category_name; ?></span>

                            <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_themes)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Get It Now"><?php if(in_array($one->slug, $appstore->installed_themes)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
        
    </div>
    <?php
}
function wp_appstore_page_search_results($results){
    $plugins = array();
    $themes = array();
    if (isset($results['plugin']))
        $plugins = $results['plugin'];
    if (isset($results['theme']))
        $themes = $results['theme'];
    $updates = get_option('wp_appstore_plugins_for_update', array());
    if (is_array($updates) && isset($updates['wp-appstore']))
        unset($updates['wp-appstore']);
    $appstore = $results['appstore_object'];
    $stats = $appstore->get_stats();
    $tags = array();
    if (isset($results['tags']))
        $tags = $results['tags'];
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
                    <p>Please send feedback to <a href="mailto:eugene@lifeisgoodlabs.com">eugene@lifeisgoodlabs.com</a></p>
                    <p><strong>Submit your plugins on <a href="https://github.com/bsn/wp-appstore" target="_blank">our github page</a>!</strong></p>                
				</div>
            </div> 
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>Search</span></h3>
                <div class="inside">
                <form method="get" enctype="text/plain" action="">
                <input type="hidden" value="wp-appstore.php" name="page" />
                <input type="hidden" value="search" name="screen" />
                    <p class="searchbox">
	                   <label for="plugin-search-input" class="screen-reader-text">Search Formulas Library:</label>
                       <input type="text" value="<?php if(isset($results['keyword'])) echo $results['keyword']; ?>" name="s" id="plugin-search-input" style="margin-right: 1.5em;padding: .2em .3em;width: 95%;float: left;z-index: 999;"/>
	                   <ul class="search-options">
                            <li><input type="checkbox" value="1" checked="checked" name="plugin" title="Search Plugins" />Search Plugins</li>
                            <li><input type="checkbox" value="1" name="theme" title="Search Themes" />Search Themes</li>
                       </ul>
                       <input type="submit" value="Search" class="button" id="search-submit" name="" />
                    </p>
                </form>
                </div>
            </div>
            <?php if($tags): ?>
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>Tags:</span></h3>
                <div class="inside">
                    <p>
                    <?php foreach($tags as $tag): ?>
                    <?php
                    $tags_type = '';
                    if (sizeof($plugins) > 0) {
                            $tags_type = array('plugin'=>'1');
                    } 
                    if (sizeof($themes) > 0 ) {
                            $tags_type = array('theme'=>'1');
                    } 
                    if (sizeof($plugins) > 0 && sizeof($themes) > 0 ) {
                            $tags_type = '';
                    } 
                    ?>
                        <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'tag-filter','tag'=>$tag,$tags_type))); ?>" style="text-transform: capitalize; margin-right: 5px;"><?php echo $tag; ?></a>
                    <?php endforeach; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>  
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>WP AppStore stats</span></h3>
                <div class="inside">
                    <p><?php echo $stats['last_update']; ?></a></p>
                    <p>Plugins formulas in database: <?php echo $stats['plugins']; ?></p>
                    <p>Themes formulas in database: <?php echo $stats['themes']; ?></p>
                </div>
            </div>
            <?php if(sizeof($updates) > 0): ?>
            <div id="" class="postbox " style="">
                <h3 class="hndle"><span>NEW VERSIONS OF PLUGINS AVALIABLE!</span></h3>
                <div class="inside">
                    <p>We just received info about updates of next plugins:</p>
                    <ul>
                    <?php foreach($updates as $update): ?>
                        <li><a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-plugin','plugin_name'=>$update['object']->slug,'plugin_id'=>$update['object']->id)));?>" title="View Plugin Page"><?php echo $update['title'].' : Updated to version '.$update['object']->new_version; ?></a></li>
                    <?php endforeach; ?>
                    </ul>
                    </p>
                </div>
            </div>  
            <?php endif; ?>             
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
            <?php if(isset($results['serched_for'])): ?>
            <h2><?php echo $results['serched_for']; ?></h2>
            <?php endif; ?>
                <?php if(sizeof($plugins) > 0): ?>
                <div id="namediv" class="stuffbox">
                    <h3><label for="link_name">Plugins</label></h3>
                    <div class="inside">
                        <?php foreach($plugins as $one): ?>
                        <div class="plugin">
                            <img class="logo" src="<?php echo icon_path($one); ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));?>"><?php echo $one->title;?></a>
                            <span class="category"><?php echo $one->category_name; ?></span>
                            <span class="buyoptions"><a href="<?php if(array_key_exists($one->slug, $updates)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'plugin-update','plugin_name'=>$one->slug)));} elseif(!in_array($one->slug, $appstore->installed_plugins)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-plugin','plugin_name'=>$one->slug,'plugin_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Buy It Now"><?php if(array_key_exists($one->slug, $updates)){echo "UPDATE";} elseif(in_array($one->slug, $appstore->installed_plugins)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if(sizeof($themes) > 0): ?>
                <div id="addressdiv" class="stuffbox">
                    <h3><label for="link_url">Themes</label></h3>
                    <div class="inside">
                        <?php foreach($themes as $one): ?>
                        <div class="theme">
                            <img class="scrot" width="280px" src="<?php echo icon_path($one); ?>" alt="" />
                            <a class="title" href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'view-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));?>"><?php echo $one->title; ?></a>
                            <span class="category"><?php echo $one->category_name; ?></span>
                            <span class="buyoptions"><a href="<?php if(!in_array($one->slug, $appstore->installed_themes)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-theme','theme_name'=>$one->slug,'theme_id'=>$one->id)));}else{echo "#";}?>" class="button rbutton" title="Buy It Now"><?php if(in_array($one->slug, $appstore->installed_themes)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
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
    $updates = get_option('wp_appstore_plugins_for_update', array());
    if (is_array($updates) && isset($updates['wp-appstore']))
        unset($updates['wp-appstore']);
    $tags = $appstore->sort_item_tags($plugin_info, 'plugin');
    ?>
    <script type="text/javascript">
	jQuery(document).ready(function($) {
	   $('a.tags-trigger').click(function(){
	       $('.tags-annotation').toggle('slow');
           $('.tags-all').toggle('slow');
	   });
	});
    </script>
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
                                <?php if(sizeof($tags) > 0): ?>
                                <li>Tags:
                                <div class="tags-annotation collapsable" style="display: block;">
                                <?php for ($i=0;$i<=4 ;$i++):?>
                                <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'tag-filter','tag'=>$tags[$i],'plugin'=>'1'))); ?>" style="text-transform: capitalize; margin-right: 5px;"><?php echo $tags[$i]; ?></a>
                                <?php endfor; ?>
                                <a href="#" class="tags-trigger">All tags...</a>
                                </div>
                                <div class="tags-all collapsable" style="display: none;">
                                <?php foreach($tags as $tag): ?>
                                <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'tag-filter','tag'=>$tag,'plugin'=>'1'))); ?>" style="text-transform: capitalize; margin-right: 5px;"><?php echo $tag; ?></a>
                                <?php endforeach; ?>
                                </div>
                                </li>
                                <?php endif; ?>
                                <li>Rating:
                                <div title="<?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $plugin_info->votes ), number_format_i18n( $plugin_info->votes ) ) ?>" class="star-holder">
            					<div style="width: <?php echo esc_attr( $plugin_info->rating ) ?>px" class="star star-rating"></div>
            					<div class="star star5"><img alt="5 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star4"><img alt="4 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star3"><img alt="3 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star2"><img alt="2 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star1"><img alt="1 star" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            				    </div>
                                </li>
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
                        <img class="logo" src="<?php echo icon_path($plugin_info); ?>" alt="" />
                        <span class="buyoptions"><a href="<?php if(array_key_exists($plugin_info->slug, $updates)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'plugin-update','plugin_name'=>$plugin_info->slug)));} elseif(!in_array($plugin_info->slug, $appstore->installed_plugins)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-plugin','plugin_name'=>$plugin_info->slug,'plugin_id'=>$plugin_info->id)));}else{echo "#";}?>" class="button rbutton" title="Get It Now"><?php if(array_key_exists($plugin_info->slug, $updates)){echo "UPDATE";} elseif(in_array($plugin_info->slug, $appstore->installed_plugins)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                        <?php if (defined('WP_APPSTORE_DEV') && WP_APPSTORE_DEV == true):?>
                        <?php if(in_array($plugin_info->slug, $appstore->installed_plugins)): ?>
                        <span class="buyoptions"><a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'force-update','plugin'=>$plugin_info->id)));?>" class="button rbutton" title="Get It Now">REINSTALL</a></span>
                        <?php endif; ?>
                        <?php endif; ?>
                        </div>
                        <h2><?php echo $plugin_info->title; ?></h2>
                        <div class="description">
							<?php echo $plugin_info->description; ?>
                        </div>
                        <div class="latest_changes"></div>
                    </div>
                    <?php if(count($plugin_info->screenshots)>0): ?>
                    <!-- slider -->
                    <!-- wrapper element for the large image -->
                    <div id="image_wrap">
                    
                    	<!-- Initially the image is a simple 1x1 pixel transparent GIF -->
                    	<img src="<?php echo plugins_url( 'assets/img/blank.gif', __FILE__ ); ?>" width="530" />
                    
                    </div>


                    <!-- "previous page" action -->
                    <a class="prev browse left"></a>
                    
                    <!-- root element for scrollable -->
                    <div class="scrollable">   
                       
                       <!-- root element for the items -->
                       <div class="items">
                          <div>
                          <?php for ($i=1;$i <= count($plugin_info->screenshots); $i++): ?>
                            <?php if(!($i % 5)) echo '</div><div>'; ?>
                            <?php echo '<img src="'.$plugin_info->screenshots[$i-1].'" />' ?>
                          <?php endfor; ?>
                          </div>  
                       </div>
                       
                    </div>

                    <!-- "next page" action -->
                    <a class="next browse right"></a>
                    <script>
                    // execute your scripts when the DOM is ready. this is mostly a good habit
                    jQuery(document).ready(function($) {
                    
                    	// initialize scrollable
                    	$(".scrollable").scrollable();
                        $(".items img").click(function() {
                    
                    	// see if same thumb is being clicked
                    	if ($(this).hasClass("active")) { return; }
                    
                    	// calclulate large image's URL based on the thumbnail URL (flickr specific)
                    	var url = $(this).attr("src")//.replace("_t", "");
                    
                    	// get handle to element that wraps the image and make it semi-transparent
                    	var wrap = $("#image_wrap").fadeTo("medium", 0.5);
                    
                    	// the large image from www.flickr.com
                    	var img = new Image();
                    
                    
                    	// call this function after it's loaded
                    	img.onload = function() {
                    
                    		// make wrapper fully visible
                    		wrap.fadeTo("fast", 1);
                    
                    		// change the image
                    		wrap.find("img").attr("src", url);
                    
                    	};
                    
                    	// begin loading the image from www.flickr.com
                    	img.src = url;
                    
                    	// activate item
                    	$(".items img").removeClass("active");
                    	$(this).addClass("active");
                    
                    // when page loads simulate a "click" on the first image
                    }).filter(":first").click();
                    
                    });
                    </script>


                    <!-- endof slider -->
                    <?php endif; ?>
                </div>
            </div>
            </div>
    </div>
    <?php
}

function wp_appstore_page_view_theme($theme_info){
    $appstore = new WP_AppStore();
    $updates = get_option('wp_appstore_themes_for_update', array());
    $tags = $appstore->sort_item_tags($theme_info, 'theme');
    ?>
    <script type="text/javascript">
	jQuery(document).ready(function($) {
	   $('a.tags-trigger').click(function(){
	       $('.tags-annotation').toggle('slow');
           $('.tags-all').toggle('slow');
	   });
	});
    </script>
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
                                <li>Version: <?php echo $theme_info->version; ?></li>
                                <?php if(sizeof($tags) > 0): ?>
                                <li>Tags:
                                <div class="tags-annotation collapsable" style="display: block;">
                                <?php for ($i=0;$i<=4 ;$i++):?>
                                <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'tag-filter','tag'=>$tags[$i],'theme'=>'1'))); ?>" style="text-transform: capitalize; margin-right: 5px;"><?php echo $tags[$i]; ?></a>
                                <?php endfor; ?>
                                <a href="#" class="tags-trigger">All tags...</a>
                                </div>
                                <div class="tags-all collapsable" style="display: none;">
                                <?php foreach($tags as $tag): ?>
                                <a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'tag-filter','tag'=>$tag,'theme'=>'1'))); ?>" style="text-transform: capitalize; margin-right: 5px;"><?php echo $tag; ?></a>
                                <?php endforeach; ?>
                                </div>
                                </li>
                                <?php endif; ?>
                                <li>Rating:
                                <div title="<?php printf( _n( '(based on %s rating)', '(based on %s ratings)', $theme_info->votes ), number_format_i18n( $theme_info->votes ) ) ?>" class="star-holder">
            					<div style="width: <?php echo esc_attr( $theme_info->rating ) ?>px" class="star star-rating"></div>
            					<div class="star star5"><img alt="5 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star4"><img alt="4 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star3"><img alt="3 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star2"><img alt="2 stars" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            					<div class="star star1"><img alt="1 star" src="<?php echo plugins_url( 'images/gray-star.png?v=20110615', __FILE__ ); ?>"></div>
            				    </div>
                                </li>
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
                        <span class="buyoptions"><a href="<?php if(array_key_exists($theme_info->slug, $updates)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'theme-update','theme'=>$theme_info->slug)));} elseif(!in_array($theme_info->slug, $appstore->installed_themes)){echo esc_attr(WP_AppStore::admin_url(array('screen'=>'install-theme','theme'=>$theme_info->slug,'theme_id'=>$theme_info->id)));}else{echo "#";}?>" class="button rbutton" title="Get It Now"><?php if(array_key_exists($theme_info->slug, $updates)){echo "UPDATE";} elseif(in_array($theme_info->slug, $appstore->installed_themes)){echo "INSTALLED"; } else {echo "INSTALL";}?></a></span>
                        <?php if (defined('WP_APPSTORE_DEV') && WP_APPSTORE_DEV == true):?>
                        <?php if(in_array($theme_info->slug, $appstore->installed_themes)): ?>
                        <span class="buyoptions"><a href="<?php echo esc_attr(WP_AppStore::admin_url(array('screen'=>'force-update','theme'=>$theme_info->id)));?>" class="button rbutton" title="Get It Now">REINSTALL</a></span>
                        <?php endif; ?>
                        <?php endif; ?>
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
                    <!-- slider -->
                    <!-- wrapper element for the large image -->
                    <div id="image_wrap">
                    
                    	<!-- Initially the image is a simple 1x1 pixel transparent GIF -->
                    	<img src="<?php echo plugins_url( 'assets/img/blank.gif', __FILE__ ); ?>" width="530" />
                    
                    </div>


                    <!-- "previous page" action -->
                    <a class="prev browse left"></a>
                    
                    <!-- root element for scrollable -->
                    <div class="scrollable">   
                       
                       <!-- root element for the items -->
                       <div class="items">
                          <div>
                          <?php for ($i=1;$i <= count($theme_info->screenshots); $i++): ?>
                            <?php if(!($i % 5)) echo '</div><div>'; ?>
                            <?php echo '<img src="'.$theme_info->screenshots[$i-1].'" />' ?>
                          <?php endfor; ?>
                          </div>  
                       </div>
                       
                    </div>

                    <!-- "next page" action -->
                    <a class="next browse right"></a>
                    <script>
                    // execute your scripts when the DOM is ready. this is mostly a good habit
                    jQuery(document).ready(function($) {
                    
                    	// initialize scrollable
                    	$(".scrollable").scrollable();
                        $(".items img").click(function() {
                    
                    	// see if same thumb is being clicked
                    	if ($(this).hasClass("active")) { return; }
                    
                    	// calclulate large image's URL based on the thumbnail URL (flickr specific)
                    	var url = $(this).attr("src")//.replace("_t", "");
                    
                    	// get handle to element that wraps the image and make it semi-transparent
                    	var wrap = $("#image_wrap").fadeTo("medium", 0.5);
                    
                    	// the large image from www.flickr.com
                    	var img = new Image();
                    
                    
                    	// call this function after it's loaded
                    	img.onload = function() {
                            
                    		// make wrapper fully visible
                    		wrap.fadeTo("fast", 1);
                    
                    		// change the image
                    		wrap.find("img").attr("src", url);
                            
                    	};
                        
                    	// begin loading the image from www.flickr.com
                    	img.src = url;
                    
                    	// activate item
                    	$(".items img").removeClass("active");
                    	$(this).addClass("active");
                    
                    // when page loads simulate a "click" on the first image
                    }).filter(":first").click();
                    
                    });
                    </script>


                    <!-- endof slider -->
                    <?php endif; ?>
                </div>
            </div>
            </div>
    </div>
    <?php
}

function wp_appstore_main() {
    $pages = array('store', 'search', 'tag-filter', 'all-plugins', 'all-themes', 'view-plugin', 'view-theme', 'install-plugin', 'install-theme', 'autoupdate', 'plugin-update', 'theme-update', 'installed', 'featured-plugins', 'featured-themes', 'force-formulas-update', 'force-update');
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
        case 'search':
            if(isset($_GET['s']) && (strlen($_GET['s']) > 2)){
                //s=keyword&plugin=1&theme=1
                if (isset($_GET['plugin']))
                    $type = 'plugin';
                elseif(isset($_GET['theme']))
                    $type = 'theme';
                else
                    $type = '';
                if (isset($_GET['plugin']) && isset($_GET['theme']))
                    $type = '';
                $appstore = new WP_AppStore();
                $result = $appstore->search($_GET['s'], $type);
                if ( !$result['plugin'] && !$result['theme'] )
                    $result['serched_for'] = sprintf('No matches found for "%s" in formulas library', $_GET['s']);
                else
                    $result['serched_for'] = sprintf('Search results for "%s" from formulas library:', $_GET['s']);
                $result['keyword'] = $_GET['s'];
                $result['appstore_object'] = $appstore;
                if(sizeof($result) > 0){
                    wp_appstore_page_search_results($result);
                } else {
                    wp_appstore_page_store();
                }
            } else {
                wp_appstore_page_store();
            }
            break;
        case 'installed':

                $appstore = new WP_AppStore();
                $result['plugin'] = $appstore->get_installed('plugin');
                $result['theme'] = $appstore->get_installed('theme');

                $result['serched_for'] = 'Plugins and Themes installed in your system:';
                $result['appstore_object'] = $appstore;

                wp_appstore_page_search_results($result);
            break;
        case 'featured-plugins':

                $appstore = new WP_AppStore();
                $result['plugin'] = $appstore->get_featured('plugin', false, false);
                $result['tags'] = $appstore->get_tags('plugin', false);
                $result['serched_for'] = 'All featured plugins:';
                $result['appstore_object'] = $appstore;

                wp_appstore_page_search_results($result);
            break;
        case 'featured-themes':

                $appstore = new WP_AppStore();
                $result['plugin'] = $appstore->get_featured('theme', false, false);
                $result['tags'] = $appstore->get_tags('theme', false);
                $result['serched_for'] = 'All featured themes:';
                $result['appstore_object'] = $appstore;

                wp_appstore_page_search_results($result);
            break;
        case 'tag-filter':
            if(isset($_GET['tag']) && (strlen($_GET['tag']) > 2)){
                //s=keyword&plugin=1&theme=1
                if (isset($_GET['plugin']))
                    $type = 'plugin';
                elseif(isset($_GET['theme']))
                    $type = 'theme';
                else
                    $type = '';
                if (isset($_GET['plugin']) && isset($_GET['theme']))
                    $type = '';
                $appstore = new WP_AppStore();
                $result = $appstore->get_items_by_tag($_GET['tag'], $type);
                if ( !$result['plugin'] && !$result['theme'] )
                    $result['serched_for'] = sprintf('Ooops. Seems like we don\'t have "%s" tag attached to any items in formulas library', $_GET['tag']);
                else
                    $result['serched_for'] = sprintf('Items with "%s" tag from formulas library:', $_GET['tag']);
                $result['tag'] = $_GET['tag'];
                $result['appstore_object'] = $appstore;
                if(sizeof($result) > 0){
                    wp_appstore_page_search_results($result);
                } else {
                    wp_appstore_page_store();
                }
            } else {
                wp_appstore_page_store();
            }
            break;
            
        case 'all-plugins':
            $appstore = new WP_AppStore();
            if($result['plugin'] = $appstore->get_plugins()){
                $result['tags'] = $appstore->get_tags('plugin', false);
                $result['appstore_object'] = $appstore;
                if(sizeof($result) > 0){
                    wp_appstore_page_search_results($result);
                } else {
                    wp_appstore_page_store();
                }
            } else {
                wp_appstore_page_store();
            }
            break;
        case 'all-themes':
            $appstore = new WP_AppStore();
            if($result['theme'] = $appstore->get_themes()){
                $result['tags'] = $appstore->get_tags('theme', false);
                $result['appstore_object'] = $appstore;
                if(sizeof($result) > 0){
                    wp_appstore_page_search_results($result);
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
                    if (!$package = wp_appstore_prepare_package($plugin_info->link, $plugin_info->slug))
                            $package = $plugin_info->link;
                    $api->download_link = $package;
                    
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
                        if (!$package = wp_appstore_prepare_package($theme_info->link, $theme_info->slug))
                            $package = $theme_info->link;
                        $api->download_link = $package;
                        
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
                    $plugin_slug = 'wp-appstore';
                    $plugin = 'wp-appstore/wp-appstore.php';
                    $current = get_site_transient( 'update_plugins' );
                    if (!is_object($current)) {
                        wp_update_plugins();
                    }
                    $for_update = get_option('wp_appstore_plugins_for_update');
                    if (isset($for_update[$plugin_slug]['file'])) {
                        if (!isset($current->response[$plugin])) {
                            if ($package = wp_appstore_prepare_package($for_update[$plugin_slug]['object']->package, $for_update[$plugin_slug]['object']->slug))
                                $for_update[$plugin_slug]['object']->package = $package;
                            $current->response[$plugin] = $for_update[$plugin_slug]['object'];
                            set_site_transient('update_plugins', $current);
                        }
                    }
                    if (!isset($for_update[$plugin_slug]['file']) &&  !isset($current->response[$plugin])) {
                        delete_option('wp_appstore_autoupdate_request');
                        wp_appstore_page_store();
                    }
            		$title = sprintf( __('Update Plugin: %s'), 'WP Appstore');
                    
                    $parent_file = 'plugins.php';
                    $submenu_file = 'plugins.php';
                    
            		$nonce = 'upgrade-plugin_' . $plugin;
                    $url = 'admin.php?page=wp-appstore.php&screen=autoupdate';
            		$upgrader = new Plugin_Upgrader( new WPAppstore_Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
            		$result = $upgrader->upgrade($plugin);
                    if ($result !== false && !is_wp_error($result)) {
                        unset($for_update[$plugin_slug]);
                        update_option('wp_appstore_plugins_for_update', $for_update);
                        delete_option('wp_appstore_autoupdate_request');
                    }
            }
            break;
        case 'plugin-update':
            if ( ! current_user_can('update_plugins') )
    			wp_die(__('You do not have sufficient permissions to update plugins for this site.'));
             if(isset($_GET['plugin_name']) && (strlen($_GET['plugin_name']) > 1)){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                // $plugin here is 'folder/file.php', $plugin_slug is a slug.
                    $plugin_slug = $_GET['plugin_name'];
                    $current = get_site_transient( 'update_plugins' );
                    if (!is_object($current)) {
                        wp_update_plugins();
                    }
                    $for_update = get_option('wp_appstore_plugins_for_update');
                    if (!$plugin = $for_update[$plugin_slug]['file']) {
                        $plugin = wp_appstore_get_plugin_string_for_update($plugin_slug);
                        if (!isset($current->response[$plugin])) {
                          wp_die(__('Error occured while update of this plugin.'));  
                        }
                    }else{
                        if (!isset($current->response[$plugin])) {
                            if ($package = wp_appstore_prepare_package($for_update[$plugin_slug]['object']->link, $for_update[$plugin_slug]['object']->slug))
                                $for_update[$plugin_slug]['object']->link = $package;
                            $current->response[$plugin] = $for_update[$plugin_slug]['object'];
                            set_site_transient('update_plugins', $current);
                        }
                    }
          			$title = __('Update Plugin');
                    
                    $parent_file = 'plugins.php';
                    $submenu_file = 'plugins.php';
                    
            		$nonce = 'upgrade-plugin_' . $plugin;

                    $url = 'admin.php?page=wp-appstore.php&screen=plugin-update&plugin_name='. $plugin_slug ;

            		$upgrader = new Plugin_Upgrader( new WPAppstore_Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
                    $upgrader->upgrade($plugin);
                    unset($for_update[$plugin_slug]);
                    update_option('wp_appstore_plugins_for_update', $for_update);
            }
            break;
        case 'theme-update':
            if ( ! current_user_can('update_themes') )
    			wp_die(__('You do not have sufficient permissions to update themes for this site.'));
             if(isset($_GET['theme']) && (strlen($_GET['theme']) > 1)){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                // $plugin here is 'folder/file.php', $plugin_slug is a slug.
                    $theme = $_GET['theme'];
                    $current = get_site_transient( 'update_themes' );
                    if (!is_object($current)) {
                        wp_update_themes();
                    }
                    $for_update = get_option('wp_appstore_themes_for_update');
                    if (!isset($for_update[$theme])) {
                        if (!isset($current->response[$theme])) {
                          wp_die(__('Error occured while update of this plugin.'));  
                        }
                    }else{
                        if (!isset($current->response[$theme])) {
                            if ($package = wp_appstore_prepare_package($for_update[$theme]['object']->link, $theme))
                                $for_update[$plugin_slug]['object']->link = $package;
                            $current->response[$theme] = $for_update[$theme]['object'];
                            set_site_transient('update_plugins', $current);
                        }
                    }

                    $title = __('Update Theme');
            		$parent_file = 'themes.php';
            		$submenu_file = 'themes.php';
            
            		$nonce = 'upgrade-theme_' . $theme;
            		$url = 'update.php?action=upgrade-theme&theme=' . $theme;
                    $url = 'admin.php?page=wp-appstore.php&screen=theme-update&theme='. $theme ;
            
            		$upgrader = new Theme_Upgrader( new WPAppstore_Theme_Upgrader_Skin( compact('title', 'nonce', 'url', 'theme') ) );
            		$upgrader->upgrade($theme);
                    unset($for_update[$theme]);
                    update_option('wp_appstore_themes_for_update', $for_update);
            }
            break;
        case 'force-update':
            if ( ! current_user_can('update_themes') )
    			wp_die(__('You do not have sufficient permissions to update themes for this site.'));
             if(isset($_GET['theme']) && (intval($_GET['theme']) > 0)){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                // $plugin here is 'folder/file.php', $plugin_slug is a slug.
                    $theme_id = $_GET['theme'];
                    $current = get_site_transient( 'update_themes' );
                    if (!is_object($current)) {
                        wp_update_themes();
                    }

                    $appstore = new WP_AppStore();
                    $theme_object = $appstore->get_theme($theme_id);
                    if (is_object($theme_object)) {
                        $theme = $theme_object->slug;
                        $api = array();
                        $api['new_version'] = $theme_object->version;
                        $api['url'] = $theme_object->homepage;
                        if (!$package = wp_appstore_prepare_package($theme_object->link, $theme_object->slug))
                            $package = $theme_object->link;
                        $api['package'] = $package;
                        
                        if (!isset($current->response[$theme])) {
                            $current->response[$theme] = $api;
                            set_site_transient('update_themes', $current);
                        }
                    }else{
                        wp_die(__('Update failed'));
                    }
                    
                    $title = __('Update Theme');
            		$parent_file = 'themes.php';
            		$submenu_file = 'themes.php';
            
            		$nonce = 'upgrade-theme_' . $theme;
                    $url = 'admin.php?page=wp-appstore.php&screen=force-update&theme='. $theme_id ;
            
            		$upgrader = new Theme_Upgrader( new WPAppstore_Theme_Upgrader_Skin( compact('title', 'nonce', 'url', 'theme') ) );
            		$upgrader->upgrade($theme);
            }
            if(isset($_GET['autoupdate'])){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                    $plugin_slug = 'wp-appstore';
                    $plugin = 'wp-appstore/wp-appstore.php';
                    $current = get_site_transient( 'update_plugins' );
                    if (!is_object($current)) {
                        wp_update_plugins();
                    }
                        $api = new StdClass;
                        $api->id = 1;
                        $api->slug = 'wp-appstore';
                        $api->new_version = 999;
                        $api->url = "http://github.com/bsn/wp-appstore";
                        $download_url = "https://github.com/bsn/wp-appstore/zipball/master";
                        if (defined('WP_APPSTORE_AUTOUPDATE_URL') && WP_APPSTORE_AUTOUPDATE_URL == true)
                            $download_url = "https://github.com/bsn/wp-appstore/zipball/DeV";
                        if (!$package = wp_appstore_prepare_package($download_url, 'wp-appstore'))
                            $package = $download_url;
                        $api->package = $package;
                        $current->response[$plugin] = $api;
                        set_site_transient('update_plugins', $current);   
                    
            		$title = sprintf( __('Update Plugin: %s'), 'WP Appstore');
                    
                    $parent_file = 'plugins.php';
                    $submenu_file = 'plugins.php';
                    require_once(ABSPATH . 'wp-admin/admin-header.php');
                    
            		$nonce = 'upgrade-plugin_' . $plugin;
                    $url = 'admin.php?page=wp-appstore.php&screen=force-update&autoupdate=true' ;

            		$upgrader = new Plugin_Upgrader( new WPAppstore_Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin') ) );
            		$upgrader->upgrade($plugin);
                    include(ABSPATH . 'wp-admin/admin-footer.php');
            }
            if(isset($_GET['plugin']) && (intval($_GET['plugin']) > 0)){
                // include_once ABSPATH . 'wp-admin/includes/plugin-install.php'; //for plugins_api..
                // check_admin_referer('install-plugin_' . $plugin);
                // $plugin here is 'folder/file.php', $plugin_slug is a slug.
                    $plugin_id = $_GET['plugin'];
                    $current = get_site_transient( 'update_plugins' );
                    if (!is_object($current)) {
                        wp_update_plugins();
                    }
                    $appstore = new WP_AppStore();

                    $plugin = $appstore->get_plugin($plugin_id);
                    if (is_object($plugin)) {
                        $plugin_slug = $plugin->slug;
                        $api = new StdClass;
                        $api->id = $plugin->id;
                        $api->slug = $plugin->slug;
                        $api->new_version = 999;
                        $api->url = $plugin->homepage;
                        if (!$package = wp_appstore_prepare_package($plugin->link, $plugin->slug))
                            $package = $plugin->link;
                        $api->package = $package;
                        $string = wp_appstore_get_plugin_string_for_update($plugin->slug);
                        if(!$string)
                            wp_die(__('Update failed'));
                        $current->response[$string] = $api;
                        set_site_transient('update_plugins', $current);
                        
                    }else{
                        wp_die(__('Update failed'));
                    }
   
          			$title = __('Update Plugin');
                    
                    $parent_file = 'plugins.php';
                    $submenu_file = 'plugins.php';
                    
            		$nonce = 'upgrade-plugin_' . $plugin_slug;

            		$url = 'admin.php?page=wp-appstore.php&screen=force-update&plugin='. $api->id ;

            		$upgrader = new Plugin_Upgrader( new WPAppstore_Plugin_Upgrader_Skin( compact('title', 'nonce', 'url', 'plugin_slug') ) );
                    //set_site_transient('update_plugins', $current);
                    $upgrader->upgrade($string);
            }
            if(isset($_GET['formulas'])){
                update_option('wp_appstore_formulas_rescan', true);
                $appstore = new WP_AppStore();
                wp_appstore_page_store();
            }
            break;
        case 'force-formulas-update':
            if ( ! current_user_can('update_plugins') )
    			wp_die(__('You do not have sufficient permissions to update plugins for this site.'));
            if (!get_option('wp_appstore_file_permissions_denied'))
                wp_appstore_page_store();
            //check_admin_referer('upgrade-formulas');
            global $wp_filesystem;
            $url = 'admin.php?page=wp-appstore.php&screen=force-formulas-update';
    		if ( false === ($credentials = wp_appstore_request_filesystem_credentials($url)) )
                exit('Filesystem access fail!');
                //return false;

    		if ( ! WP_Filesystem($credentials) ) {
    			$error = true;
    			if ( is_object($wp_filesystem) && $wp_filesystem->errors->get_error_code() )
    				$error = $wp_filesystem->errors;
    			$this->skin->wp_appstore_request_filesystem_credentials($url, '', $error); //Failed to connect, Error and request again
                exit('Filesystem access fail!');
                //return false;
    		}

    		if ( ! is_object($wp_filesystem) )
                exit('Filesystem access fail!');
    
    		if ( is_wp_error($wp_filesystem->errors) && $wp_filesystem->errors->get_error_code() )
    			 exit('Filesystem access error:'.$wp_filesystem->errors);
            
            wp_appstore_update_formulas();
            delete_option('wp_appstore_file_permissions_denied');
            $msg = '<div class="updated" id="message"><p>Updated successfully</p></div>';
            wp_appstore_page_store($msg);
            break;
    }
}
function wp_appstore_request_filesystem_credentials($url, $type = '', $error = false, $context = false) {
        if (!$context)
            $context = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'formulas';
        $nonce = 'upgrade-formulas';
        $url = wp_nonce_url($url, $nonce);
		return request_filesystem_credentials($url, '', $error, $context); //Possible to bring inline, Leaving as is for now.
	}
function wp_appstore_check_for_force_formulas_update() {
    $path = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'formulas';
    $files = scandir($path, 1);
    if (is_writable($path.DIRECTORY_SEPARATOR.$files[0])){
        delete_option('wp_appstore_file_permissions_denied');
        return true;
    }else{
        update_option('wp_appstore_file_permissions_denied', true);
        return false;
    }
}
function wp_appstore_myaccount() {
   //var_dump(get_posts());
}
function icon_path($item_object){
    if (strlen($item_object->icon) < 5)
        return plugins_url( 'images/'.$item_object->category_slug.'.png', __FILE__ );
    else
        return $item_object->icon;
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
        
    if  ( function_exists('sys_get_temp_dir') ) {
		$temp = sys_get_temp_dir(). '/';
		if ( @is_writable($temp) )
			return $temp;
	}
	$temp = '/tmp/';
    if ( is_dir($temp) && @is_writable($temp) )
	   return $temp;
    return false;
}

function wp_appstore_update_formulas() {
    @ini_set( 'max_execution_time', 360 );
    @set_time_limit( 360 );
    
    global $wp_filesystem;
    if (!get_tmp_path()) {
        wp_die(__('You do not have sufficient permissions to update formulas on this site.'));
    }
    if (!wp_appstore_check_for_force_formulas_update() && !is_object($wp_filesystem)) {
        wp_die(__('You do not have sufficient permissions to update formulas on this site. Check your filesystem.'));
    }

    //$core_path = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'wp_appstore.php';
    $tmp_file_name = get_tmp_path().'tmp.zip';
    $download_url = "https://github.com/bsn/wp-appstore/zipball/master";
    if (defined('WP_APPSTORE_FORMULAS_URL') && WP_APPSTORE_FORMULAS_URL == true)
        $download_url = "https://github.com/bsn/wp-appstore/zipball/DeV";
    $file = file_get_contents($download_url);
    file_put_contents($tmp_file_name, $file);
    
    $path = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'formulas';
    if (is_object($wp_filesystem))
        $path = $wp_filesystem->wp_plugins_dir().'wp-appstore'.DIRECTORY_SEPARATOR.'formulas';
    
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
                    $for_update['wp-appstore'] = array('file' => 'wp-appstore/wp-appstore.php', 'object' => $api);
                    update_option('wp_appstore_plugins_for_update', $for_update);
                    update_option('wp_appstore_autoupdate_request', true);
                }
            }
          }
          
          if(preg_match('|\.ini$|', zip_entry_name($zip_entry))){
            $filename = strrchr(zip_entry_name($zip_entry),'/');
            if (is_object($wp_filesystem)) {
                $wp_filesystem->put_contents(
                  $path.$filename,
                  $buf,
                  0777 // predefined mode settings for WP files
                );
            }else
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
    update_option('wp_appstore_last_lib_update', time());
    if (function_exists('wp_appstore_frontend')) {
        wp_appstore_frontend();
    }
}

function wp_appstore_get_plugin_string_for_update($plugin_slug){
    if(!$plugin_slug)
        return false;
    $plugins = get_plugins();
    
    if(is_array($plugins)){
        foreach ($plugins as $path => $plugin_data) {
            $exploded_path = explode('/', $path);
            if(preg_match('|\.php$|', $exploded_path[0])){
                $ext = strrchr($exploded_path[0], '.'); 
                
                if($ext !== false) 
                    $exploded_path[0] = substr($exploded_path[0], 0, -strlen($ext));
            }
            if ($exploded_path[0] == $plugin_slug) {
                return $path;
            }
        }
    }
    return false;
}
function wp_appstore_prepare_package($url, $folder_slug){
    if (!$file = file_get_contents($url)) {
        return false;
    }
    if (!get_tmp_path()) {
        return false;
    }
    $tmp_file_name = get_tmp_path().'tmp.zip';
    file_put_contents($tmp_file_name, $file);
    $zip = new ZipArchive;
    $res = $zip->open( $tmp_file_name );
    if ($res === TRUE) {
        $folder = rtrim($folder_slug, '/\\').'/';
        $root_element = $zip->statIndex(0);
        $search = $root_element['name'];
        if ($folder == $search || $root_element['size'] > 0) {
            $zip->close();
            return $tmp_file_name;
        }
        for($i = 0; $i < $zip->numFiles; $i++)
         {  
            $new_name = str_replace($search, $folder, $zip->getNameIndex($i));
            $zip->renameIndex($i, $new_name);
         } 

        $zip->close();
        return $tmp_file_name;
    } else {
        return false;
    }
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
  `category_slug` VARCHAR(255) NOT NULL DEFAULT 'uncategorized',
  `category_name` VARCHAR(255) NOT NULL DEFAULT 'Uncategorized',
  `link` varchar(255) NOT NULL,
  `icon` VARCHAR(255) NOT NULL,
  `homepage` varchar(255) NOT NULL,
  `featured` INT(1) NOT NULL DEFAULT '0',
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
  `category_slug` VARCHAR(255) NOT NULL DEFAULT 'none',
  `category_name` VARCHAR(255) NOT NULL DEFAULT 'Not Categorized',
  `link` varchar(255) NOT NULL,
  `icon` VARCHAR(255) NOT NULL,
  `preview_url` varchar(255) NOT NULL,
  `homepage` varchar(255) NOT NULL,
  `featured` INT(1) NOT NULL DEFAULT '0',
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
    //wp_appstore_update_formulas();
    update_option('wp_appstore_formulas_rescan', true);
    update_option('wp_appstore_last_lib_update', time());
    $a = new WP_AppStore;
	wp_schedule_event(time(), 'daily', 'wp_appstore_daily_event');
    if(has_action('wp_appstore_twicedaily_event'))
    wp_schedule_event(time(), 'twicedaily', 'wp_appstore_twicedaily_event');
}
function wp_appstore_deactivation() {
	wp_clear_scheduled_hook('wp_appstore_daily_event');
    wp_clear_scheduled_hook('wp_appstore_twicedaily_event');
}
function wp_appstore_uninstall() {
    global $wpdb;
    wp_clear_scheduled_hook('wp_appstore_daily_event');
    wp_clear_scheduled_hook('wp_appstore_twicedaily_event');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_plugins');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_plugins_tags');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_screenshots');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_themes');
    $wpdb->query('DROP TABLE IF EXISTS ' . $wpdb->prefix . 'appstore_themes_tags');
    delete_option('wp_appstore_formulas_rescan');
    delete_option('wp_appstore_autoupdate_request');
    delete_option('wp_appstore_plugins_for_update');
    delete_option('wp_appstore_themes_for_update');
    delete_option('wp_appstore_last_lib_update');
}
add_action('admin_init', 'wp_appstore_admin_init');
add_action('admin_menu', 'wp_appstore_admin_menu');
add_action('wp_appstore_daily_event', 'wp_appstore_update_formulas');
register_activation_hook(__FILE__, 'wp_appstore_activation');
register_deactivation_hook(__FILE__, 'wp_appstore_deactivation');
register_uninstall_hook(__FILE__, 'wp_appstore_uninstall')
?>