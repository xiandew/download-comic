<?php
/**
 * Created by Xiande Wen, November 2017
 */

function make_dir($dir){
	return is_dir($dir) or make_dir(dirname($dir)) and mkdir($dir, 0777);
}

function save_file($filename, $content) {
    make_dir(dirname($filename));
    $file = fopen($filename, "w");
    fwrite($file, $content);
    fclose($file);
}
