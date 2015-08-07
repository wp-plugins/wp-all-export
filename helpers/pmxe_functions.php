<?php
	
	if ( ! function_exists('wp_all_export_isValidMd5')){
		function wp_all_export_isValidMd5($md5 ='')
		{
		    return preg_match('/^[a-f0-9]{32}$/', $md5);
		}
	}	

	if ( ! function_exists('wp_all_export_get_relative_path') ){
		function wp_all_export_get_relative_path($path){

			$uploads = wp_upload_dir();

			return str_replace($uploads['basedir'], '', $path);			

		}
	}

	if ( ! function_exists('wp_all_export_get_absolute_path') ){
		function wp_all_export_get_absolute_path($path){			
			$uploads = wp_upload_dir();
			return ( strpos($path, $uploads['basedir']) === false and ! preg_match('%^https?://%i', $path)) ? $uploads['basedir'] . $path : $path;			
		}
	}