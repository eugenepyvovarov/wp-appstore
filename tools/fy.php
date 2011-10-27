<?php

/**
 * @author Arashi
 * @copyright 2011
 */

//converts all descriptions to markdown
include 'markdownify_extra.php';


//$md = new Markdownify(true, MDFY_BODYWIDTH, true);
$md = new Markdownify_Extra(false, MDFY_BODYWIDTH, false);
//$output = $md->parseString($_POST['input']);

include_once "markdown.php";
    

$formulas_path = 'D:/xampp/htdocs/wordpress/wp-content/plugins/wp-appstore/formulas/';

		$plugins_dir = @ opendir( $formulas_path );
		if ( $plugins_dir ) {
			while (($file = readdir( $plugins_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				$plugin_files[] = $formulas_path.$file;
			}
			@closedir( $plugins_dir );
		}

//var_dump($plugin_files);

foreach ($plugin_files as $ini) {
    $contents = array();
    $contents = parse_ini_file($ini, true);
    
    $contents['general']['description'] = escape_quotes($md->parseString(convert_escaped_quotes($contents['general']['description'])));
    write_ini_file($contents, $ini, true);
}

/*
foreach ($plugin_files as $ini) {
   
    $contents = array();
    $contents = parse_ini_file($ini, true);
    
    $tm = Markdown(convert_escaped_quotes($contents['general']['description']));
    var_dump($tm);
    die;
}
*/

echo('done');
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