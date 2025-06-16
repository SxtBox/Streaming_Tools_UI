<?php
/**
    * Advanced JSON Converter
    * Category: Streaming Tool
    * Author: Olsion Bakiaj
    * Created Date: Monday, 16 June 2025
*/

// IF ITS Actived Show Login With PIN Page
if (!defined("Protected_Mode"))
{
	// 1 or true to enable 0 or false to disable
    define("Protected_Mode", 1);
}

// IF ITS Actived Show Examples Structures at Bottom
if (!defined("Active_JSON_Examples"))
{
	// 1 or true to enable 0 or false to disable
    define("Active_JSON_Examples", 0);
}

// IF ITS Actived Show Example URL From /JSON_Data/ AT Enter Remote JSON URL Field see $JSON_Data_Path Value
if (!defined("Active_JSON_Example_Remote_URL"))
{
	// 1 or true to enable 0 or false to disable
    define("Active_JSON_Example_Remote_URL", 0);
}

if (Protected_Mode):
session_start();
$Enter_PIN_Code = "123456"; // Set Your Login PIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pin'])) {
    if ($_POST['pin'] === $Enter_PIN_Code) {
        $_SESSION['authenticated'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $error = "Incorrect PIN";
    }
}
if (empty($_SESSION['authenticated'])):
?>
<!DOCTYPE html>
<html>
<head>
<title>Enter PIN</title>
<link rel="shortcut icon" href="https://kodi.al/favicon.ico"/>
<link rel="shortcut icon" href="https://kodi.al/favicon.ico" type="image/x-icon" />
<meta http-equiv="cache-control" content="no-store">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="JSON to M3U Converter" />
<meta name="author" content="OLSION BAKIAJ - ENDRIT PANO" />
<meta property="og:site_name" content="JSON to M3U Converter">
<meta property="og:locale" content="en_US">
<meta name="msapplication-TileColor" content="#0F0">
<meta name="theme-color" content="#0F0">
<meta name="msapplication-navbutton-color" content="#0F0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#0F0">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    background: #000;
    color: lime;
    font-family: Arial;
    text-align: center;
    padding-top: 100px;
}

input {
    padding: 10px;
    font-size: 18px;
    background: black;
	font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    color: lime;  
    border: 1px solid #7fff00;
    border-radius: 1rem;
    box-shadow: 0 0 20px 0 rgba(127, 255, 0, 0.6);
}

button {
    background: lime;
    color: black;
    padding: 10px 20px;
    font-size: 16px;
    border: none;
}
</style>
</head>
<body>
<h1>🔐 Access Protected</h1>
<?php if (!empty($error)) echo "<p style='color: red;'>$error</p>"; ?>
<form method="post">
<input type="text" name="pin" placeholder="Enter PIN">
<br><br>
<button type="submit">Unlock</button>
</form>
</body>
</html>
<?php
exit;
endif;
endif;
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
  $protocol = 'http://';
} else {
  $protocol = 'https://';
}

$ROOT_PATH = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";
$ROOT_URL = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";
$JSON_Data_Path = $ROOT_PATH . "JSON_Data/" . "Minimal.json";

function get_data($input, $is_url) {
    if ($is_url) {
        $ch = curl_init($input);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return "cURL Error: " . curl_error($ch);
        }
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($statusCode >= 400) {
            return "HTTP Error: $statusCode";
        }
        curl_close($ch);
        return $response;
    } else {
        if (!file_exists($input)) return "Error: File $input does not exist.";
        return file_get_contents($input);
    }
}

function json_to_m3u($jsonData) {
    $m3uHeader = "#EXTM3U\r\n";
    $m3uContent = $m3uHeader;
    foreach ($jsonData as $channel) {
        $group = isset($channel['group']) ? $channel['group'] : '';
        $name = isset($channel['name']) ? $channel['name'] : '';
        $logo = isset($channel['logo']) ? $channel['logo'] : '';
        $tvg_id = isset($channel['tvg_id']) ? $channel['tvg_id'] : '';
        $url = isset($channel['url']) ? $channel['url'] : '';
        $m3uContent .= '#EXTINF:-1 group-title="' . addslashes($group) .
            '" tvg-id="' . addslashes($tvg_id) .
            '" tvg-logo="' . addslashes($logo) .
            '",' . addslashes($name) . "\r\n" . $url . "\r\n";
    }
    return $m3uContent;
}

