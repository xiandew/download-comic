<?php
/**
 * Created by Xiande Wen, November 2017
 * Last modified: 10:25, 2019-05-09
 *
 * This is a program for downloading pictures and extracting information
 * about a comic from <http://www.acg456.com/> for personal interests.
 * Regular expresssions are used to mathch Particular section of a page.
 * I understand that the design of this program is not so good since it
 * hardwires strings to some extent but it serves the functionality.
 *
 * This is an updated version. The original version is not suitable for
 * the purpose due to the reformation of the website since the execution
 * of this program requires exact match of particular contents.
 *
 * To run this program, be sure that the server and PHP are configured
 * properly and then simply try to reach this file from any browser through
 * localhost (eg. http://localhost/dl_comic.php).
 */
include_once("file.php");
include_once("dl.php");

set_time_limit(0);
ini_set('max_execution_time', '0');

/*----------------------------------------------------------------------------*/

$download_root_dir = "./downloads"

$host = "http://www.acg456.com";
$comic_list_route = "/Catalog/default.aspx?PageIndex=1";

$comic_list_pattern =
'/\"Conjunction\">\s*<a.href=\"(.*?)\".target=\"_blank/';

$info_pattern =
'/class=\"comic_cover\"><img.src=\"(.*?)".*?alt=\"(.*?)\"\/>[\s\S]*?<ul'.
'.class=\"Height_px22\">\s*<li>漫画类型：<a.*?>(.*?)<\/a>.*?\s*.*?作　　'.
'者：<a.*?>(.*?)<\/a>[\s\S]*?故事简介：([\s\S]*?)<\/li/';

$info_format =
"Cover: %s\nName: %s\nCategory: %s\nAuthor: %s\nIntro: %s\n";

$info_filename = "info.txt"


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

	$content =
		sprintf($GLOBALS['info_format'], $cover, $name, $category, $author, $intro);

	$download_comic_dir = $GLOBALS['download_root_dir']."/".$name;

	$info_filepath = $download_comic_dir."/".$GLOBALS['info_filename'];

	save_file($info_filepath, $content);
	echo "Comic information saved to ".$info_filepath."<br>";

	return $download_comic_dir;
}

function get_chapters($page_content){
	$pattern_chapters = '/<li><a.href=\"(.*?)\".target=\"_blank\">(.*?)<\/a>/';
	preg_match_all($pattern_chapters, $page_content, $chapters_arr);

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
 */
function dl_chapter_pictures($chapter_url, $save_to) {
	make_dir($save_to);

	$page_content = fetch_contents($chapter_url);

	print($page_content);
	exit(0);

	$pics_js = fetch_contents($pics_js[1]);
	$phst_pat = '/hosts.=.\[\"(.*?)\"/';
	preg_match($phst_pat, $pics_js, $pics_hst);
	$pics_pat = '/picAy\[.*?\].=.\"(.*?)\";/';
	preg_match_all($pics_pat, $pics_js, $pics_arr);
	foreach($pics_arr[1] as $pic){
		$pic_url = $pics_hst[1].$pic;
		print($pic_url.'</br>');
	}
	exit(0);
}

function main() {
	$comic_list_content = fetch_contents($GLOBALS['host'].$GLOBALS['comic_list_route']);
	preg_match_all($GLOBALS['comic_list_pattern'], $comic_list_content, $comic_list);

	// get information about a comic by processing contents fetched from its url.
	foreach($comic_list[1] as $comic_route){
		$page_content = fetch_contents($GLOBALS['host'].$comic_route);
		$download_comic_dir = get_info($page_content);
		$chapters = get_chapters($page_content);

		foreach ($chapters as $chapter_name => $chapter_route) {
			dl_chapter_pictures($GLOBALS['host'].$chapter_route,
										$download_comic_dir.$chapter_name);
		}

		return;
	}
}

main();

?>
