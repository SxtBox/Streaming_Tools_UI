<?php
require_once "vendor/autoload.php";

use Cocur\Slugify\Slugify;

/////////////////////////////////////////////////////////
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
  $protocol = 'http://';
} else {
  $protocol = 'https://';
}

$ROOT_PATH = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";
//$ROOT_URL = $protocol . $_SERVER['SERVER_NAME'] . ($_SERVER['PHP_SELF']) . "";
$ROOT_URL = $protocol . $_SERVER['SERVER_NAME'] . dirname($_SERVER['PHP_SELF']) . "/";

// Helper: Send HTTP request with cURL
function curl_request($url, $method = 'GET', $headers = [], $body = null) {
    $ch = curl_init();

    $opts = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 15,
    ];

    // Set method
    if (strtoupper($method) === 'POST') {
        $opts[CURLOPT_POST] = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }

    // Set headers
    if (!empty($headers)) {
        $headerLines = [];
        foreach ($headers as $key => $value) {
            $headerLines[] = "$key: $value";
        }
        $opts[CURLOPT_HTTPHEADER] = $headerLines;
    }

    curl_setopt_array($ch, $opts);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false) {
        echo "cURL error: $error\n";
        return null;
    }

    if ($statusCode >= 400) {
        echo "HTTP error $statusCode for $url\n";
        return null;
    }

    return $response;
}

// Extract stream URL using regex
function get_stream_url($url, $pattern, $method = "GET", $headers = [], $body = []) {
    $response = curl_request($url, $method, $headers, $body);

    if (!$response) {
        echo "Failed to fetch URL: $url\n";
        return null;
    }

    if (preg_match("/$pattern/", $response, $matches)) {
        return $matches[1] ?? $matches[0]; // Return capture group if exists
    }

    echo "No Match Found in Response For Pattern: $pattern\n";
    return null;
}

// Fetch and rewrite playlist (variant mode)
function playlist_variant_mode($url) {
    $response = curl_request($url);

    if (!$response) {
        echo "Failed to Fetch Playlist: $url\n";
        return "";
    }

    $lines = explode("\n", $response);
    $text = "";

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        if (str_starts_with($line, '#')) {
            $text .= $line . "\n";
        } else {
            $base = rtrim(dirname($url), '/') . '/';
            $text .= $base . $line . "\n";
        }
    }

    return $text;
}

function get_json_config($url) {
    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false || $code >= 400) {
        die("Failed to load config: $error (HTTP $code)\n");
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("JSON error: " . json_last_error_msg() . "\n");
    }

    return $data;
}

// Main entry
// FUNCTION WITH CURL
function main($configFile) {
    if (filter_var($configFile, FILTER_VALIDATE_URL)) {
        $config = get_json_config($configFile); // remote URL
    } elseif (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true); // local file
    } else {
        die("Missing Config File or Invalid URL: $configFile\n");
    }

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Invalid JSON: " . json_last_error_msg() . "\n");
    }

   $slugify = new Slugify();

    foreach ($config as $site) {
        $sitePath = $site["slug"];
        if (!is_dir($sitePath)) {
            mkdir($sitePath, 0777, true);
        }

        foreach ($site["channels"] as $channel) {
            $filename = $slugify->slugify(strtolower($channel["name"])) . ".m3u8";
            $filepath = "$sitePath/$filename";

            // Prepare URL
            $channelUrl = $site["url"];
            foreach ($channel["variables"] as $var) {
                $channelUrl = str_replace($var["name"], $var["value"], $channelUrl);
            }

            // Fetch stream URL
            $stream_url = get_stream_url(
                $channelUrl,
                $site["pattern"],
                $site["method"] ?? "GET",
                $site["headers"] ?? [],
                $site["body"] ?? []
            );

            if (!$stream_url || strpos($stream_url, $site["output_filter"]) === false) {
                echo "Skipping {$channel['name']}: stream not valid or missing output filter.\n";
                if (file_exists($filepath)) unlink($filepath);
                continue;
            }

            // Fetch final playlist
            $text = "";
            if ($site["mode"] === "variant") {
                $text = playlist_variant_mode($stream_url);
            } elseif ($site["mode"] === "master") {
                $bandwidth = $site["bandwidth"] ?? 800000;
                $text = "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=$bandwidth\n$stream_url\n";
            } else {
                echo "Invalid Mode For {$channel['name']}\n";
                continue;
            }

            if (!empty($text)) {
                file_put_contents($filepath, $text);
				header("Access-Control-Allow-Origin: *");
				header("Content-type: application/json");
                echo "Saved: $filepath\n";
            } else {
                if (file_exists($filepath)) unlink($filepath);
                echo "Removed Empty: $filepath\n";
            }
        }
    }
}

// FUNCTION WITH file_get_contents
function main1($configFile) {
    if (!file_exists($configFile)) {
        die("Missing Config File: $configFile\n");
    }
	$config = json_decode(file_get_contents($configFile), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Invalid JSON: " . json_last_error_msg() . "\n");
    }

    $slugify = new Slugify();

    foreach ($config as $site) {
        $sitePath = $site["slug"];
        if (!is_dir($sitePath)) {
            mkdir($sitePath, 0777, true);
        }

        foreach ($site["channels"] as $channel) {
            $filename = $slugify->slugify(strtolower($channel["name"])) . ".m3u8";
            $filepath = "$sitePath/$filename";

            // Prepare URL
            $channelUrl = $site["url"];
            foreach ($channel["variables"] as $var) {
                $channelUrl = str_replace($var["name"], $var["value"], $channelUrl);
            }

            // Fetch stream URL
            $stream_url = get_stream_url(
                $channelUrl,
                $site["pattern"],
                $site["method"] ?? "GET",
                $site["headers"] ?? [],
                $site["body"] ?? []
            );

            if (!$stream_url || strpos($stream_url, $site["output_filter"]) === false) {
                echo "Skipping {$channel['name']}: stream not valid or missing output filter.\n";
                if (file_exists($filepath)) unlink($filepath);
                continue;
            }

            // Fetch final playlist
            $text = "";
            if ($site["mode"] === "variant") {
                $text = playlist_variant_mode($stream_url);
            } elseif ($site["mode"] === "master") {
                $bandwidth = $site["bandwidth"] ?? 800000;
                $text = "#EXTM3U\n#EXT-X-VERSION:3\n#EXT-X-STREAM-INF:BANDWIDTH=$bandwidth\n$stream_url\n";
            } else {
                echo "Invalid Mode For {$channel['name']}\n";
                continue;
            }

            if (!empty($text)) {
                file_put_contents($filepath, $text);
				header("Access-Control-Allow-Origin: *");
				header("Content-type: application/json");
                echo "Saved: $filepath\n";
            } else {
                if (file_exists($filepath)) unlink($filepath);
                echo "Removed Empty: $filepath\n";
            }
        }
    }
}

// Run the script
//main("config.json");

// ----- Web Entry Point -----
$config_file = isset($_GET["config"]) ? $_GET["config"] : "config.json"; // Default fallback
main($config_file);