$error = "";
$m3uResult = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonContent = "";
    $baseName = "playlist";
    if (!empty($_FILES['jsonfile']['name'])) {
        $jsonContent = file_get_contents($_FILES['jsonfile']['tmp_name']);
        $baseName = pathinfo($_FILES['jsonfile']['name'], PATHINFO_FILENAME);
    } elseif (!empty($_POST['jsonurl'])) {
        $jsonurl = trim($_POST['jsonurl']);
        $jsonContent = get_data($jsonurl, true);
        $parsed = parse_url($jsonurl);
        $baseName = isset($parsed['path']) ? pathinfo($parsed['path'], PATHINFO_FILENAME) : "playlist";
    } elseif (!empty($_POST['jsontext'])) {
        $jsonContent = trim($_POST['jsontext']);
        $baseName = "custom";
    }

    if (strpos($jsonContent, 'Error:') === 0 || strpos($jsonContent, 'cURL Error:') === 0 || strpos($jsonContent, 'HTTP Error:') === 0) {
        $error = $jsonContent;
    } else {
        $jsonData = json_decode($jsonContent, true);

        // Accept both array and {"channels": [...]}
        if (is_array($jsonData) && isset($jsonData['channels']) && is_array($jsonData['channels'])) {
            $jsonData = $jsonData['channels'];
        }

        if (!is_array($jsonData)) {
            $error = "Invalid JSON Data (must be a top-level array or object with 'channels' key).";
        } else {
            $m3uResult = json_to_m3u($jsonData);
            header('Content-Type: application/x-mpegURL');
            header('Content-Disposition: attachment; filename="' . $baseName . '.m3u"');
            echo $m3uResult;
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>JSON to M3U Converter - Dark Lime UI</title>
<link rel="shortcut icon" href="https://kodi.al/favicon.ico"/>
<link rel="shortcut icon" href="https://kodi.al/favicon.ico" type="image/x-icon" />
<meta http-equiv="cache-control" content="no-store">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="description" content="JSON to M3U Converter" />
<meta name="author" content="OLSION BAKIAJ - ENDRIT PANO" />
<meta property="og:site_name" content="JSON to M3U Converter">
<meta property="og:locale" content="en_US">
<meta name="msapplication-TileColor" content="#0F0">
<meta name="theme-color" content="#0F0">
<meta name="msapplication-navbutton-color" content="#0F0">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="#0F0">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Bootstrap 5.3+ -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome 6+ -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
/* Dark background And lime accents */
body {
    background: #181c1b;
    color: #bfff00;
    font-family: 'Segoe UI', 'Roboto', Arial, sans-serif;
    min-height: 100vh;
}

.card {
    background: #222826;
    border: 1px solid #7fff00;
    border-radius: 1rem;
    box-shadow: 0 0 20px 0 rgba(127, 255, 0, 0.6);
}

h2 {
    display: inline-block;
    margin: 0 auto 1rem;
    text-align: center;
    border-bottom: 2px solid #7fff00;
    padding-bottom: 0.5rem;
    color: #bfff00;
}

.btn-lime {
    background-color: #bfff00;
    border-color: #bfff00;
    color: #181c1b;
}

.btn-lime:hover,
.btn-lime:focus {
    background-color: #7fff00;
    border-color: #7fff00;
    color: #111;
}

.form-control,
.form-label {
    background: #232826;
    color: #bfff00;
    border: 1px solid #7fff00;
}

.form-control::placeholder {
    color: #bfff00;
    opacity: 0.7;
}

.form-control:focus {
    box-shadow: 0 0 0 .2rem rgba(191, 255, 0, 0.4);
    border-color: #bfff00;
}

.input-group-text {
    background: #232826;
    border-color: #7fff00;
    color: #bfff00;
}

.example {
    background: #161918;
    color: #93ff00;
    border: 1px solid #7fff00;
    border-radius: 0.5em;
    font-size: 0.98em;
    padding: 1em;
    margin-bottom: 1.5em;
    overflow-x: auto;
}

.example-title {
    font-size: 1.1em;
    font-weight: bold;
    color: #bfff00;
    margin-top: 1em;
}

.footer {
    color: #93ff00;
    text-align: center;
    font-size: 1em;
    margin-top: 4em;
    opacity: 0.88;
}

::selection {
    background: #7fff00;
    color: #111;
}

/* EXTRA CSS */
input[type="file"]::file-selector-button {
    background-color: #222826;
    /* Black background */
    color: #bfff00;
    /* Lime text */
    border: 1px solid #7fff00;
    /* Lime border */
    padding: 6px 12px;
    border-radius: 4px;
    cursor: pointer;
}

input[type="file"]::file-selector-button:hover {
    background-color: #111;
    color: #ccff33;
}

.example:hover {
    outline: 2px dashed #7fff00;
}

#jsontext {
    background-color: #222826;
    color: #bfff00;
    border: 1px solid #7fff00;
    font-family: monospace;
}

/*  */
#jsontext.form-control {
    background-color: #222826 !important;
    color: #bfff00 !important;
    border-color: #7fff00 !important;
}

