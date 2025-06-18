<?php
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
  $protocol = 'http://';
} else {
  $protocol = 'https://';
}

$ROOT_PATH = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";
$ROOT_URL = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";
?>
<html lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>RSS JW Playlist Player</title>
<link rel="shortcut icon" href="https://kodi.al/panel.ico"/>
<link rel="icon" href="https://kodi.al/panel.ico"/>
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
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/css/bootstrap.min.css">
<!-- LIBRARY -->
<script src="https://content.jwplatform.com/libraries/CoaM1Zse.js"></script>
<style>
.jw-rightclick ul{
	display:none;
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
<style>
    body {
      background: black;
      height: 100%;
    }
    #player {
      height: auto !important;
      width: 100% !important;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      margin: auto !important;
      position: fixed !important;
    }
</style>

<style type="text/css">
.opacityPulse-css {
    animation: opacityPulse 2s ease-out;
    animation-iteration-count: infinite; 
    opacity: 1;
}

@-webkit-keyframes pulsate {
    0% {-webkit-transform: scale(0.1, 0.1); opacity: 0.0;}
    50% {opacity: 1.0;}
    100% {-webkit-transform: scale(1.2, 1.2); opacity: 0.0;}
}

/* Make the element's opacity pulse*/
/* Usage
    .myElement {
        animation: opacityPulse 1s ease-out;
        animation-iteration-count: infinite;
        opacity: 0; 
    }
*/
@-webkit-keyframes opacityPulse {
    0% {opacity: 0.0;}
    50% {opacity: 1.0;}
    100% {opacity: 0.0;}
}

/* Make the element's background pulse. I call this alertPulse because it is red. You can call it something more generic. */
/* Usage
    .myElement {
        animation: alertPulse 1s ease-out;
        animation-iteration-count: infinite;
        opacity: 1; 
    }
*/
@-webkit-keyframes alertPulse {
    0% {background-color: #9A2727; opacity: 1;}
    50% {opacity: red; opacity: 0.75; }
    100% {opacity: #9A2727; opacity: 1;}
}


/* Make the element rotate infinitely. */
/* 
Usage
    .myElement {
        animation: rotating 3s linear infinite;
    }
*/
@keyframes rotating {
  from {
    -ms-transform: rotate(0deg);
    -moz-transform: rotate(0deg);
    -webkit-transform: rotate(0deg);
    -o-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  to {
    -ms-transform: rotate(360deg);
    -moz-transform: rotate(360deg);
    -webkit-transform: rotate(360deg);
    -o-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
</style>
<style type="text/css">
#player {
    position: absolute;
    width: 100% !important;
    height: 100% !important;
}
</style>
</head>

<body topmargin="0" leftmargin="0" oncontextmenu="return false" style="background:black;">
<div class="opacityPulse-css" onClick="nextItem();" id="divOver" style="background-color: transparent; margin-left:0px; margin-top:0px; opacity: 1.00; position:absolute; z-index:10000;">
</div>
<div style="position: fixed; z-index: 500; right: 8px; top: 8px;">
<img height="70px" border="0" src="https://png.kodi.al/tv/albdroid/logo_bar.png">
</div>

<script>
    jwplayer.key = 'AxtUTRaRN2XoKSqfng16IByVxBY6mENRZp0DVw==';
</script>

<div id="player"></div>
<script type="text/javascript">

jwplayer("player").setup(
{
    "playlist": "<?=$ROOT_URL;?>RSS_Playlist.php",

/* STRETCHING OPTIONS
RESOLUTION
stretching = object-fit
none =none
exactfit = fill
fill = cover
uniform	= contain*
*/
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
    displaydescription: false,
    visualplaylist: "true",

skin: {
	name: "netflix",
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

    logo: {
        file: "https://png.kodi.al/tv/albdroid/logo_bar.png",
        position: 'control-bar',
        margin: '270',
        hide: 'false'
    },

    autostart: false,
    repeat: true,
    androidhls: true,
});
</script>
</body>
</html>