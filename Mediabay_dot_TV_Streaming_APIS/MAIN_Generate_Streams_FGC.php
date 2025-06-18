<?php
// https://mediabay.tv/
// WITH file_get_contents
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");

// Directory to save output files
$outputDir = "Streams";
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

/**
 * Generate_Streams: Processes the JSON configuration, fetches data, and generates playlist files.
 */
function Generate_Streams() {
    global $outputDir;

    try {
        // Load the JSON configuration from 'config.json'
        $configFile = "config.json";
        if (!file_exists($configFile)) {
            throw new Exception("The file 'config.json' was not found.");
        }

        $config = json_decode(file_get_contents($configFile), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error decoding 'config.json'. Ensure it's valid JSON.");
        }

        // Iterate through the channels in the JSON
        foreach ($config as $source) {
            if (!isset($source['channels']) || empty($source['channels'])) {
                echo "No channels found in the configuration.\n";
                continue;
            }

            // Base URL with placeholders
            $baseUrl = $source['url'] ?? '';
            if (empty($baseUrl)) {
                echo "No URL found in the source configuration.\n";
                continue;
            }

            // Headers (optional in JSON)
            $headers = $source['headers'] ?? [];
            $headers['User-Agent'] = $headers['User-Agent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';

            foreach ($source['channels'] as $channel) {
                // Get channel name and variables
                $channelName = $channel['name'] ?? 'Unknown Channel';
                $variables = $channel['variables'] ?? [];

                // Extract 'CHANNEL_NAME' variable value
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

                // Format URL by replacing placeholder
                $url = str_replace("CHANNEL_NAME", $channelVariable, $baseUrl);

                try {
                    // Fetch data from the URL
                    $options = [
                        "http" => [
                            "header" => implode("\r\n", array_map(
                                fn($key, $value) => "$key: $value",
                                array_keys($headers),
                                $headers
                            )),
                            "method" => "GET",
                            "timeout" => 10
                        ]
                    ];
                    $context = stream_context_create($options);
                    $response = @file_get_contents($url, false, $context);

                    if ($response === false) {
                        throw new Exception("Failed to fetch data.");
                    }

                    // Parse JSON response
                    $data = json_decode($response, true);
                    if (json_last_error() !== JSON_ERROR_NONE || empty($data['data'])) {
                        echo "Invalid API response for $channelName.\n";
                        continue;
                    }

                    // Extract threadAddress
                    $threadAddress = $data['data'][0]['threadAddress'] ?? null;
                    if (empty($threadAddress)) {
                        echo "Thread address missing for $channelName.\n";
                        continue;
                    }

                    // Define variations for streaming
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

                    // Create an output file for the channel
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
        echo $e->getMessage() . "\n";
    }
}

// Main entry point
Generate_Streams();