/*Optional (to improve UX):*/
#jsontext::placeholder {
    color: #66ff66;
    opacity: 0.6;
}

#jsonurl {
    background-color: #222826;
    color: #bfff00;
    border: 1px solid #7fff00;
    font-family: monospace;
}

/*  */
#jsonurl.form-control {
    background-color: #222826 !important;
    color: #bfff00 !important;
    border-color: #7fff00 !important;
}

.or-separator {
  background: #111;          /* Dark background */
  color: #bfff00;            /* Lime text */
  border: 1px solid #7fff00; /* Lime border */
  display: inline-block;
  padding: 4px 12px;
  border-radius: 20px;
  font-weight: bold;
  margin: 1rem 0;
  box-shadow: 0 0 5px #7fff0055;
  text-transform: uppercase;
  letter-spacing: 1px;
}
</style>
</head>

<body>
<div class="container py-2">
<div class="row justify-content-center">
<div class="col-lg-12 col-md-9">
<div class="card p-4 shadow-lg">
<div class="text-center mb-1">
<i class="fa-solid fa-robot fa-3x"></i>
<h2 class="mt-2">TRC4 <span class="text-white bg-success px-2 rounded">JSON ➔ M3U</span> Converter</h2>
<?php if (Active_JSON_Example_Remote_URL): ?>
<br>
<small class="or-separator">All-in-One • <span class="fw-bold">Examples included</span></small>
<?php endif; ?>
</div>
<form method="post" enctype="multipart/form-data" autocomplete="off">
<div class="mb-1">
<label class="form-label" for="jsonfile">
<i class="fa fa-file-code"></i> Upload JSON File
</label>
<input class="form-control" type="file" name="jsonfile" id="jsonfile" accept=".json">
</div>

<div class="mb-1 text-center fw-bold">
<span class="or-separator">— OR —</span>
</div>
<!----/>
<div class="mb-3 text-center fw-bold">— OR —</div>
<!---->
<div class="mb-1">
<label class="form-label" for="jsonurl">
<i class="fa fa-link"></i> Enter Remote JSON URL
</label>

<div class="input-group">
<span class="input-group-text">
<i class="fa fa-globe"></i>
</span>

<input class="form-control" type="url" name="jsonurl" id="jsonurl" placeholder="https://example.com/list.json" <?php if (Active_JSON_Example_Remote_URL): ?>value="<?=$JSON_Data_Path;?>"<?php endif; ?>>
</div>
</div>
<div class="mb-1 text-center fw-bold">
<span class="or-separator">— OR —</span>
</div>
<!----/>
<div class="mb-3 text-center fw-bold">— OR —</div>
<!---->
<div class="mb-1">
<label class="form-label" for="jsontext">
<i class="fa fa-edit"></i> Paste JSON Structure Here
</label>

<textarea class="form-control" rows="6" name="jsontext" id="jsontext" placeholder="Paste Your JSON Here"></textarea>
</div>

