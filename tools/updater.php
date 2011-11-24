<?php

/**
 * @author Arashi
 * @copyright 2011
 */
@ini_set( 'max_execution_time', 360 );
@set_time_limit( 360 );
include_once WP_PLUGIN_DIR."/wp-appstore/tools/markdownify_extra.php";
//$md = new Markdownify(true, MDFY_BODYWIDTH, true);
$md = new Markdownify_Extra(false, MDFY_BODYWIDTH, false);
//$output = $md->parseString($_POST['input']);
include_once WP_PLUGIN_DIR."/wp-appstore/tools/markdown.php";
if (!function_exists('plugins_api')) {
    require_once (ABSPATH.'/wp-admin/includes/plugin-install.php');
}
if (!function_exists('themes_api')) {
    require_once (ABSPATH.'/wp-admin/includes/theme-install.php');
}
$formulas_path = WP_PLUGIN_DIR.DIRECTORY_SEPARATOR.'wp-appstore'.DIRECTORY_SEPARATOR.'formulas';

function wp_appstore_update_checker(){
    global $formulas_path, $md;
    $error = '';
    $mail = '';
    $formulas_dir = @ opendir( $formulas_path );
		if ( $formulas_dir ) {
			while (($file = readdir( $formulas_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				$ini = $formulas_path.DIRECTORY_SEPARATOR.$file;
                if($contents = @parse_ini_file($ini, true)){
                    if ($contents['general']['type'] == 'plugin') {
                        $formula_ver = WP_AppStore::str_to_float($contents['general']['version']);
                        $plugin = plugins_api('plugin_information', array('slug' => $contents['general']['slug'] ));
                        $wp_api_ver = WP_AppStore::str_to_float($plugin->version);
                        //var_dump($wp_api_ver);
                        if ($wp_api_ver > $formula_ver) {
                        //let's start update formula file
                            //contents section
                            $contents['general']['title'] = $plugin->name;
                            $contents['general']['slug'] = $plugin->slug;
                            $contents['general']['description'] = escape_quotes($md->parseString($plugin->sections['description']));
                            $contents['general']['author'] = escape_quotes($plugin->author);
                            $contents['general']['version'] = $plugin->version;
                            $contents['general']['updated'] = $plugin->last_updated;
                            $contents['general']['added'] = $plugin->added;
                            $contents['general']['requires'] = $plugin->requires;
                            $contents['general']['tested'] = $plugin->tested;
                            //tags section
                            $contents['tags']['tags'] = array_values($plugin->tags);
                            //assets
                            $contents['assets']['link'] = $plugin->download_link;
                            //screenshots
                            if (isset($plugin->sections['screenshots'])) {
                                $html = simplexml_load_string($plugin->sections['screenshots']);
                                $ss = array();
                                if (sizeof($html->li)>0) {
                                    foreach ($html->li as $element) {
                                        $img = get_object_vars($element->img);
                                        
                                        $ss[] = $img['@attributes']['src'];
                                    }
                                }
                                if (sizeof($ss) > 0) {
                                    $contents['assets']['screenshots'] = $ss;
                                }
                                
                            }
                            //info section
                            $contents['info']['homepage'] = $plugin->homepage;
                            $contents['info']['rating'] = $plugin->rating;
                            $contents['info']['votes'] = $plugin->num_ratings;
                            $contents['info']['downloaded'] = $plugin->downloaded;
                            
                            //write to temp files:
                            if ($path = get_tmp_path()) {
                                $filename = $path.$plugin->slug.'.ini';
                                if (!write_ini_file($contents, $filename, true)) {
                                    $error[] = "Can't write $filename file.";
                                }else{
                                    $mail[$plugin->slug]['name'] = $plugin->name;
                                    $mail[$plugin->slug]['version'] = $plugin->version;
                                    $mail[$plugin->slug]['attachement'] = $filename;
                                    $plugin_log[$plugin->slug] = $plugin->version;
                                }
                            }else
                                $error[] = "Can't get tempfile path.";
                        }
                    }
                    //themes section
                    if ($contents['general']['type'] == 'theme') {
                        $formula_ver = WP_AppStore::str_to_float($contents['general']['version']);
                        $theme = themes_api('theme_information', array('slug' => $contents['general']['slug'] ));
                        $wp_api_ver = WP_AppStore::str_to_float($theme->version);
                        //var_dump($wp_api_ver);
                        if ($wp_api_ver > $formula_ver) {
                        //let's start update formula file
                            //contents section
                            $contents['general']['title'] = $theme->name;
                            $contents['general']['slug'] = $theme->slug;
                            $contents['general']['description'] = escape_quotes($md->parseString($theme->sections['description']));
                            $contents['general']['author'] = escape_quotes($theme->author);
                            $contents['general']['version'] = $theme->version;
                            $contents['general']['updated'] = $theme->last_updated;
                            //tags section
                            $contents['tags']['tags'] = array_values($theme->tags);
                            //assets
                            $contents['assets']['link'] = $theme->download_link;
                            //screenshots
                            if (isset($theme->screenshot_url)) {
                                $contents['assets']['icon'] = $theme->screenshot_url;
                                $contents['assets']['screenshots'] = $theme->screenshot_url;
                            }
                            //info section
                            $contents['info']['preview_url'] = $theme->preview_url;
                            $contents['info']['homepage'] = $theme->homepage;
                            $contents['info']['rating'] = $theme->rating;
                            $contents['info']['votes'] = $theme->num_ratings;
                            $contents['info']['downloaded'] = $theme->downloaded;
                            
                            //write to temp files:
                            if ($path = get_tmp_path()) {
                                $filename = $path.$theme->slug.'.ini';
                                if (!write_ini_file($contents, $filename, true)) {
                                    $error[] = "Can't write $filename file.";
                                }else{
                                    $mail[$theme->slug]['name'] = $theme->name;
                                    $mail[$theme->slug]['version'] = $theme->version;
                                    $mail[$theme->slug]['attachement'] = $filename;
                                    $theme_log[$theme->slug] = $theme->version;
                                }
                            }else
                                $error[] = "Can't get tempfile path.";
                        }
                    }
                    
                }
			}
			@closedir( $formulas_dir );
		}else
            $error[] = "Can't open formulas dir.";
        //start of mailing section
        $to = 'stanislav.proshkin@gmail.com, bsn.dev@gmail.com';
        $subject = 'Some formulas updated!';
        $date = date("D M j G:i:s T Y");
        $attach = array();
        $msg = '';
        if (is_array($error)) {
            $msg = "ERRORS------------------------------------------\n";
            $msg .= implode("\n", $error);
        }
        if (is_array($mail)) {
            foreach ($mail as $formula) {
                $msg .= "Formulas updated\n";
                $msg .= "{$formula['name']}------------------------------------------\n";
                $msg .= "Updated to: {$formula['version']}\n";
                $msg .= "More details in this attach: {$formula['attachement']}\n";
                $attach[] = $formula['attachement'];
            }
        }
        if (strlen($msg) > 5) {
            $body = "Today - $date we checked for formulas updates and got this results:\n";
            $body .= $msg;
            wp_mail($to, $subject, $body, '', $attach);
        }   
        
        if (sizeof($attach) > 0) {
            foreach ($attach as $file) {
                @unlink($file);
            }
        }
}

add_action('wp_appstore_twicedaily_event', 'wp_appstore_update_checker');
wp_schedule_event(time(), 'twicedaily', 'wp_appstore_twicedaily_event');
function escape_quotes($str){
    $str = str_replace("'", "&#039;", $str);
    $str = str_replace('"', "&quot;", $str);
    $str = str_replace('[', "\[", $str);
    $str = str_replace(']', "\]", $str);
    return $str;
}
function convert_escaped_quotes($str){
        $str = str_replace("&#039;", "'", $str);
        $str = str_replace("&quot;", '"', $str);
        $str = str_replace('\[', "[", $str);
        $str = str_replace('\]', "]", $str);
        return $str;
}
function write_ini_file($assoc_arr, $path, $has_sections = FALSE){
 $content = ""; 

 if ($has_sections) { 
  foreach ($assoc_arr as $key=>$elem) { 
   $content .= "[".$key."]\n"; 
   foreach ($elem as $key2=>$elem2) 
   { 
    if(is_array($elem2)) 
    { 
     for($i=0;$i<count($elem2);$i++) 
     { 
      $content .= $key2."[] = \"".$elem2[$i]."\"\n"; 
     } 
    } 
    else if($elem2=="") $content .= $key2." = \n"; 
    else $content .= $key2." = \"".$elem2."\"\n"; 
   } 
  } 
 } 
 else
 { 
  foreach ($assoc_arr as $key=>$elem) { 
   if(is_array($elem)) 
   { 
    for($i=0;$i<count($elem);$i++) 
    { 
     $content .= $key2."[] = \"".$elem[$i]."\"\n"; 
    } 
   } 
   else if($elem=="") $content .= $key2." = \n"; 
   else $content .= $key2." = \"".$elem."\"\n"; 
  } 
 } 

 if (!$handle = fopen($path, 'w'))
 { 
  return false; 
 }

 if (!fwrite($handle, $content))
 { 
  return false; 
 }

 fclose($handle); 
 return true;
}
?>