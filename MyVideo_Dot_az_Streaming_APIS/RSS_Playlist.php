<?php

function slug_to_title($slug) {
    return preg_replace_callback('/[^-]+/', function ($match) {
        // Uppercase common acronyms fully
        $word = $match[0];
        $upperAcronyms = ['az', 'tv', 'hd', 'atv', 'cbc'];

        if (in_array(strtolower($word), $upperAcronyms)) {
            return strtoupper($word);
        }

        return ucfirst(strtolower($word));
    }, str_replace('-', ' ', $slug));
}
//  Example usage:
/*
$slugs = [
    'atv-azad',
    'az-tv',
    'baku-tv',
    'mcj-tv-shop',
    'arb-24',
    'arb-gunes',
    'mtv-azerbaycan',
];

foreach ($slugs as $slug) {
    echo slug_to_title($slug) . PHP_EOL;
}
*/

function slug_to_title1($slug) {
    return preg_replace_callback('/[^-]+/', function ($match) {
        $word = strtolower($match[0]);

        // Force "tv" to always be uppercase
        if ($word === 'tv') {
            return 'TV';
        }

        // Capitalize acronyms if needed (optional)
        $acronyms = ['az', 'atv', 'cbc', 'arb', 'hd', 'mtv', 'tv'];
        if (in_array($word, $acronyms)) {
            return strtoupper($word);
        }

        // Default: ucfirst the word
        return ucfirst($word);
    }, str_replace('-', ' ', $slug));
}
/*
Example usage:
$slugs = [
    'atv-azad',
    'az-tv',
    'baku-tv',
    'mcj-tv-shop',
    'arb-24',
    'arb-gunes',
    'mtv-azerbaycan',
    'real-tv',
    'medeniyet-tv'
];

foreach ($slugs as $slug) {
    echo slug_to_title($slug) . PHP_EOL;
}
*/

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
  $protocol = 'http://';
} else {
  $protocol = 'https://';
}
$ROOT_URL = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";

$media_dir = "Streams";
$media_files = scandir($media_dir);

echo '<rss version="2.0" xmlns:jwplayer="http://rss.jwpcdn.com/">' . PHP_EOL;
echo '<channel>' . PHP_EOL . PHP_EOL;
header("Access-Control-Allow-Origin: *");
header("Content-type: application/xml");
foreach($media_files as $item) {
    if($item!="." && $item!="..") {
	$title = ($item);
	$title = substr($title, 0, (strlen ($title)) - (strlen (strrchr($title,'.'))));
	$title = slug_to_title1($title);
	$description = substr($item, 0, (strlen ($item)) - (strlen (strrchr($item,'.'))));
	$file = rawurlencode($item);

$file = str_replace(
array("\/\/","\/"),
array("//", "/"),
$file
);

$title = str_replace(
array("&","\/"),
array("&amp;", "/"),
$title
);

$description = str_replace(
array("&","\/"),
array("&amp;", "/"),
$description
);

$player_files = $ROOT_URL . $media_dir . "/" . $file;

    echo "<item>" . PHP_EOL;
	echo "<title>{$title}</title>" . PHP_EOL;
    echo "<description>". $description . "</description>" . PHP_EOL;
    echo "<jwplayer:image>https://png.kodi.al/tv/albdroid/logo_bar.png</jwplayer:image>" . PHP_EOL;
    echo '<jwplayer:source file="'.$player_files.'" />' . PHP_EOL;
    echo "</item>" . PHP_EOL . PHP_EOL;
   }
}
echo "</channel>" . PHP_EOL;
echo "</rss>" . PHP_EOL;