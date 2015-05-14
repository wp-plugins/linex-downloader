<?php

function linex_downloader_activate(){
	global $wpdb;
	$table_name = $wpdb->prefix . 'linex_downloader';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id int(11) NOT NULL AUTO_INCREMENT,
		start_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		finish_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		title text NOT NULL DEFAULT '',
		file_size INT(11) NOT NULL DEFAULT '0',
		file_name tinytext NOT NULL DEFAULT '',
		file_path text NOT NULL DEFAULT '',
		url text NOT NULL DEFAULT '',
		is_finished TINYINT NOT NULL DEFAULT '0',
		UNIQUE KEY id (id),
		KEY is_finished (is_finished),
		KEY start_time (start_time)
	) $charset_collate ENGINE=MyISAM;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );
	$upload_dir = wp_upload_dir();
	$newDir = $upload_dir['basedir'].DIRECTORY_SEPARATOR."linex_downloader";
	//die($newDir) ;
	@mkdir($newDir,0755,true);
	touch($newDir.DIRECTORY_SEPARATOR."index.html");
	
}

function linex_downloader_deactivate(){
}
