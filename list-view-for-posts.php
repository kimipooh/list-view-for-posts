<?php
/*
Plugin Name: List View for Posts
Plugin URI: 
Description: The plugin is the shortcode for comprehensively displaying the list view for pages and posts (including customizing posts) supported with the plugins; WPML and The Events Calendar.
Version: 1.8
Author: Kimiya Kitani
Author URI: https://profiles.wordpress.org/kimipooh/
Text Domain: list-view-for-posts
Domain Path: /lang
*/

require_once( plugin_dir_path(__FILE__) . '/includes/library.php');

class lvp extends lvp_library{
	var $plugin_name = 'list-view-for-posts';
	var $plugin_title = 'List View for Posts';
	var $plugin_shortcode = 'list-view-posts';
	var $html_tags = array('li'=>'li', 'p'=>'p', 'dd'=>'dd', 'lip'=>'lip'); 
	var $default_html_tag = 'li'; 
	var $lang_dir = 'lang';	// Language folder name
	// https://wpdocs.osdn.jp/%E6%8A%95%E7%A8%BF%E3%82%BF%E3%82%A4%E3%83%97
	var $default_post_types = array('post'=>'post', 'page'=>'page', 'attachment'=>'attachment', 'revision'=>'revision', 'nav_menu_item'=>'nav_menu_item');
	var $settings = array();
	
	function __construct(){
		$this->init_settings();
		add_action('plugins_loaded', array(&$this,'enable_language_translation'));
		add_shortcode($this->plugin_shortcode, array(&$this, 'shortcodes'));
	}
	public function enable_language_translation(){
		load_plugin_textdomain($this->plugin_name)
		or load_plugin_textdomain($this->plugin_name, false, dirname( plugin_basename( __FILE__ ) ) . '/' . $this->lang_dir . '/');
	}
	public function init_settings(){
		$this->settings['version'] = 170;
		$this->settings['db_version'] = 100;
	}

