<?php

if ( !defined("USE_PLAYER_VERSION") )
{
    define("USE_PLAYER_VERSION", 8); // SELECT PLAYER VERSION 7 OR 8
}

// SHARE BUTTON
if ( !defined("ENABLE_SHARING_BUTTON") )
{
    define("ENABLE_SHARING_BUTTON", 0); // 0 = HIDE - 1 = SHOW
}

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

function get_extension_type($filename) {
    $ext1 = explode('.',$filename);
    $ext2 = array_reverse($ext1);
    $file_extenstion = strtolower($ext2[0]);
    return $file_extenstion;		
}

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
  $protocol = 'http://';
} else {
  $protocol = 'https://';
}

$ROOT_URL = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";

$Streams_Dir = "Streams";
$media_files = scandir($Streams_Dir);
?>
<!doctype html>
<html>
<head>
<meta http-equiv="refresh" content="6200">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Albdroid Player</title>
<link rel="shortcut icon" href="https://kodi.al/favicon.ico"/>
<link rel="icon" href="https://kodi.al/favicon.ico"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
<meta name="description" content="JW Player Code Builder" />
<meta name="author" content="Olsion Bakiaj - Endrit Pano" />
<meta property="og:site_name" content="JW Player Code Builder">
<meta property="og:locale" content="en_US">
<meta name="msapplication-TileColor" content="#0F0">
<meta name="theme-color" content="#0F0">
<meta name="msapplication-navbutton-color" content="#0F0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#0F0">
<body oncontextmenu="return false;">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css">
<?php
// PLAYER 7
if(USE_PLAYER_VERSION == "7"){
?>
<script src="https://ssl.p.jwpcdn.com/player/v/7.6.0/jwplayer.js"></script>
<script>
    jwplayer.key = 'snsWw4XuxGRuXJGnTnjXrOY/WHhPfREVS4YOGQ==';
</script>
<?php
}
?>
<?php
// PLAYER 8
if (USE_PLAYER_VERSION == "8") {
?>
<script src="https://content.jwplatform.com/libraries/CoaM1Zse.js"></script>
<script>
    jwplayer.key = 'AxtUTRaRN2XoKSqfng16IByVxBY6mENRZp0DVw==';
</script>
<?php
}
?>
<style type="text/css">
#player {
    position: absolute;
    width: 100% !important;
    height: 100% !important;
}
</style>
<style type="text/css">
body,td,th {
	color: #0F0;
}
body {
	background-color: #000;
}
a:link {
	color: #0FC;
}
a:visited {
	color: #3F6;
}
a:hover {
	color: #09F;
}
a:active {
	color: #009;
}
</style>
</head>

<body>
<div id="player"></div>
<script>
jwplayer("player").setup(
{
playlist: [
<?php
foreach($media_files as $item) {
    if($item!="." && $item!="..") {
	$title = $item;
	$title = substr($title, 0, (strlen ($title)) - (strlen (strrchr($title,'.'))));
	$title = slug_to_title1($title);
	$description = substr($item, 0, (strlen ($item)) - (strlen (strrchr($item,'.'))));
	$description = slug_to_title($description);
	$file = rawurlencode($item);
	$type = get_extension_type($item);
$image = "https://png.kodi.al/tv/albdroid/logo_bar.png";
$file = str_replace(
array("\/\/","\/"),
array("//", "/"),
$file
);

$player_files = $ROOT_URL . $Streams_Dir . "/" . $file;
echo '{' . PHP_EOL;
    echo "title: '". $title ."'," . PHP_EOL;
    echo "file: '".$player_files ."'," . PHP_EOL;
    echo "description: '". $description ."'," . PHP_EOL;
    echo "image: '". $image ."'," . PHP_EOL;
	echo "type: '". $type ."'," . PHP_EOL;
echo "}," . PHP_EOL. PHP_EOL;
}
}
?>
],
    stretching: "uniform",
    controls: true,
    displaytitle: true,
    fullscreen: "true",
    height: "100%",
    width: "100%",
    fallback: false,
    repeat: true,
    autostart: false, 
    primary: "html5",
    aspectratio: "16:9",
    renderCaptionsNatively: false,
    abouttext: "Albdroid",
    aboutlink: "http://albdroid.al/",
    mute: false,

skin: {
	name: "glow",
    active: "#fc0303",
    inactive: "#0F0",
    background: "transparent",
	text: "#0F0",
    icons: "#0F0",
    iconsActive: "#0F0",
    timeslider: {
    progress: "none"
    }
},
<?php
if (USE_PLAYER_VERSION == "8") {
?>
    logo: {
        file: 'https://png.kodi.al/tv/albdroid/logo_bar.png',
        position: 'control-bar',
        margin: '270',
        hide: 'false'
    },
<?php
}
?>
<?php
if (USE_PLAYER_VERSION == "7") {
?>
logo: {
    file: "https://png.kodi.al/tv/albdroid/smart_x1.png",
	position: "top-right"
},
<?php
}
?>
    autostart: false,
    repeat: true,
    androidhls: true,
    abouttext: "Albdroid",
    aboutlink: "http://albdroid.al/",
<?php
if (ENABLE_SHARING_BUTTON) {
?>
    sharing: {
        link: "http://albdroid.al/"
    },
<?php
}
?>
});
</script>
</body>
</html>