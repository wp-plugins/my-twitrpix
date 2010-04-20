<?php
/*
Plugin Name: My TwitrPix
Version: 1.0
Plugin URI: http://twitrpix.com/apps
Description: Display your recent TwitrPix photos.  Activate plugin and then drag the My TwitrPix widget into your sidebar under Appearance &raquo; <a href="widgets.php">Widgets</a>.  For additional help, visit <a href="http://twitrpix.com/apps" target="_blank">http://twitrpix.com/apps</a>.
Author: @TwitrPix
Author URI: http://twitrpix.com
*/

/*
	Based on My Twitpics plugin by Pepijn Koning
	 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('MAGPIE_CACHE_ON', 0); //2.7 Cache Bug

// Default options
$twitrpix_options['widget_fields']['title'] = array('label'=>'Widget Title:', 'type'=>'text', 'default'=>'My TwitrPix');
$twitrpix_options['widget_fields']['username'] = array('label'=>'Twitter Username:', 'type'=>'text', 'default'=>'');
$twitrpix_options['widget_fields']['num'] = array('label'=>'Photos (Max 10):', 'type'=>'text', 'default'=>'4');
$twitrpix_options['widget_fields']['size'] = array('label'=>'Thumbnail (Max 150 pixels wide):', 'type'=>'text', 'default'=>'75');
$twitrpix_options['widget_fields']['margin'] = array('label'=>'Margin (Default: 5):', 'type'=>'text', 'default'=>'5');
$twitrpix_options['widget_fields']['border'] = array('label'=>'Border Size (Default: 1):', 'type'=>'text', 'default'=>'1');
$twitrpix_options['widget_fields']['bordercolor'] = array('label'=>'Border color (Default: #fff):', 'type'=>'text', 'default'=>'#FFFFFF');
$twitrpix_options['widget_fields']['custom_css'] = array('label'=>'Custom CSS:', 'type'=>'checkbox', 'default'=> 0);

$twitrpix_options['prefix'] = 'twitrpix';

// Display the most recent TwitrPix user photos
function showMyTwitrPix($username = '', $num = 4, $size = 75, $margin = 5, $border = 1, $bordercolor = '#fff') {
	
	if ($num > 10 || empty($num) ) { $num = 10; }
	if ($size > 150 || empty($size) ) { $size = 150; }
	if (empty($margin)) { $margin = 5; }
	if (empty($border)) { $border = 1; }
	if (empty($bordercolor)) { $bordercolor = '#FFFFFF'; }

	$api = 'http://api.twitrpix.com/';
	$apiEndpoint = 'timeline/user/' . trim(strip_tags($username)) . '.xml?limit=' . $num;
	
	$file = @file_get_contents( $api . $apiEndpoint );
	
	for($i = 1; $i <= $num; ++$i) {

		$imageid = explode('<mediaid>', $file);
		$imageid = explode('</mediaid>', $imageid[$i]);
		$imageid = trim($imageid[0]);

		$thumburl = explode('<thumb>', $file);
		$thumburl = explode('</thumb>', $thumburl[$i]);
		$thumburl = trim($thumburl[0]);
		
		echo '<a href="http://twitrpix.com/'.$imageid.'" title="View Photo" target="_blank"><img src="'.$thumburl.'" width="'.$size.'" height="'.$size.'" style="margin: '.$margin.'px; border: '.$border.'px solid '.$bordercolor.';" class="twitrpix_img" /></a>';
	}
}


// TwitrPix widget init
function widget_twitrpix_init() {
	if (!function_exists('register_sidebar_widget'))
		return;
	
	$check_options = get_option('widget_twitrpix');
	if ($check_options['number']=='') {
			$check_options['number'] = 1;
			update_option('widget_twitrpix', $check_options);
	}

	function widget_twitrpix($args, $number = 1) {
	
		global $twitrpix_options;
		
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_twitrpix');
		
		// fill options with default values if value is not set
		$item = $options[$number];
		foreach($twitrpix_options['widget_fields'] as $key => $field) {
			if (! isset($item[$key])) {
				$item[$key] = $field['default'];
			}
		}
		
		// These lines generate our output.
		if ($item['username'] != '') {
			echo $before_widget . $before_title . $item['title'] . $after_title;
			
			if ( !isset($item['custom_css']) || $item['custom_css'] == 0) {
				echo '<link rel="stylesheet" href="http://assets.twitrpix.com/css/my-twitrpix.css" type="text/css" media="screen" />';
			}
			
			echo '<p align="left" class="twitrpix_mininav"><a class="twitrpix_buttonlink" href="http://twitrpix.com/user/'.$item['username'].'" title="View More" target="_blank">My TwitrPix</a> <a class="twitrpix_buttonlink" href="http://twitter.com/'.$item['username'].'" title="Follow me" target="_blank">Follow Me Twitter</a></p>';
			
			echo '<p class="widget_twitrpix">';
			showMyTwitrPix($item['username'], $item['num'], $item['size'], $item['margin'], $item['border'], $item['bordercolor']);
			echo '</p>';
			echo $after_widget;
		} else {
			echo '<!-- TwitrPix Widget:  Please configure this widget to display. //-->';
		}
	}
	
	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_twitrpix_control($number) {

		global $twitrpix_options;

		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_twitrpix');
		
		if ( isset($_POST['twitrpix-submit']) ) {

			foreach($twitrpix_options['widget_fields'] as $key => $field) {
				$options[$number][$key] = $field['default'];
				$field_name = sprintf('%s_%s_%s', $twitrpix_options['prefix'], $key, $number);

				if ($field['type'] == 'text') {
					$options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
				} elseif ($field['type'] == 'checkbox') {
					$options[$number][$key] = isset($_POST[$field_name]);
				}
			}

			update_option('widget_twitrpix', $options);
		}

		foreach($twitrpix_options['widget_fields'] as $key => $field) {
			
			$field_name = sprintf('%s_%s_%s', $twitrpix_options['prefix'], $key, $number);
			$field_checked = '';
			if ($field['type'] == 'text') {
				$field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
			} elseif ($field['type'] == 'checkbox') {
				$field_value = 1;
				if (! empty($options[$number][$key])) {
					$field_checked = 'checked="checked"';
				}
			}
			
			printf('<p><label for="%s">%s<br/> <input id="%s" name="%s" type="%s" value="%s" class="widefat" %s /></label></p>',
				$field_name, __($field['label']), $field_name, $field_name, $field['type'], $field_value, $field_checked);
		}
		echo '<input type="hidden" id="twitrpix-submit" name="twitrpix-submit" value="1" />';
	}
	

	function widget_twitrpix_setup() {
		$options = $newoptions = get_option('widget_twitrpix');
		
		if ( isset($_POST['twitrpix-number-submit']) ) {
			$number = (int) $_POST['twitrpix-number'];
			$newoptions['number'] = $number;
		}
		
		if ( $options != $newoptions ) {
			update_option('widget_twitrpix', $newoptions);
			widget_twitrpix_register();
		}
	}
	
	function widget_twitrpix_register() {
		
		$options = get_option('widget_twitrpix');
		$dims = array('width' => 200, 'height' => 300);
		$class = array('classname' => 'widget_twitrpix');

		for ($i = 1; $i <= 9; $i++) {
			$name = sprintf(__('My TwitrPix'), $i);
			$id = "twitrpix-$i"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, $i <= $options['number'] ? 'widget_twitrpix' : /* unregister */ '', $class, $i);
			wp_register_widget_control($id, $name, $i <= $options['number'] ? 'widget_twitrpix_control' : /* unregister */ '', $dims, $i);
		}
		
		add_action('sidebar_admin_setup', 'widget_twitrpix_setup');
	}

	widget_twitrpix_register();
}

// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_twitrpix_init');

?>