<?php
/*
 * The common tools for using an other function.
 *
 */

class lvp_library{
	// Escape array's values on 28/08/2017
	protected function security_check_array($array){
		if(empty($array)) return $array;
		if(is_array($array)):
			foreach($array as $k => $v):
				$array[$k] = $this->security_check_array($v);
			endforeach;
		else:
			$array = esc_html(wp_strip_all_tags($array)); 
		endif;

		return $array;
	}
	// Check a plugin is enabled or not on 28/08/2017.
	// The code is referred by https://firegoby.jp/archives/5237. 
	protected function is_active($plugin){
		if(function_exists('is_plugin_active')):
			return is_plugin_active($plugin);
		else:
			return in_array($plugin, get_option('active_plugins'), true);
		endif;
	}
	// Output the taxonomy template 
	// $taxonomy is taxonomy slug.
	// $taxonomy = "taxsonomy slug" or $taxonomy = array("teaxsonomy slug 1", "teaxsonomy slug 2")
	protected function get_taxonomy_template($atts){
		if(empty($atts)):
			return '';
		endif;
		$atts = $this->security_check_array($atts);
		extract($atts);

		global $post;

		$out = '';
		$output_category_temp_post_type = '';
		$output_category_temp_category = '';

		// If the post belongs to a custom post, get the custom post label.
		if(!empty($enable_view_post_type)):
			if( ! isset($default_post_types[get_post_type_object(get_post_type())->name]) ? $default_post_types[get_post_type_object(get_post_type())->name] : "" ):
				$custom_category_label = esc_html( get_post_type_object(get_post_type())->label );
				$output_category_temp_post_type = "<span class='{$html_tag_class}_post_type'>$custom_category_label</span>";
			endif;
		endif;

		// Tax
		if(!empty($enable_view_category)):
			foreach($category_taxonomy as $cat):
				$terms = get_the_terms($post->ID, $cat);
				if($terms && !is_wp_error($terms)):
					$term  = esc_html( isset($terms[0]->name) ? $terms[0]->name : "" ); // Only get first value in the terms.
					$term_slug = esc_attr( isset($terms[0]->slug) ? $terms[0]->slug : "" );
					if (strtolower($term) === 'uncategorized' || strtolower($term) === 'unclassified'): // 'uncategorized' and 'unclassified' are ignored.
						 continue;
					endif;
					$term_link = get_term_link($terms[0]);
					$output_category_temp_category .= "<span class='{$html_tag_class}_category_{$term_slug}'>";
					if(!is_wp_error($term_link) && !is_wp_error($term)):
						$output_category_temp_category .= "<a class='{$html_tag_class}_category_link_{$term_slug}' href='$term_link'>$term</a></span>";
					else:
						$output_category_temp_category .= "<span>$term</span>";
					endif;
				endif;
			endforeach;
		endif;

		$out = $output_category_temp_post_type . $output_category_temp_category;

		return $out;
	}
}