<?php

/*
   * Plugin Name: WP Naver Map in Post
   * Description: Add a naver map to your post
   * Version: 1.0
   * Author: Alghost
   * Author URI: http://blog.alghost.co.kr
   */

/*  Copyright 2015  WP Naver Map in Post  (email : alghost.lee@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

add_action('admin_init', 'alghost_action_mce_button');
add_shortcode('navermap', 'alghost_add_naver_map');
add_action('wp_ajax_alghost_get_locations_from_keyword', 'alghost_get_locations_from_keyword');
add_action('wp_ajax_nopriv_alghost_get_locations_from_keyword', 'alghost_get_locations_from_keyword');
add_action('admin_menu', 'alghost_navermap_admin_setup');

function alghost_get_locations_from_keyword(){
    $keyword = $_POST['keyword'];
    if (!$keyword)
        $keyword = '';
    $keyword = sanitize_text_field($keyword);
    update_post_meta(0, 'keyword', $keyword);

    $api_key = esc_attr(get_option('alghost-navermap-search-key'));
    $api_key = trim($api_key);

    $url = "http://openapi.naver.com/search?key=".$api_key."&query=".$keyword."&target=local&start=1&display=10";

    $response = file_get_contents($url);
    $object = simplexml_load_string($response);

    $channel = $object->channel;
    $key=0;
    foreach($channel->item as $value){
        echo '<li><a href="#" id="maplink_'.$key.'">'.($key+1).'. '.$value->title.': '.$value->roadAddress.'('.$value->address.')</a><input type="hidden" id="map_'.$key.'" value="'.$value->mapx.'|'.$value->mapy.'|'.$value->title.'" /></li>';
        $key++;
    }

    die();
}

function alghost_action_mce_button() {
    // Check if user have permission
    if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
        add_filter( 'mce_buttons', 'alghost_register_mce_button' );
        add_filter( 'mce_external_plugins', 'alghost_navermap_plugin' );
    }
}

// Function for new button
function alghost_navermap_plugin( $plugin_array ) {
    $plugin_array['alghost_navermap'] = plugin_dir_url(__FILE__).'/navermap.js';
    return $plugin_array;
}

// Register new button in the editor
function alghost_register_mce_button( $buttons ) {
    array_push( $buttons, 'navermap_button');
    return $buttons;
}

function alghost_add_naver_map($atts){
    $location = shortcode_atts(array(
                "mapx" => "0",
                "mapy" => "0",
                "title" => "None"
                ), $atts);
    $all_script = file_get_contents(plugin_dir_path(__FILE__).".SCRIPT_LAYOUT");
    $api_key = esc_attr(get_option('alghost-navermap-map-key'));
    $api_key = trim($api_key);
    
    $all_script = str_replace("__INPUT__PLUGINPATH", plugins_url(), $all_script);
    $all_script = str_replace("__INPUT__APIKEY", $api_key, $all_script);
    $all_script = str_replace("__INPUT__X", $location['mapx'], $all_script);
    $all_script = str_replace("__INPUT__Y", $location['mapy'], $all_script);
    $location['title'] = strip_tags($location['title']);
    $all_script = str_replace("__INPUT__TITLE", $location['title'], $all_script);

    return strval($all_script);
}

function alghost_navermap_admin_setup(){
    add_menu_page('네이버지도 설정 페이지', 'Naver map', 'manage_options', 'alghost-navermap-admin', 'alghost_navermap_admin_init');
    
    add_action('admin_init', 'alghost_navermap_admin_form_setup');
}
function alghost_navermap_admin_form_setup(){
    register_setting('alghost-navermap-keys', 'alghost-navermap-search-key');
    register_setting('alghost-navermap-keys', 'alghost-navermap-map-key');
}

function alghost_navermap_admin_init(){
    echo '<div class="wrap">';
    echo '<h2>네이버지도 설정</h2>';
    echo '<p>네이버지도 플러그인 설정화면입니다. 검색 API와 지도 API를 설정하신 후 사용하시길 바랍니다.</p>';
    echo '<form method="post" action="options.php">';
    settings_fields('alghost-navermap-keys');
    do_settings_sections('alghost-navermap-keys');
    echo '<table class="form-table">';
    echo '  <tr valign="top">';
    echo '      <th scope="row">검색 API</th>';
    echo '      <td><input type="text" name="alghost-navermap-search-key" value="'.esc_attr(get_option('alghost-navermap-search-key')).'" style="max-width:70%;"/></td>';
    echo '  </tr>';
    echo '  <tr valign="top">';
    echo '      <th scope="row">지도 API</th>';
    echo '      <td><input type="text" name="alghost-navermap-map-key" value="'.esc_attr(get_option('alghost-navermap-map-key')).'" style="max-width:70%;" /></td>';
    echo '  </tr>';
    echo '</table>';
    submit_button();
    echo '</form>';
    echo '</div>';
}
?>
