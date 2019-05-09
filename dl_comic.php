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

/**
 * take content of a single comic page and save information to <comic_name>/info.txt.
 * Return commic's name.
 */
function get_info($page_content){
	$pattern_info =
	'/class=\"comic_cover\"><img.src=\"(.*?)".*?alt=\"(.*?)\"\/>[\s\S]*?<ul'.
	'.class=\"Height_px22\">\s*<li>漫画类型：<a.*?>(.*?)<\/a>.*?\s*.*?作　　'.
	'者：<a.*?>(.*?)<\/a>[\s\S]*?故事简介：([\s\S]*?)<\/li/';

	preg_match($pattern_info, $page_content, $info);
	array_splice($info, 0, 1);
	$name = $info[1];
	$content =
		sprintf("Cover: %s\nName: %s\nCategory: %s\nAuthor: %s\nIntro: %s\n",
				$info[0], $name, $info[2], $info[3], $info[4]);
	save_file("./".$name."/info.txt", $content);
	echo "Information saved to ".$name."/info.txt";

	return $name;
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
 * download pictures for a chapter
 */
function dl_chapter_pictures($chapter_url, $save_to) {
	make_dir($save_to);

	$page_content = fetch_contents($chapter_url);

	preg_match($pcjs_pat, $page_content, $pics_js);
	print_r($pics_js);
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
	$host = "http://www.acg456.com";
	$route_comic_list = "/Catalog/default.aspx?PageIndex=1";

	$page_comic_list = fetch_contents($host.$route_comic_list);
	$pattern_comic = '/\"Conjunction\">\s*<a.href=\"(.*?)\".target=\"_blank/';
	preg_match_all($pattern_comic, $page_comic_list, $results);

	// get information about a comic by processing contents fetched from its url.
	foreach($results[1] as $route_comic){
		$page_content = fetch_contents($host.$route_comic);
		$comic_name = get_info($page_content);
		$chapters = get_chapters($page_content);

		foreach ($chapters as $chapter_name => $chapter_route) {
			dl_chapter_pictures($host.$chapter_route, "./".$comic_name."/".$chapter_name);
		}

		return;
	}
}

main();

?>
