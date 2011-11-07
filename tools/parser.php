<?php

/**
 * @author Arashi
 * @copyright 2011
 */
@ini_set( 'max_execution_time', 360 );
// TODO route this pages via a specific iframe handler instead of the do_action below

/** WordPress Administration Bootstrap */
require_once('../../../../wp-config.php');
require_once('../../../../wp-load.php');
require_once(ABSPATH.'/wp-admin/admin.php');
//require_once (ABSPATH.'/wp-admin/plugin-install.php');
//require_once (ABSPATH.'/wp-admin/plugins.php');
require_once (ABSPATH.'/wp-admin/includes/plugin-install.php');
require_once (ABSPATH.'/wp-admin/includes/theme-install.php');
include 'markdownify_extra.php';


//$md = new Markdownify(true, MDFY_BODYWIDTH, true);
$md = new Markdownify_Extra(false, MDFY_BODYWIDTH, false);

$args = array( 'page' => $paged, 'per_page' => 50 );
$args['browse'] = 'popular';
$api = plugins_api( 'query_plugins', $args );
$path = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'formulas'.DIRECTORY_SEPARATOR;
foreach ($api->plugins as $plugin) {

    $plugin = plugins_api('plugin_information', array('slug' => $plugin->slug ));
    //$plugin = plugins_api('plugin_information', array('slug' => $api->plugins[1]->slug ));
    //var_dump($plugin);
    //die;
    
    $html = simplexml_load_string($plugin->sections['screenshots']);
    $ss = array();
    if (sizeof($html->li)>0) {
        foreach ($html->li as $element) {
            $img = get_object_vars($element->img);
            
            $ss[] = $img['@attributes']['src'];
        }
    }

//    exit;


                 $output = '';
                 $output .= "[general]\n";
                 $output .= "type = plugin\n";
                 $output .= "title = \"{$plugin->name}\"\n";
                 $output .= "slug = \"{$plugin->slug}\"\n";
                 $description = escape_quotes($md->parseString($plugin->sections['description']));
                 $output .= "description = \"{$description}\"\n";
                 $author = escape_quotes($plugin->author);
                 $output .= "author = \"{$author}\"\n";
                 $output .= "version = \"{$plugin->version}\"\n";
                 $output .= "updated = \"{$plugin->last_updated}\"\n";
                 $output .= "added = \"{$plugin->added}\"\n";
                 $output .= "requires = \"{$plugin->requires}\"\n";
                 $output .= "tested = \"{$plugin->tested}\"\n";
                 
                 $output .= "[tags]\n";
                 if (sizeof($plugin->tags) > 0) {
                    foreach ($plugin->tags as $key => $tag) {
                        //$output .= "tag_slug[] = \"{$key}\"\n";
                        $output .= "tags[] = \"{$tag}\"\n";
                    }
                 }
                 
                 $output .= "[category]\n";
                 $output .= "category_slug = \"\"\n";
                 $output .= "category_name = \"\"\n";
                 
                 $output .= "[assets]\n";
                 $output .= "link = \"{$plugin->download_link}\"\n";
                 $output .= "icon = \"\"\n";
                 if (sizeof($ss) > 0) {
                    foreach ($ss as $s) {
                        $output .= "screenshots[] = \"{$s}\"\n";
                    }
                 }
                 
                 $output .= "[info]\n";
                 $output .= "featured = \"\"\n";
                 $output .= "homepage = \"{$plugin->homepage}\"\n";
                 $output .= "rating = \"{$plugin->rating}\"\n";
                 $output .= "votes = \"{$plugin->num_ratings}\"\n";
                 $output .= "downloaded = \"{$plugin->downloaded}\"\n";
                 if (! $price=$plugin->price )
                    $price = '0.00'; 
                 $output .= "price = \"{$price}\"\n";
                 $filename = $path.$plugin->slug.'.ini';
                 file_put_contents($filename, $output);

        
    
    
}

	$args = array( 'page' => $paged, 'per_page' => 50);
	$args['browse'] = 'featured';
$api = themes_api( 'query_themes', $args );
foreach ($api->themes as $theme) {
    $theme = themes_api('theme_information', array('slug' => $theme->slug ));
       
                 $output = '';
                 $output .= "[general]\n";
                 $output .= "type = theme\n";
                 //$output .= "id = {$theme->id}\n";
                 $output .= "title = \"{$theme->name}\"\n";
                 $output .= "slug = \"{$theme->slug}\"\n";
                 $description = escape_quotes($md->parseString($theme->sections['description']));
                 $output .= "description = \"{$description}\"\n";
                 $author = escape_quotes($theme->author);
                 $output .= "author = \"{$author}\"\n";
                 $output .= "version = \"{$theme->version}\"\n";
                 $output .= "updated = \"{$theme->last_updated}\"\n";
                 //$output .= "added = \"{$theme->added}\"\n";
                 //$output .= "requires = \"{$theme->requires}\"\n";
                 //$output .= "tested = \"{$theme->tested}\"\n";
                 
                 $output .= "[tags]\n";
                 if (sizeof($theme->tags) > 0) {
                    foreach ($theme->tags as $key => $tag) {
                        //$output .= "tag_slug[] = \"{$key}\"\n";
                        $output .= "tags[] = \"{$tag}\"\n";
                    }
                 }
                 
                 $output .= "[category]\n";
                 $output .= "category_slug = \"\"\n";
                 $output .= "category_name = \"\"\n";
                 
                 $output .= "[assets]\n";
                 $output .= "link = \"{$theme->download_link}\"\n";
                 $output .= "icon = \"\"\n";
                 
                 if ($theme->screenshot_url) {   
                        $output .= "screenshots[] = \"$theme->screenshot_url\"\n";
                 }
                 
                 $output .= "[info]\n";
                 $output .= "featured = \"\"\n";
                 $output .= "preview_url = \"{$theme->preview_url}\"\n";
                 $output .= "homepage = \"{$theme->homepage}\"\n";
                 $output .= "rating = \"{$theme->rating}\"\n";
                 $output .= "votes = \"{$theme->num_ratings}\"\n";
                 $output .= "downloaded = \"{$theme->downloaded}\"\n";
                 if (! $price=$theme->price )
                    $price = '0.00'; 
                 $output .= "price = \"{$price}\"\n";
                 $filename = $path.$theme->slug.'.ini';
                 file_put_contents($filename, $output);
                 
}
  // var_dump( themes_api('theme_information', array('slug' => $api->themes[1]->slug )) );


function escape_quotes($str){
    $str = str_replace("'", "&#039;", $str);
    $str = str_replace('"', "&quot;", $str);
    $str = str_replace('[', "\[", $str);
    $str = str_replace(']', "\]", $str);
    return $str;
}
?>