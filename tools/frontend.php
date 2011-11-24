<?php

/**
 * @author Arashi
 * @copyright 2011
 * Fronten function for wp-appstore
 */
// Load Importer API
/*require_once ABSPATH . 'wp-admin/includes/import.php';

if ( ! class_exists( 'WP_Importer' ) ) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if ( file_exists( $class_wp_importer ) )
		require $class_wp_importer;
}*/

@ini_set( 'max_execution_time', 360 );
@set_time_limit( 360 );

function wp_appstore_frontend(){
    global $wpdb;
    main_categories_check();
    $appstore = new WP_AppStore();
    $author_id = admin_user_ids();
    
    //plugins
    $plugins = $appstore->get_plugins();
    foreach ($plugins as $plugin) {
        if ($id = post_exists($plugin->title)) {
            $postmeta = get_post_custom($id);
                if (!isset($postmeta['version']) || $postmeta['version'] != $plugin->version) {
                    $category = probably_create_category($plugin->category_name ,$plugin->category_slug, 'plugin');
                    $scr = '';
                    if (count($plugin->screenshots)>0) {
                        $scr = '<p></p>';
                        foreach ($plugin->screenshots as $ss) {
                            $scr .= '<img src="'.$ss.'" />';
                        }
                    }
                    $meta = array(
                    'version' => $plugin->version,
                    'author' => $plugin->author,
                    'homepage' => $plugin->homepage,
                    );
                    $post_id = updatePost($id, $plugin->title, $plugin->description.$scr, $plugin->slug, null, array($category['term_id']), 'draft', $meta);
                    if (is_array($plugin->tags)) {
                        wp_set_post_tags($post_id, implode(',', $plugin->tags));
                    }
                }

        }else{
            $category = probably_create_category($plugin->category_name ,$plugin->category_slug, 'plugin');
            $scr = '';
            if (count($plugin->screenshots)>0) {
                $scr = '<p></p>';
                foreach ($plugin->screenshots as $ss) {
                    $scr .= '<img src="'.$ss.'" />';
                }
            }
            $meta = array(
            'version' => $plugin->version,
            'author' => $plugin->author,
            'homepage' => $plugin->homepage,
            );
            $post_id = insertPost($plugin->title, $plugin->description.$scr, $plugin->slug, null, array($category['term_id']), 'draft', $author_id[0], true, 'open', $meta);
            if (is_array($plugin->tags)) {
                wp_set_post_tags($post_id, implode(',', $plugin->tags));
            }
        }
    }
    
    //themes
    
    $themes = $appstore->get_themes();
    foreach ($themes as $theme) {
        if ($id = post_exists($theme->title)) {
            $postmeta = get_post_custom($id);
                if (!isset($postmeta['version']) || $postmeta['version'] != $theme->version) {
                    $category = probably_create_category($theme->category_name ,$theme->category_slug, 'theme');
                    $scr = '';
                    if (count($theme->screenshots)>0) {
                        $scr = '<p></p>';
                        foreach ($theme->screenshots as $ss) {
                            $scr .= '<img src="'.$ss.'" />';
                        }
                    }
                    $meta = array(
                    'version' => $theme->version,
                    'author' => $theme->author,
                    'homepage' => $theme->homepage,
                    );
                    $post_id = updatePost($id, $theme->title, $theme->description.$scr, $theme->slug, null, array($category['term_id']), 'draft', $meta);
                    if (is_array($theme->tags)) {
                        wp_set_post_tags($post_id, implode(',', $theme->tags));
                    }
                }

        }else{
            $category = probably_create_category($theme->category_name ,$theme->category_slug, 'theme');
            $scr = '';
            if (count($theme->screenshots)>0) {
                $scr = '<p></p>';
                foreach ($theme->screenshots as $ss) {
                    $scr .= '<img src="'.$ss.'" />';
                }
            }
            $meta = array(
            'version' => $theme->version,
            'author' => $theme->author,
            'homepage' => $theme->homepage,
            );
            $post_id = insertPost($theme->title, $theme->description.$scr, $theme->slug, null, array($category['term_id']), 'draft', $author_id[0], true, 'open', $meta);
            if (is_array($theme->tags)) {
                wp_set_post_tags($post_id, implode(',', $theme->tags));
            }
        }
    }

}
function probably_create_category($cat_name, $cat_slug, $parent){
    $parent_term = term_exists( $parent, 'category' ); // array is returned if taxonomy is given
    $parent_term_id = $parent_term['term_id']; // get numeric term id
    
    $result = term_exists( $cat_slug, 'category' );
    if(!$result)
    $result = wp_insert_term(
          $cat_name, // the term 
          'category', // the taxonomy
          array(
            'slug' => $cat_slug,
            'parent'=> $parent_term_id
          )
    );
    return $result;
}
function main_categories_check(){
        //get_term_by( 'slug', 'post', 'category')
    if(!term_exists( 'plugin', 'category' ))
    wp_insert_term(
          'Plugin', // the term 
          'category', // the taxonomy
          array(
            'description'=> 'Plugins parent Category.',
            'slug' => 'plugin',
          )
    );
    if(!term_exists( 'theme', 'category' ))
    wp_insert_term(
          'Theme', // the term 
          'category', // the taxonomy
          array(
            'description'=> 'Themes parent Category.',
            'slug' => 'theme',
          )
    );
}
//Get all admin user ID's in the DB
function admin_user_ids(){
    //Grab wp DB
    global $wpdb;
    //Get all users in the DB
    $wp_user_search = $wpdb->get_results("SELECT ID, display_name FROM $wpdb->users ORDER BY ID");
   
    //Blank array
    $adminArray = array();
    //Loop through all users
    foreach ( $wp_user_search as $userid ) {
        //Current user ID we are looping through
        $curID = $userid->ID;
        //Grab the user info of current ID
        $curuser = get_userdata($curID);
        //Current user level
        $user_level = $curuser->user_level;
        //Only look for admins
        if($user_level >= 8){//levels 8, 9 and 10 are admin
            //Push user ID into array
            $adminArray[] = $curID;
        }
    }
    return $adminArray;
}
function insertPost($title, $content, $slug = null, $timestamp = null, $category = null, $status = 'draft', $authorid = null, $allowpings = true, $comment_status = 'open', $meta = array())
  {
    $date = ($timestamp) ? gmdate('Y-m-d H:i:s', $timestamp + (get_option('gmt_offset') * 3600)) : null;
    $postid = wp_insert_post(array(
    	'post_title' 	            => $title,
        'post_name' 	            => $slug,
  		'post_content'  	        => $content,
  		'post_category'           => $category,
  		'post_status' 	          => $status,
  		'post_author'             => $authorid,
  		'post_date'               => $date,
  		'comment_status'          => $comment_status,
  		'ping_status'             => $allowpings,
    ));
    	
		foreach($meta as $key => $value) 
			update_post_meta($postid, $key, $value);			
		
		return $postid;
  }
function updatePost($id, $title, $content, $slug = null, $timestamp = null, $category = null, $status = 'draft', $meta = array())
  {
    $date = ($timestamp) ? gmdate('Y-m-d H:i:s', $timestamp + (get_option('gmt_offset') * 3600)) : null;
    $postid = wp_update_post(array(
    	'ID' => $id,
    	'post_title' => $title,
        'post_name' => $slug,
   		'post_content' => $content,
   		'post_category' => $category,
  		'post_status' 	          => $status,
  		'post_date'               => $date,
    ));
    	
		foreach($meta as $key => $value) 
			update_post_meta($postid, $key, $value);			
		
		return $postid;
  }

function main_post_type_check($type){
    $exists = post_type_exists('post');
}
?>