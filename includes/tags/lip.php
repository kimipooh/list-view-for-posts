<?php 

// Customizing category html.
$output_category_temp = '';
$output_category_temp_post_type = '';
$output_category_temp_category = '';

if(!empty($enable_view_post_type)):
	if( ! isset($this->default_post_types[get_post_type_object(get_post_type())->name]) ? $this->default_post_types[get_post_type_object(get_post_type())->name] : "" ):
		$custom_category_label = esc_html( get_post_type_object(get_post_type())->label );
		$output_category_temp_post_type = "<p class='{$html_tag_class}_post_type'><span>$custom_category_label</span></p>";
	endif;
endif;

if(!empty($enable_view_category)):
	foreach($category_taxonomy as $cat):
		$terms = get_the_terms($post->ID, $cat);
		if($terms && !is_wp_error($terms)):
			$term  = esc_html( isset($terms[0]->name) ? $terms[0]->name : "" ); // Only get first value in the terms.
			$term_slug = esc_attr( isset($terms[0]->slug) ? $terms[0]->name : "" );
			if (strtolower($term) === 'uncategorized'): // 'uncategorized' is ignored.
				 continue;
			endif;
			$term_link = get_term_link($terms[0]);
			$output_category_temp_category .= "<p class='{$html_tag_class}_category_{$term_slug}'>";
			if(!is_wp_error($term_link) && !is_wp_error($term)):
				$output_category_temp_category .= "<span><a class='{$html_tag_class}_category_link_{$term_slug}' href='$term_link'>$term</a></span></p>";
			else:
				$output_category_temp_category .= "<span>$term</span></p>";
			endif;								
		endif;
	endforeach;
endif;

$output_category_temp = $output_category_temp_post_type . $output_category_temp_category;

$out_temp = <<< ___EOF___
 <li class='{$html_tag_class}_item'><p class='{$html_tag_class}_date'>$date</p>$output_category_temp<a class='{$html_tag_class}_link' href='$link'>$title</a></li>
 
___EOF___;
