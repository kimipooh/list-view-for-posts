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
}