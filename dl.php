<?php
/**
 * Created by Xiande Wen, November 2017
 *
 * Module for downloading files.
 */
include_once("file.php");

function fetch_contents($furl){
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$header[]= 'User-Agent:Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36';
	curl_setopt($ch,CURLOPT_HTTPHEADER,$header);
	curl_setopt($ch, CURLOPT_URL, $furl);
	curl_setopt($ch, CURLOPT_REFERER, $furl);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,600);
	curl_setopt($ch, CURLOPT_TIMEOUT,600);

	return curl_exec($ch);
}
function download_remote_file($file_url, $save_to, $imgname){
	make_dir($save_to);
	if(file_exists($save_to.'/'.$imgname)){
		echo('<b>'.$file_url.'</b><br>');
	}else{
		$content = fetch_contents($file_url);
		if(!$content){
			$content = file_get_contents($file_url);
		}
		file_put_contents($save_to.'/'.$imgname, $content);
		echo($file_url.'<br>');
	}
}

?>
