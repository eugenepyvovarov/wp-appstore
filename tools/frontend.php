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
    main_post_type_check();
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
                    $post_id = updatePost($id, $plugin->title, $plugin->description.$scr, $plugin->slug, null, $category, 'draft', 'wp-appstore-plugin', $meta);
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
            $post_id = insertPost($plugin->title, $plugin->description.$scr, $plugin->slug, null, $category, 'draft', 'wp-appstore-plugin', $author_id[0], true, 'open', $meta);
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
                    $post_id = updatePost($id, $theme->title, $theme->description.$scr, $theme->slug, null, $category, 'draft', 'wp-appstore-theme', $meta);
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
            $post_id = insertPost($theme->title, $theme->description.$scr, $theme->slug, null, $category, 'draft', 'wp-appstore-theme', $author_id[0], true, 'open', $meta);
            if (is_array($theme->tags)) {
                wp_set_post_tags($post_id, implode(',', $theme->tags));
            }
        }
    }
update_option('wp_appstore_frontend_rescan', false);
}
function probably_create_category($cat_name, $cat_slug, $parent){
    $parent_term = term_exists( $parent, 'wp-appstore-category' ); // array is returned if taxonomy is given
    $parent_term_id = $parent_term['term_id']; // get numeric term id
    
    $term = term_exists( $cat_slug, 'wp-appstore-category' );
    if(!$term)
    $term = wp_insert_term(
          $cat_name, // the term 
          'wp-appstore-category', // the taxonomy
          array(
            'slug' => $cat_slug,
            'parent'=> $parent_term_id
          )
    );
    $result = array();
    if(is_array($parent_term))
        $result[] = $parent_term['term_id'];
    if(is_array($term))
        $result[] = $term['term_id'];
            
    return $result;
}
function main_categories_check(){
        //get_term_by( 'slug', 'post', 'category')
    if(!term_exists( 'plugin', 'wp-appstore-category' ))
    wp_insert_term(
          'Plugin', // the term 
          'wp-appstore-category', // the taxonomy
          array(
            'description'=> 'Plugins parent Category.',
            'slug' => 'plugin',
          )
    );
    if(!term_exists( 'theme', 'wp-appstore-category' ))
    wp_insert_term(
          'Theme', // the term 
          'wp-appstore-category', // the taxonomy
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
function insertPost($title, $content, $slug = null, $timestamp = null, $category = null, $status = 'draft', $type = 'post', $authorid = null, $allowpings = true, $comment_status = 'open', $meta = array())
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
        'post_type'               => $type,
  		'comment_status'          => $comment_status,
  		'ping_status'             => $allowpings,
    ));
    var_dump( wp_set_object_terms($postid, $category, 'wp-appstore-category'));
    	
		foreach($meta as $key => $value) 
			update_post_meta($postid, $key, $value);			
		
		return $postid;
  }
function updatePost($id, $title, $content, $slug = null, $timestamp = null, $category = null, $status = 'draft', $type = 'post', $meta = array())
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
        'post_type'               => $type,
    ));
    $category = array_map('intval', $category);
    $category = array_unique( $category );
    var_dump(wp_set_object_terms($postid, $category, 'wp-appstore-category'));	
		foreach($meta as $key => $value) 
			update_post_meta($postid, $key, $value);			
		
		return $postid;
  }

