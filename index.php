<?php
/**
 * Created by Xiande Wen, November 2017
 * Last modified: 10:25, 2019-05-09
 * This code is presented "as is" without any guarantees.
 */
include_once("file.php");
include_once("download.php");

set_time_limit(0);
ini_set('max_execution_time', '0');

/*----------------------------------------------------------------------------*/

$download_root_dir = "./downloads";

$host = "http://www.acg456.com";
$comic_list_route = "/Catalog/default.aspx?PageIndex=1";
$comic_list_pattern =
'/\"Conjunction\">\s*<a.href=\"(.*?)\".target=\"_blank/';

$info_pattern =
'/class=\"comic_cover\"><img.src=\"(.*?)".*?alt=\"(.*?)\"\/>[\s\S]*?<ul'.
'.class=\"Height_px22\">\s*<li>漫画类型：<a.*?>(.*?)<\/a>.*?\s*.*?作　　'.
'者：<a.*?>(.*?)<\/a>[\s\S]*?故事简介：([\s\S]*?)<\/li/';
$info_format = "Cover: %s\nName: %s\nCategory: %s\nAuthor: %s\nIntro: %s\n";
$info_filename = "info.txt";

$chapters_pattern = '/<li><a.href=\"(.*?)\".target=\"_blank\">(.*?)<\/a>/';

// pattern matching params for AJAX requesting pictures for a chapter
$ajax_params_pattern =
'/<script.language=\"javascript\">[\s\S]*var.tskReady.=[\s\S]*?var.c.=.'.
'(.*?);[\s\S]*var.fn.=.\"(.*?)\";[\s\S]*var.nv.=.\"(.*?)\";/';

$ajax_api_route = '/ajax/Common.ashx?op=';
$ajax_option = 'getPics';
$ajax_url_format = $host.$ajax_api_route.$ajax_option.'&cid=%s&serial=%s&path=%s';

/*----------------------------------------------------------------------------*/

/**
 * take content of a single comic page and save information to
 * <comic_name>/info.txt. Return commic's download directory
 * (eg. "<download_root_dir>/<comic_name>")
 */
function get_info($page_content){

	preg_match($GLOBALS['info_pattern'], $page_content, $info);
	array_splice($info, 0, 1);
	list($cover, $name, $category, $author, $intro) = $info;

	$content = sprintf($GLOBALS['info_format'], $cover, $name, $category, $author, $intro);

	$download_comic_dir = $GLOBALS['download_root_dir']."/".$name;
	$info_filepath = $download_comic_dir."/".$GLOBALS['info_filename'];

	save_file($info_filepath, $content);
	echo "Comic information saved to ".$info_filepath."<br>";

	return $download_comic_dir;
}

function get_chapters($page_content){
	preg_match_all($GLOBALS['chapters_pattern'], $page_content, $chapters_arr);

	$chapter_routes = $chapters_arr[1];
	$chapter_names = $chapters_arr[2];

	$chapters = Array();
	for($i = 0; $i < sizeof($chapter_names); $i++) {
		$chapters[strip_tags($chapter_names[$i])] = $chapter_routes[$i];
	}

	return $chapters;
}

/**
 * The previous version of the website (ex. 2017-11) stores all picture urls
 * in a .js file. Picture urls can be easily got by parsing the js file.
 *
 * However, the current version of the website using AJAX to request picture
 * urls from back-end and store it in a variable and loaded pictures
 * asynchronously.
 *
 * One thing to do is to mimic AJAX call with PHP curl. However, since the AJAX
 * is using get method, the resource can be easily retrieved by visiting a
 * correctly formated url. Three parameters are required by the request and
 * all presented in plain javascript text in the html body.
 *
 * A valid format should be looking like the following:
 * http://www.acg456.com/ajax/Common.ashx?op=getPics&cid=<c>&serial=<fn>&path=<nv>
 */
function dl_chapter_pictures($chapter_url, $save_to) {
	make_dir($save_to);

	$page_content = fetch_contents($chapter_url);

	preg_match($GLOBALS['ajax_params_pattern'], $page_content, $params);
	array_splice($params, 0, 1);
	list($c, $fn, $nv) = $params;
	$ajax_url = sprintf($GLOBALS['ajax_url_format'], $c, $fn, $nv);

	$pictures_url = json_decode(fetch_contents($ajax_url), true)["data"];

	foreach($pictures_url as $pic_url){
		download_remote_file($pic_url, $save_to, basename($pic_url));
		echo $pic_url." downloaded<br>";
	}
	exit(0);
}

function main() {
	$comic_list_content = fetch_contents($GLOBALS['host'].$GLOBALS['comic_list_route']);
	preg_match_all($GLOBALS['comic_list_pattern'], $comic_list_content, $comic_list);

	// get a comic by processing contents fetched from its url.
	foreach($comic_list[1] as $comic_route){
		$page_content = fetch_contents($GLOBALS['host'].$comic_route);
		// get basic information
		$download_comic_dir = get_info($page_content);

		// get chapters
		$chapters = get_chapters($page_content);

		// download pictures for each chapter
		foreach ($chapters as $chapter_name => $chapter_route) {
			dl_chapter_pictures($GLOBALS['host'].$chapter_route, $download_comic_dir."/".$chapter_name);
		}
	}
}

main();

?>
