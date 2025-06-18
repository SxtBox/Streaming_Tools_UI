<?php
// https://mediabay.tv/
// WITH CURL
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");

// Directory to save output files
$outputDir = "Streams";
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

/**
 * Fetch content using cURL
 */
function get_data($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => array_map(
            fn($k, $v) => "$k: $v",
            array_keys($headers),
            $headers
        ),
        CURLOPT_USERAGENT => $headers['User-Agent'] ?? 'Mozilla/5.0 (PHP cURL)',
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if (!$response) {
        throw new Exception("cURL error: $error");
    }
    return $response;
}

/**
 * Main processing function
 */
function Generate_Streams() {
    global $outputDir;

    try {
        $configFile = "config.json";
        if (!file_exists($configFile)) {
            throw new Exception("The file 'config.json' was not found.");
        }

        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decoding 'config.json'. Ensure it's valid JSON.");
        }

        foreach ($config as $source) {
            if (empty($source['channels'])) {
                echo "No channels found in the configuration.\n";
                continue;
            }

            $baseUrl = $source['url'] ?? '';
            if (empty($baseUrl)) {
                echo "No URL found in the source configuration.\n";
                continue;
            }

            $headers = $source['headers'] ?? [];
            $headers['User-Agent'] = $headers['User-Agent'] ?? 'Mozilla/5.0';

            foreach ($source['channels'] as $channel) {
                $channelName = $channel['name'] ?? 'Unknown Channel';
                $variables = $channel['variables'] ?? [];

                $channelVariable = null;
                foreach ($variables as $var) {
                    if ($var['name'] === 'CHANNEL_NAME') {
                        $channelVariable = $var['value'];
                        break;
                    }
                }

                if (empty($channelVariable)) {
                    echo "Skipping $channelName: Missing 'CHANNEL_NAME' variable.\n";
                    continue;
                }

                $url = str_replace("CHANNEL_NAME", $channelVariable, $baseUrl);

                try {
                    $response = get_data($url, $headers);
                    $data = json_decode($response, true);

                    if (json_last_error() !== JSON_ERROR_NONE || empty($data['data'])) {
                        echo "Invalid API response for $channelName.\n";
                        continue;
                    }

                    $threadAddress = $data['data'][0]['threadAddress'] ?? null;
                    if (!$threadAddress) {
                        echo "Thread address missing for $channelName.\n";
                        continue;
                    }

                    $variations = [
                        [
                            "average_bandwidth" => 650000,
                            "bandwidth" => 810000,
                            "resolution" => "426x240",
                            "codec" => "avc1.4d0015,mp4a.40.2",
                            "track" => "tracks-v3a1/mono.m3u8"
                        ],
                        [
                            "average_bandwidth" => 1170000,
                            "bandwidth" => 1470000,
                            "resolution" => "640x360",
                            "codec" => "avc1.4d001e,mp4a.40.2",
                            "track" => "tracks-v2a1/mono.m3u8"
                        ],
                        [
                            "average_bandwidth" => 2420000,
                            "bandwidth" => 3030000,
                            "resolution" => "854x480",
                            "codec" => "avc1.4d001e,mp4a.40.2",
                            "track" => "tracks-v1a1/mono.m3u8"
                        ]
                    ];

                    $outputFile = "$outputDir/$channelName.m3u8";
                    $file = fopen($outputFile, "w");
                    if (!$file) {
                        throw new Exception("Failed to open file: $outputFile");
                    }

                    fwrite($file, "#EXTM3U\n");
                    foreach ($variations as $variation) {
                        $modifiedLink = str_replace("playlist.m3u8", $variation["track"], $threadAddress);
                        fwrite($file, "#EXT-X-STREAM-INF:AVERAGE-BANDWIDTH={$variation["average_bandwidth"]},BANDWIDTH={$variation["bandwidth"]},RESOLUTION={$variation["resolution"]},FRAME-RATE=25.000,CODECS=\"{$variation["codec"]}\",CLOSED-CAPTIONS=NONE\n");
                        fwrite($file, "$modifiedLink\n");
                    }

                    fclose($file);
                    echo "Playlist Created for $channelName: $outputFile\n";

                } catch (Exception $e) {
                    echo "Error fetching data for $channelName: " . $e->getMessage() . "\n";
                }
            }
        }

    } catch (Exception $e) {
        echo "Fatal Error: " . $e->getMessage() . "\n";
    }
}

// Run it
Generate_Streams();