	public function shortcodes($atts){
		// Delete an illegal code for the shortcode options.
		$atts = $this->security_check_array($atts);
		extract($atts = shortcode_atts(array(
			'post_type'		=> 'post',
			'post_status' 	=> 'publish',
			'date_format'	=> 'Y.m.d', 
			'orderbysort'	=> 'DESC',	// ascending or descending.
			'max_items'		=> 5,		// Maximum number of items
			'page'	=> 1,		// If you want to display many items, you can get the number of $max_items  from ($page-1)*$max_items.
			'html_tag'		=> 'li',	// Allow $this->html_tags value.
			'html_tag_class'=> '',		// adding a class to html tag (default: $this->plugin_shortcode)
			'enable_view_post_type'	=> '',  // If you want to display the post type on the list view, set "true".
			'enable_view_category' => '', // If you want to display the category and taxonomy on the list view, set "true" and set "category_taxonomy" value.
			'category_taxonomy' => 'category', // Set taxonomy's list for displaying the the category and taxonomy except "uncategorized".
			'enable_the_event_calendar_events' => '', // If you want to include the events on the "The Event Calendar" plugin, set "true" value
			'enable_passowrd_protected_post' => '', // If you want to display a password protected post, set "true".
			'wpml_lang'			=> '',		// If you want to specific the language, set "language code" on WPML.
			'hook_secret_key' => '',	// If you use a hook, please set the secret key because of preventing an overwrite from any other plugins.
			'id'			=> '',		// If you want to change various original templates instead of "html_tag", judge the key.
		), $atts));
		
		// Fixed the illegal value of the shortcode option.
		if($max_items <= 0):
			$max_items = 5;
			$atts['max_items'] = $max_items;
		endif;
		if($page <= 0):
			$page = 1;
			$atts['page'] = $page;
		endif;
		$html_tag_class = $html_tag_class ?: $this->plugin_shortcode;
		$html_tag = isset($this->html_tags[$html_tag]) ? $this->html_tags[$html_tag] : $default_html_tag;
		$atts['html_tag_class'] = $html_tag_class;
		$atts['html_tag'] = $html_tag;
		$post_type = explode(',', $post_type);
		$post_status = explode(',', $post_status);
		$category_taxonomy = explode(',', $category_taxonomy);
		$atts['category_taxonomy'] = $category_taxonomy;
		if($orderbysort !== "DESC" || $orderbysort !== "ASC"):
			$orderbysort = 'DESC';
			$atts['orderbysort'] = $orderbysort;
		endif; 
		$start_item = ($page-1)*5;

		// Processing for Posts
		global $wpdb;
		global $post;

		// Reference: WPML support
		// http://wpml.org/forums/topic/recent-posts-custom-widget-wpdb-get_results/
		$wp_prefix = $wpdb->prefix;
		$wp_icl_translations = "{$wp_prefix}icl_translations";
		if($this->is_active("sitepress-multilingual-cms/sitepress.php")):
			$lang_code = $wpml_lang ?: ICL_LANGUAGE_CODE;
		endif;

		// Creating SQL for Database.
		$sql  = sprintf("SELECT * FROM %s ", esc_sql($wpdb->posts));
		if($this->is_active("sitepress-multilingual-cms/sitepress.php")):
			$sql .= sprintf("LEFT JOIN %s ON %sposts.ID = %s.element_id", esc_sql($wp_icl_translations), esc_sql($wp_prefix), esc_sql($wp_icl_translations));
		endif;
		$sql .= " WHERE (";
		if($enable_the_event_calendar_events && $this->is_active("the-events-calendar/the-events-calendar.php")):
			$sql .= "post_type = 'tribe_events' OR ";
		endif;
		$post_types_first_flag = true;
		foreach($post_type as $v):
			if($post_types_first_flag):
				$post_types_first_flag = false;
				$sql .= $wpdb->prepare("post_type = '%s'", $v);
			else:
				$sql .= $wpdb->prepare(" OR post_type = '%s'", $v);
			endif;
		endforeach;	
		$sql .= ") AND post_status IN (";
		$post_status_first_flag = true;
		foreach($post_status as $v):
			if($post_status_first_flag):
				$post_status_first_flag = false;
				$sql .= $wpdb->prepare("'%s'",$v);
			else:
				$sql .= $wpdb->prepare(",'%s'",$v);
			endif;
		endforeach;	
		$sql .= ")";
		if(!$enable_passowrd_protected_post):
			$sql .= " AND post_password = ''";
		endif;
		if($this->is_active("sitepress-multilingual-cms/sitepress.php")):
			$sql .= $wpdb->prepare(" AND " . esc_sql($wp_icl_translations) . ".language_code = '%s'", $lang_code);
		endif;
		$sql .= $wpdb->prepare(" ORDER BY post_date " . esc_sql($orderbysort) . " limit %d,%d", $start_item, $max_items);
		$loop = $wpdb->get_results( $sql );
				
		if( $loop ):
			$max_post = $max_items;
			$out = '';
			foreach( $loop as $post ): setup_postdata($post);
				if($max_post-- <= 0 ) break;

				
				// Getting category template code.
				$out_atts = array_merge( array('default_post_types'=>$this->default_post_types), $atts );
				unset($out_atts['hook_secret_key']);
				$output_category_temp = $this->get_taxonomy_template($out_atts);

				$out_temp = '';
				$link = esc_url(get_permalink());
				$title = esc_html( wp_strip_all_tags( get_the_title() ) );
				$date =  esc_html( wp_strip_all_tags( get_the_date( $date_format ) ) );

				if(!empty($html_tag) && file_exists (dirname( __FILE__ ) . '/includes/tags/' . $html_tag . '.php')):
					include(dirname( __FILE__ ) . '/includes/tags/' . $html_tag . '.php');
				endif;
				if(!empty($hook_secret_key)):
					$out_atts = array_merge( array('title'=>$title, 'link'=>$link, 'date'=>$date, 'default_post_types'=>$this->default_post_types), $atts);
					unset($out_atts['hook_secret_key']);
					$out_t = wp_kses_post(apply_filters( 'lvp_each_item', $out_temp, $out_atts));
					if(isset($out_t['hook_secret_key']) && $hook_secret_key === $out_t['hook_secret_key']):
						$out .= $out_t['data'];
					else:	
						$out .= $out_temp;
					endif;
				else:
						$out .= $out_temp;
				endif;					
			endforeach;
			wp_reset_postdata();

		endif;

		return wp_kses_post($out);
	}

}

$wm = new lvp();