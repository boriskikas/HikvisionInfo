<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


function makeIsapiRequest($url, $username, $password) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_DIGEST, 
        CURLOPT_USERPWD => "$username:$password",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 5, 
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_FOLLOWLOCATION => true 
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE); 
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
     
        error_log("Camera proxy failed for URL: $url, HTTP Code: $httpCode, Error: $error");
        return ['error' => "Failed to fetch image: " . ($error ?: "HTTP $httpCode")];
    }
    return ['data' => $response, 'contentType' => $contentType];
}


$ip = $_GET['ip'] ?? '';
$port = $_GET['port'] ?? '81';
$username = $_GET['user'] ?? 'admin';
$password = $_GET['pass'] ?? 'Admin1234';
$channelId = $_GET['channel'] ?? '101';
$download = isset($_GET['download']) && $_GET['download'] === 'true'; 

if (empty($ip)) {
    http_response_code(400);
    die("Error: IP address is required.");
}

$protocol = ($port == '443') ? 'https' : 'http';

$snapshotUrl = "$protocol://$ip:$port/ISAPI/Streaming/channels/$channelId/picture";


$snapshotDir = 'snapshots/';
if (!is_dir($snapshotDir)) {
    if (!mkdir($snapshotDir, 0777, true)) {
        error_log("Failed to create snapshot directory: $snapshotDir");
      
    }
}

$filename = $snapshotDir . 'channel_' . $channelId . '.jpg'; 
$cacheDuration = 5; 

$imageData = null;
$contentType = null;
$extension = 'jpg'; 


if (!$download && is_file($filename) && (time() - filemtime($filename) < $cacheDuration)) {
    
    $imageData = file_get_contents($filename);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $contentType = finfo_file($finfo, $filename);
    finfo_close($finfo);
   
    $path_parts = pathinfo($filename);
    $extension = $path_parts['extension'] ?? 'jpg';

} else {
   
    $response = makeIsapiRequest($snapshotUrl, $username, $password);

    if (isset($response['error'])) {
        http_response_code(500);
        error_log("Error fetching image from camera: " . $response['error']);
        
        header("Content-Type: image/svg+xml");
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 800 450"><rect width="100%" height="100%" fill="#f8f9fa"/><text x="50%" y="50%" font-family="sans-serif" font-size="24" fill="#dc3545" text-anchor="middle" dominant-baseline="middle">ERROR: ' . htmlspecialchars($response['error']) . '</text></svg>';
        exit;
    }

    $imageData = $response['data'];
    $contentType = $response['contentType'];

    
    if (strpos($contentType, 'image/png') !== false) {
        $extension = 'png';
    } elseif (strpos($contentType, 'image/gif') !== false) {
        $extension = 'gif';
    }
  
    $filename = $snapshotDir . 'channel_' . $channelId . '.' . $extension;

    
    if (is_dir($snapshotDir) && !empty($imageData)) {
        if (file_put_contents($filename, $imageData) === false) {
            error_log("Failed to write image to file: $filename");
        }
    }
}


if ($download) {
    header("Content-Disposition: attachment; filename=\"snapshot-channel-{$channelId}-" . time() . '.' . $extension . "\"");
}


header("Content-Type: " . ($contentType ?: 'image/jpeg')); 
if ($imageData) {
    echo $imageData;
} else {
    
    header("Content-Type: image/svg+xml");
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" viewBox="0 0 800 450"><rect width="100%" height="100%" fill="#f8f9fa"/><text x="50%" y="50%" font-family="sans-serif" font-size="16" fill="#6c757d" text-anchor="middle" dominant-baseline="middle">Nije moguće učitati sliku</text></svg>';
}
?>