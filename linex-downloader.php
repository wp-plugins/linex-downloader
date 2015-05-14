<?php
/*
Plugin Name: Linex Downloader
Plugin URI: http://github.com/tajary/linex-downloader
Description: Downloads files from web and puts them in the uploads folder
Version: 1.0
Author: Alireza Tajary
Author URI: http://tajary.ir
Text Domain: linex-downloader
Domain Path: /languages
License: GPLv2
*/

/* Copyright 2015 Alireza Tajary (email : tajary@gmail.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301
USA
*/
/* @var $wpdb wpdb*/
defined("ABSPATH")||die("invalid access");
defined('LINEX_DOWNLOADER_DIR') || define('LINEX_DOWNLOADER_DIR',  plugin_dir_path(__FILE__));
defined('LINEX_DOWNLOADER_URL') || define('LINEX_DOWNLOADER_URL',  plugins_url("/",__FILE__));

include LINEX_DOWNLOADER_DIR."includes/linex_downloader_installation.php";


register_activation_hook( __FILE__, 'linex_downloader_activate' );
register_deactivation_hook( __FILE__, 'linex_downloader_deactivate' );

function linex_downloader_init() {
	load_plugin_textdomain( 'linex-downloader', false, plugin_basename(LINEX_DOWNLOADER_DIR."languages") );
}
add_action('plugins_loaded', 'linex_downloader_init');

add_action( 'admin_menu', 'linex_downloader_create_menu' );

function linex_downloader_create_menu(){
	add_menu_page( __("Linex Downloader","linex-downloader"), __("Linex Downloader","linex-downloader"), 'manage_options',
			'linex-downloader', 'linex_downloader_admin_page' );
	add_submenu_page( 'linex-downloader',  __("Add Download","linex-downloader"),  __("Add Download","linex-downloader"),
			'manage_options', 'linex-downloader-download', 'linex_downloader_download_file_admin_page' );
	//add_submenu_page('linex-downloader',  __("List Downloads","linex-downloader"), __("List Downloads","linex-downloader"), 'manage_options',
	//		'linex-downloader', 'linex_downloader_admin_page' );
}

function linex_downloader_admin_page(){
	global $wpdb;
	if(!class_exists('Linex_WP_List_Table')){
		include LINEX_DOWNLOADER_DIR."includes/class-linex-wp-list-table.php";	
	}
	if(!class_exists('LinexMyListTable')){
		include LINEX_DOWNLOADER_DIR."includes/class-my-table.php";	
	}
	$action = $_GET['action'];
	if($action == "delete"){
		wp_verify_nonce($_GET['nonce'], 'delete');
		$id = $_GET["id"]+0;
		$tablename = $wpdb->prefix."linex_downloader";
		$filename = $wpdb->get_var("select file_name from $tablename where id=$id");
		$upload_dir = wp_upload_dir();
		$file = $upload_dir['basedir'].DIRECTORY_SEPARATOR."linex_downloader"
			.DIRECTORY_SEPARATOR.$filename;
		@unlink($file);
		$wpdb->query("delete from $tablename where id=$id");
		
	}
	$listTable = new LinexMyListTable();
	$listTable->prepare_items();
	echo "<h2>".__("list of downloads","linex-downloader")."</h2>";
	$listTable->display();
	
?>
<script>
jQuery(".column-id").css("width","20px");
jQuery(".column-manage").css("width","60px");
jQuery(".column-file_size").css("width","60px");
</script>
<?php

}
function downloadOneFile($url, $file){
	set_time_limit(3600);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	
	$f = fopen($file,'w');		
	curl_setopt($ch, CURLOPT_FILE, $f);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 5.1; rv:23.0) Gecko/20100101 Firefox/23.0");
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_exec($ch);
	curl_close($ch);
	fclose($f);	
}
function linex_downloader_download_file_admin_page(){
	if(count($_POST)){
		global $wpdb;
		check_admin_referer( 'linex-downloader-download' );
		//var_dump($_POST);
		$data = array();
		$data['url'] = esc_url($_POST['url']);
		$data['title'] = esc_html($_POST['title']);
		$data['file_name'] = time(0)."-".sanitize_file_name($_POST['filename']);
		$data["start_time"] = date("Y-m-d H:i:s");
		$data["is_finished"] = 0;
		$data["file_path"] = $data["file_name"];
		$tablename = $wpdb->prefix."linex_downloader";
		$wpdb->insert($tablename,
			$data	
		);
		$upload_dir = wp_upload_dir();
		$file = $upload_dir['basedir'].DIRECTORY_SEPARATOR."linex_downloader"
			.DIRECTORY_SEPARATOR.$data["file_name"];
		downloadOneFile($data['url'], $file);
		$newData['finish_time'] = date("Y-m-d H:i:s");
		$newData["is_finished"] = 1;
		$newData["file_size"] = filesize($file);
		$wpdb->update($tablename, $newData, array("start_time"=>$data["start_time"]));
		echo "<div class='updated notice' style='padding:4px'>";
		_e("Your File  is Uploaded.","linex-downloader");
		echo "</div>";
	}
	
?>
<div class="wrap">
	<h2><?php _e("Download new file","linex-downloader")?></h2>
	<div id="sending" style="display:none;padding:4px" class='updated notice'><?php _e("Be patient, file is downloading...","linex-downloader")?></div>
	<form method="post" id="post-file">
		<input type="hidden" name="action" value="linex-downloader-download" />

		<?php wp_nonce_field( 'linex-downloader-download' ); ?>

		<?php _e("URL","linex-downloader")?>: <br />
		<input type="text"  style="direction: ltr;text-align: left;width:100%" name="url" value=""/>
		<br />
		<?php _e("Filename","linex-downloader")?>: <br /> 
		<input type="text" style="direction: ltr;text-align: left;width:100%" name="filename" value=""/>
		<br />
		<?php _e("Title","linex-downloader")?>: <br />
		<input type="text" name="title" style="width:100%" value=""/>
		<br />
		<input type="submit" value="<?php _e("Download","linex-downloader")?>" class="button-primary"/>
	</form>
</div>
<script>	
jQuery("#post-file").on("submit", function(){
	jQuery("#sending").css("display","block");
	return true;
});
</script>
<?php
}
