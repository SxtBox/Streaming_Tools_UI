<?php
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");
// Headers for HTTP requests
$headers = [
    'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.95 Safari/537.36'
];

// URLs and corresponding names
$urls = ["https://api.tv8.md/v1/live"];
$names = ["tv8md"];

// Directory to save output files
$outputDir = "Streams";
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

// Process each URL and save to corresponding file
foreach ($urls as $index => $currentUrl) {
    $name = $names[$index];
    $outputFile = $outputDir . DIRECTORY_SEPARATOR . "$name.m3u8";

    try {
        // Fetch live stream data
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $currentUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            throw new Exception("Failed to fetch data from $currentUrl");
        }

        $responseJson = json_decode($response, true);
        if (!isset($responseJson["liveUrl"])) {
            throw new Exception("Key 'liveUrl' not found in response");
        }

        $mastlnk = $responseJson["liveUrl"];

        // Define resolution variations
        $variations = [
            "tracks-v2a1/mono.ts.m3u8" => [2960000, 3700000, "1024x576"],
            "tracks-v1a1/mono.ts.m3u8" => [6600000, 8250000, "1920x1080"]
        ];

        // Generate M3U8 content
        $content = "#EXTM3U\n";

        foreach ($variations as $variant => [$bandwidth, $avgBandwidth, $resolution]) {
            if (strpos($mastlnk, "index.m3u8") !== false) {
                $modifiedLink = str_replace("index.m3u8", $variant, $mastlnk);
                $content .= "#EXT-X-STREAM-INF:AVERAGE-BANDWIDTH=$avgBandwidth,BANDWIDTH=$bandwidth,RESOLUTION=$resolution,FRAME-RATE=25.000,CODECS=\"avc1.4d4029,mp4a.40.2\",CLOSED-CAPTIONS=NONE\n";
                $content .= "$modifiedLink\n";
            } else {
                throw new Exception("'index.m3u8' not found in liveUrl");
            }
        }

        // Write M3U8 content to file
        file_put_contents($outputFile, $content);
        echo "Created file: $outputFile\n";
    } catch (Exception $e) {
        echo "Error processing $currentUrl: " . $e->getMessage() . "\n";
    }
}