function main_post_type_check(){
    
  if(!taxonomy_exists('wp-appstore-category')){
  // Add new taxonomy, make it hierarchical (like categories)
  $labels = array(
    'name' => _x( 'WP-appstore Categories', 'taxonomy general name' ),
    'singular_name' => _x( 'WP-appstore Category', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search WP-appstore Categories' ),
    'all_items' => __( 'All WP-appstore Categories' ),
    'parent_item' => __( 'Parent WP-appstore Category' ),
    'parent_item_colon' => __( 'Parent WP-appstore Category:' ),
    'edit_item' => __( 'Edit WP-appstore Category' ), 
    'update_item' => __( 'Update WP-appstore Category' ),
    'add_new_item' => __( 'Add New WP-appstore Category' ),
    'new_item_name' => __( 'New Genre WP-appstore Category' ),
    'menu_name' => __( 'WP-appstore Category' ),
  ); 	

  register_taxonomy('wp-appstore-category',null, array(
    'hierarchical' => true,
    'labels' => $labels,
    'show_ui' => true,
    'query_var' => true,
    'rewrite' => array( 'slug' => 'wp-appstore-category' ),
  ));
  }
    
    if(!post_type_exists('wp-appstore-plugin'))
    register_post_type('wp-appstore-plugin', array(
    	'label' => __('Plugins Pages'),
    	'singular_label' => __('Plugin'),
    	'public' => true,
    	'show_ui' => true, // UI in admin panel
    	'capability_type' => 'post',
        'show_in_menu' => true,
        'show_in_nav_menus' => true, 
    	'hierarchical' => false,
    	'rewrite' => array("slug" => "wp-appstore-plugin"), // Permalinks format
    	'supports' => array('title','editor','author','thumbnail','excerpt','comments', 'trackbacks', 'custom-fields', 'revisions', 'page-attributes', 'post-formats'),
        'taxonomies' => array('post_tag', 'wp-appstore-category')
    ));
    if(!post_type_exists('wp-appstore-theme'))
    register_post_type('wp-appstore-theme', array(
    	'label' => __('Themes Pages'),
    	'singular_label' => __('Theme'),
    	'public' => true,
    	'show_ui' => true, // UI in admin panel
    	'capability_type' => 'post',
        'show_in_menu' => true,
        'show_in_nav_menus' => true, 
    	'hierarchical' => false,
    	'rewrite' => array("slug" => "wp-appstore-theme"), // Permalinks format
    	'supports' => array('title','editor','author','thumbnail','excerpt','comments', 'trackbacks', 'custom-fields', 'revisions', 'page-attributes', 'post-formats'),
        'taxonomies' => array('post_tag', 'wp-appstore-category')
    ));
  register_taxonomy_for_object_type('wp-appstore-category', 'wp-appstore-theme');
  register_taxonomy_for_object_type('wp-appstore-category', 'wp-appstore-plugin');
    
}
add_action( 'init', 'main_post_type_check' );

function change_columns( $cols ) {
        $posts_columns = array();
        $post_type = 'wp-appstore-plugin';
		$posts_columns['cb'] = '<input type="checkbox" />';

		/* translators: manage posts column name */
		$posts_columns['title'] = _x( 'Title', 'column name' );

		if ( post_type_supports( $post_type, 'author' ) )
			$posts_columns['author'] = __( 'Author' );

		if ( empty( $post_type ) || is_object_in_taxonomy( $post_type, 'wp-appstore-category' ) )
			$posts_columns['wp-appstore-categories'] = __( 'WP-appstore Categories' );

		if ( empty( $post_type ) || is_object_in_taxonomy( $post_type, 'post_tag' ) )
			$posts_columns['tags'] = __( 'Tags' );

		$post_status = !empty( $_REQUEST['post_status'] ) ? $_REQUEST['post_status'] : 'all';
		if ( post_type_supports( $post_type, 'comments' ) && !in_array( $post_status, array( 'pending', 'draft', 'future' ) ) )
			$posts_columns['comments'] = '<span class="vers"><img alt="' . esc_attr__( 'Comments' ) . '" src="' . esc_url( admin_url( 'images/comment-grey-bubble.png' ) ) . '" /></span>';

		$posts_columns['date'] = __( 'Date' );
  return $posts_columns;
}
function custom_columns( $column, $post_id ) {
  switch ( $column ) {
    case "wp-appstore-categories":
     $categories = wp_get_object_terms( $post_id, 'wp-appstore-category');
	if ( !empty( $categories ) ) {
		$out = array();
		foreach ( $categories as $c ) {
			$out[] = sprintf( '<a href="%s">%s</a>',
				esc_url( add_query_arg( array( 'post_type' => get_post_type( $post_id ), 'wp-appstore-category' => $c->slug ), 'edit.php' ) ),
				esc_html( sanitize_term_field( 'name', $c->name, $c->term_id, 'wp-appstore-category', 'display' ) )
			);
		}
		echo join( ', ', $out );
	} else {
		_e( 'Uncategorized' );
	}
      break;
  }
}
function taxonomy_filter_post_type_request( $query ) {
  global $pagenow, $typenow;
  if ( 'edit.php' == $pagenow ) {
    $filters = get_object_taxonomies( $typenow );
    foreach ( $filters as $tax_slug ) {
      $var = &$query->query_vars[$tax_slug];
      if ( isset( $var ) ) {
        $term = get_term_by( 'slug', $var, $tax_slug );
        $var = $term->slug;
      }
    }
  }
}

add_filter( 'parse_query', 'taxonomy_filter_post_type_request' );
add_action( "manage_posts_custom_column", "custom_columns", 10, 2 );
add_filter( "manage_wp-appstore-plugin_posts_columns", "change_columns" );
add_filter( "manage_wp-appstore-theme_posts_columns", "change_columns" );
?>