<div class="d-grid gap-2 mt-4">
<button class="btn btn-lime btn-lg" type="submit">
<i class="fa-solid fa-circle-arrow-down"></i> Convert &amp; Download M3U
</button>
</div>
</form>
<?php if (!empty($error)): ?>
<div class="alert alert-danger mt-4" role="alert">
<i class="fa fa-triangle-exclamation"></i>
<strong>Error:</strong> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>
</div>
<?php
if (Active_JSON_Examples):
?>
<div class="mt-5">
<div class="example-title">
<i class="fa fa-lightbulb"></i> Example 1: Standard Array</div>
<pre class="example">
[
  {
    "group": "News",
    "name": "Global News",
    "logo": "https://example.com/logos/globalnews.png",
    "tvg_id": "globalnews.int",
    "url": "http://stream.example.com/live/globalnews.m3u8"
  },
  {
    "group": "Movies",
    "name": "CinemaPlus",
    "logo": "http://stream.example.com/logo.png",
    "tvg_id": "cinemaplus",
    "url": "http://stream.example.com/live/cinemaplus.m3u8"
  }
]
</pre>

<div class="example-title">
<i class="fa fa-lightbulb"></i> Example 2: With "channels" Object Key</div>
<pre class="example">
{
  "channels": [
    {
      "group": "Sports",
      "name": "SportZone 1",
      "logo": "https://example.com/logos/sportzone1.png",
      "tvg_id": "sportzone1",
      "url": "http://stream.example.com/live/sportzone1.m3u8"
    },
    {
      "group": "Music",
      "name": "Pop Hits",
      "logo": "https://example.com/logos/pophits.png",
      "tvg_id": "pophits",
      "url": "http://stream.example.com/live/pophits.m3u8"
    }
  ]
}
</pre>

<div class="example-title">
<i class="fa fa-lightbulb"></i> Example 3: Minimal Fields</div>
<pre class="example">
[
  {
    "url": "http://stream.example.com/live/minimal1.m3u8"
  },
  {
    "name": "Channel Only Name",
    "url": "http://stream.example.com/live/nameonly.m3u8"
  }
]
</pre>

<div class="example-title">
<i class="fa fa-lightbulb"></i> Example 4: Non-Latin/Unicode & Emojis</div>
<pre class="example">
[
  {
    "group": "Japanese",
    "name": "Nippon TV",
    "logo": "",
    "tvg_id": "nippon_tv",
    "url": "http://stream.example.com/live/nippon_tv.m3u8"
  },
  {
    "group": "K-Pop",
    "name": "Music Bank",
    "url": "http://stream.example.com/live/musicbank.m3u8"
  }
]
</pre>
</div>
<?php endif; ?>
<div class="footer">
<i class="fa-solid fa-circle-info"></i>
<span>Made with <i class="fa fa-heart" style="color:#93ff00"></i> for GitHub Users &copy; <?=date('Y')?> SxtBox</span>
</div>
</div>
</div>
</div>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/*
When you click on any .example, its JSON gets copied to the clipboard, and a ✅ Structure Copied message briefly appears at the bottom.
*/
  document.querySelectorAll('.example').forEach(pre => {
    pre.style.cursor = 'pointer';
    pre.title = 'Click to Copy JSON Structure';
    pre.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(pre.innerText);
        pre.style.backgroundColor = '#2e3b2d';
        pre.innerText += "\n\n✅ Structure Copied!";
        setTimeout(() => {
          pre.innerText = pre.innerText.replace("\n\n✅ Structure Copied!", "");
          pre.style.backgroundColor = '#161918';
        }, 1200);
      } catch (err) {
        alert("❌ Copy Failed: " + err);
      }
    });
  });
</script>

<script>
// auto-paste into the textarea
  document.querySelectorAll('.example').forEach(pre => {
    pre.style.cursor = 'pointer';
    pre.title = 'Click to copy & Paste Into Textarea';
    pre.addEventListener('click', () => {
      const text = pre.innerText;
      navigator.clipboard.writeText(text).then(() => {
        // Optional: highlight success
        pre.style.backgroundColor = '#2e3b2d';
        pre.innerText += "\n\n✅ Copied & Pasted!";
        setTimeout(() => {
          pre.innerText = pre.innerText.replace("\n\n✅ Copied & Pasted!", "");
          pre.style.backgroundColor = '#161918';
        }, 1200);
        // Paste into textarea if exists
        const textarea = document.getElementById('jsontext');
        if (textarea) {
          textarea.value = text;
          textarea.focus();
        }
      }).catch(err => {
        alert("❌ Copy Failed: " + err);
      });
    });
  });
</script>
</body>
</html>