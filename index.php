<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Funkcija za ISAPI zahtjeve
function makeIsapiRequest($url, $username, $password) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
        CURLOPT_USERPWD => "$username:$password",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true 
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) return ['error' => "CURL Error: $error"];
    if ($httpCode != 200) return ['error' => "HTTP Error: $httpCode (URL: $url)"]; 
    return ['data' => $response];
}


function parseXmlResponse($xmlString) {
   

    $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
    libxml_clear_errors(); 
    libxml_use_internal_errors(false); 

    if ($xml === false) {
        return false; 
    }


    $json = json_encode($xml);
    $array = json_decode($json, TRUE); 

    return $array;
}


function formatDeviceInfo($data) {
    if (!is_array($data)) return '<div class="alert alert-warning">Invalid device data</div>';


    $deviceName = isset($data['deviceName']) ? htmlspecialchars($data['deviceName']) : 'N/A';
    $deviceType = isset($data['deviceType']) ? htmlspecialchars($data['deviceType']) : 'N/A';
    $model = isset($data['model']) ? htmlspecialchars($data['model']) : 'N/A';
    $serialNumber = isset($data['serialNumber']) ? htmlspecialchars($data['serialNumber']) : 'N/A';
    $firmwareVersion = isset($data['firmwareVersion']) ? htmlspecialchars($data['firmwareVersion']) : 'N/A';
    $firmwareDate = isset($data['firmwareReleasedDate']) ? htmlspecialchars($data['firmwareReleasedDate']) : 'N/A';
    $macAddress = isset($data['macAddress']) ? htmlspecialchars($data['macAddress']) : 'N/A';
    $manufacturer = isset($data['manufacturer']) ? htmlspecialchars($data['manufacturer']) : 'N/A';

    return '
    <div class="device-info-grid">
        <div class="device-card">
            <div class="device-header">
                <i class="bi bi-cpu"></i>
                <h3>'.$deviceName.'</h3>
                <span class="badge device-type">'.$deviceType.'</span>
            </div>
            <div class="device-body">
                <div class="info-row">
                    <span class="info-label">Model:</span>
                    <span class="info-value">'.$model.'</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Serijski broj:</span>
                    <span class="info-value">'.$serialNumber.'</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Firmware:</span>
                    <span class="info-value">'.$firmwareVersion.' <small>('.$firmwareDate.')</small></span>
                </div>
                <div class="info-row">
                    <span class="info-label">MAC adresa:</span>
                    <span class="info-value">'.$macAddress.'</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Proizvođač:</span>
                    <span class="info-value">'.$manufacturer.'</span>
                </div>
            </div>
        </div>
    </div>';
}


function formatNetworkSettings($data) {
    if (!is_array($data)) return formatGenericData($data);

  
    $networkInfo = [
        'ipAddress' => 'N/A',
        'subnetMask' => 'N/A',
        'ipv6Address' => 'N/A',
        'gateway' => 'N/A',
        'primaryDNS' => 'N/A',
        'secondaryDNS' => 'N/A',
        'dnsEnabled' => 'false',
        'upnpEnabled' => 'false',
        'zeroconfEnabled' => 'false',
        'macAddress' => 'N/A'
    ];

 
    if (isset($data['NetworkInterface'])) {
        // Direct NetworkInterface structure
        $interface = $data['NetworkInterface'];
    } elseif (isset($data['NetworkInterfaceList']['NetworkInterface'])) {
        // NetworkInterfaceList -> NetworkInterface structure
        $interface = $data['NetworkInterfaceList']['NetworkInterface'];
    } else {
        return formatGenericData($data);
    }

    if (isset($interface['IPAddress'])) {
        $ip = $interface['IPAddress'];
        $networkInfo['ipAddress'] = $ip['ipAddress'] ?? 'N/A';
        $networkInfo['subnetMask'] = $ip['subnetMask'] ?? 'N/A';
        $networkInfo['ipv6Address'] = $ip['ipv6Address'] ?? 'N/A';
        $networkInfo['dnsEnabled'] = $ip['DNSEnable'] ?? 'false';

        if (isset($ip['DefaultGateway']) && !is_array($ip['DefaultGateway'])) {
            $networkInfo['gateway'] = $ip['DefaultGateway'];
        } elseif (isset($ip['DefaultGateway']['ipAddress'])) {
            $networkInfo['gateway'] = $ip['DefaultGateway']['ipAddress'];
        }

        if (isset($ip['PrimaryDNS']) && !is_array($ip['PrimaryDNS'])) {
            $networkInfo['primaryDNS'] = $ip['PrimaryDNS'];
        } elseif (isset($ip['PrimaryDNS']['ipAddress'])) {
            $networkInfo['primaryDNS'] = $ip['PrimaryDNS']['ipAddress'];
        }

        if (isset($ip['SecondaryDNS']) && !is_array($ip['SecondaryDNS'])) {
            $networkInfo['secondaryDNS'] = $ip['SecondaryDNS'];
        } elseif (isset($ip['SecondaryDNS']['ipAddress'])) {
            $networkInfo['secondaryDNS'] = $ip['SecondaryDNS']['ipAddress'];
        }
    }


    if (isset($interface['Discovery'])) {
        $discovery = $interface['Discovery'];
        $networkInfo['upnpEnabled'] = $discovery['UPnP']['enabled'] ?? 'false';
        $networkInfo['zeroconfEnabled'] = $discovery['Zeroconf']['enabled'] ?? 'false';
    }


    if (isset($interface['Link'])) {
        $link = $interface['Link'];
        $networkInfo['macAddress'] = $link['MACAddress'] ?? 'N/A';
    }

  
    return '
    <div class="network-settings">
        <div class="network-card">
            <div class="network-header">
                <i class="bi bi-ethernet"></i>
                <h4>Mrežne postavke</h4>
            </div>

            <div class="network-body">
                <div class="network-section">
                    <h5><i class="bi bi-ip"></i> IP Postavke</h5>
                    <div class="network-row">
                        <span class="network-label">IPv4 Adresa:</span>
                        <span class="network-value">'.htmlspecialchars($networkInfo['ipAddress']).'</span>
                    </div>
                    <div class="network-row">
                        <span class="network-label">Maska podmreže:</span>
                        <span class="network-value">'.htmlspecialchars($networkInfo['subnetMask']).'</span>
                    </div>
                    <div class="network-row">
                        <span class="network-label">Zadani pristupnik:</span>
                        <span class="network-value">'.htmlspecialchars($networkInfo['gateway']).'</span>
                    </div>
                    <div class="network-row">
                        <span class="network-label">IPv6 Adresa:</span>
                        <span class="network-value">'.htmlspecialchars($networkInfo['ipv6Address']).'</span>
                    </div>
                </div>

                <div class="network-section">
                    <h5><i class="bi bi-dns"></i> DNS Postavke</h5>
                    <div class="network-row">
                        <span class="network-label">Primarni DNS:</span>
                        <span class="network-value">'.htmlspecialchars($networkInfo['primaryDNS']).'</span>
                    </div>
                    <div class="network-row">
                        <span class="network-label">Sekundarni DNS:</span>
                        <span class="network-value">'.htmlspecialchars($networkInfo['secondaryDNS']).'</span>
                    </div>
                    <div class="network-row">
                        <span class="network-label">DNS omogućen:</span>
                        <span class="network-value">
                            <span class="badge '.($networkInfo['dnsEnabled'] === 'true' ? 'bg-success' : 'bg-secondary').'">
                                '.htmlspecialchars($networkInfo['dnsEnabled']).'
                            </span>
                        </span>
                    </div>
                </div>

                <div class="network-section">
                    <h5><i class="bi bi-wifi"></i> Dodatne postavke</h5>
                    <div class="network-row">
                        <span class="network-label">MAC Adresa:</span>
                        <span class="network-value">'.htmlspecialchars($networkInfo['macAddress']).'</span>
                    </div>
                    <div class="network-row">
                        <span class="network-label">UPnP:</span>
                        <span class="network-value">
                            <span class="badge '.($networkInfo['upnpEnabled'] === 'true' ? 'bg-success' : 'bg-secondary').'">
                                '.htmlspecialchars($networkInfo['upnpEnabled']).'
                            </span>
                        </span>
                    </div>
                    <div class="network-row">
                        <span class="network-label">Zeroconf:</span>
                        <span class="network-value">
                            <span class="badge '.($networkInfo['zeroconfEnabled'] === 'true' ? 'bg-success' : 'bg-secondary').'">
                                '.htmlspecialchars($networkInfo['zeroconfEnabled']).'
                            </span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>';
}


function formatCameraCard($ip, $port, $username, $password, $channelId, $channelName = "Live pregled") {
 
    $proxyUrl = "camera_proxy.php?" . http_build_query([
        'ip' => $ip,
        'port' => $port,
        'user' => $username,
        'pass' => $password,
        'channel' => $channelId
    ]);

    
    $fullResUrl = "camera_proxy.php?" . http_build_query([
        'ip' => $ip,
        'port' => $port,
        'user' => $username,
        'pass' => $password,
        'channel' => $channelId,
        '_t' => time() 
    ]);

    $displayChannelName = htmlspecialchars($channelName);
    if ($channelName === "Live pregled" && $channelId) {
        $displayChannelName = "Kamera " . htmlspecialchars($channelId);
    }

    return '
    <div class="camera-card">
        <div class="camera-header">
            <i class="bi bi-camera-fill"></i>
            <h3>'.$displayChannelName.'</h3>
        </div>
        <div class="camera-body">
            <div class="camera-view">
                <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal"
                   data-image-url="' . $fullResUrl . '"
                   data-camera-name="' . $displayChannelName . '">
                    <img id="camera-image-' . $channelId . '" src="'.$proxyUrl.'" class="camera-image" alt="Live preview"
                         onerror="this.onerror=null;this.src=\'data:image/svg+xml;charset=UTF-8,<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"100%\" height=\"100%\" viewBox=\"0 0 800 450\"><rect width=\"100%\" height=\"100%\" fill=\"%23f8f9fa\"/><text x=\"50%\" y=\"50%\" font-family=\"sans-serif\" font-size=\"16\" fill=\"%236c757d\" text-anchor=\"middle\" dominant-baseline=\"middle\">Nije moguće učitati sliku</text></svg>\';">
                </a>
            </div>
            <div class="camera-controls mt-3">
                <a href="' . $fullResUrl . '&download=true" class="btn btn-primary btn-sm" download="snapshot-channel-'.$channelId.'-'.time().'.jpg">
                    <i class="bi bi-download"></i> Preuzmi sliku
                </a>
                <button class="btn btn-secondary btn-sm" onclick="refreshCameraImage(this)" data-channel-id="'.$channelId.'" data-ip="'.$ip.'" data-port="'.$port.'" data-user="'.$username.'" data-pass="'.$password.'">
                    <i class="bi bi-arrow-clockwise"></i> Osvježi
                </button>
            </div>
        </div>
    </div>';
}


function formatGenericData($data, $level = 0) {
    if ($level > 3) return '';

    $html = '<div class="generic-data level-'.$level.'">';

    foreach ($data as $key => $value) {
        $safeKey = htmlspecialchars($key);

        if (is_array($value) || is_object($value)) {
            $html .= '<div class="data-group">
                        <div class="data-header" data-bs-toggle="collapse" href="#collapse-'.md5($key).'">
                            <span class="data-key">'.$safeKey.'</span>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div class="collapse" id="collapse-'.md5($key).'">
                            <div class="data-content">
                                '.formatGenericData($value, $level + 1).'
                            </div>
                        </div>
                    </div>';
        } else {
            $safeValue = is_string($value) || is_numeric($value) ? htmlspecialchars($value) : '[Complex Data]';
            $html .= '<div class="data-item">
                        <span class="data-key">'.$safeKey.':</span>
                        <span class="data-value">'.$safeValue.'</span>
                    </div>';
        }
    }

    $html .= '</div>';
    return $html;
}


$error = null;
$deviceInfo = null;
$endpointData = [];
$cameraChannels = []; 
$errorMessages = []; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_POST['ip'] ?? '';
    $port = $_POST['port'] ?? '81';
    $username = $_POST['username'] ?? 'admin';
    $password = $_POST['password'] ?? 'Admin1234';

    if (empty($ip)) {
        $error = "IP adresa je obavezna!";
    } else {
        $protocol = ($port == '443') ? 'https' : 'http';
        $baseUrl = "$protocol://$ip:$port";

        $testUrl = $baseUrl . '/ISAPI/System/deviceInfo';
        $initialResponse = makeIsapiRequest($testUrl, $username, $password);

        if (isset($initialResponse['data']) && strpos($initialResponse['data'], '<?xml') === 0) {
            $deviceInfo = parseXmlResponse($initialResponse['data']);
            if ($deviceInfo !== false) {
                $endpoints = [
                    'System Info' => '/ISAPI/System/deviceInfo',
                    'Network Settings' => '/ISAPI/System/Network/interfaces',
                    'Channel Configuration' => '/ISAPI/Streaming/channels',
                    'Recording Status' => '/ISAPI/ContentMgmt/record/tracks',
                    'Storage Devices' => '/ISAPI/ContentMgmt/storage',
                    'Event Configuration' => '/ISAPI/Event/triggers',
                    'User Accounts' => '/ISAPI/Security/users',
                    'Current Time' => '/ISAPI/System/time',
                 //   'PTZ Status' => '/ISAPI/PTZCtrl/channels/1/status',
                //    'Alarm Status' => '/ISAPI/Event/notification/alertStream',
                    'Version Information' => '/ISAPI/System/capabilities',
                ];

                foreach ($endpoints as $name => $endpoint) {
                    $url = $baseUrl . $endpoint;
                    $response = makeIsapiRequest($url, $username, $password);

                    if (isset($response['data']) && strpos($response['data'], '<?xml') === 0) {
                        $parsed = parseXmlResponse($response['data']);
                        $endpointData[$name] = [
                            'type' => 'success',
                            'data' => $parsed !== false ? $parsed : $response['data'],
                            'raw' => $response['data']
                        ];

                        
                        if ($name === 'Channel Configuration') {
                            if (isset($parsed['StreamingChannel'])) {
                                $allRawChannels = [];
                                if (isset($parsed['StreamingChannel']['id'])) {
                                    $allRawChannels = [$parsed['StreamingChannel']]; 
                                } else {
                                    $allRawChannels = $parsed['StreamingChannel']; 
                                }

                                $groupedChannels = [];
                                foreach ($allRawChannels as $channel) {
                                   
                                    if (isset($channel['Video']['videoInputChannelID'])) {
                                        $inputChannelID = $channel['Video']['videoInputChannelID'];
                                        $groupedChannels[$inputChannelID][] = $channel;
                                    }
                                }

                                $cameraChannels = []; 
                                foreach ($groupedChannels as $inputID => $channelsForInput) {
                                    $mainStreamFoundById = false;
                                    $mainStream = null;

                                   
                                    foreach ($channelsForInput as $channel) {
                                        if (isset($channel['id']) && is_string($channel['id']) && substr($channel['id'], -2) === '01') {
                                            $mainStream = $channel;
                                            $mainStreamFoundById = true;
                                            break; 
                                        }
                                    }

                                    if ($mainStreamFoundById) {
                                        $cameraChannels[] = $mainStream; 
                                    } else {

                                        foreach ($channelsForInput as $channel) {
                                            $cameraChannels[] = $channel;
                                        }
                                    }
                                }
                            } else {
                                $errorMessages[] = "Nema dostupnih streaming kanala.";
                            }
                        }
                    } else {
                        $endpointData[$name] = [
                            'type' => 'error',
                            'message' => $response['error'] ?? 'Unknown error'
                        ];
                    }
                }
            } else {
                $error = "Neuspješno parsiranje XML odgovora za Device Info.";
            }
        } else {
            $error = "Neuspješna konekcija na uređaj: " . ($initialResponse['error'] ?? 'Nepoznata greška. Provjerite IP, port i kredencijale.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hikvision Device INFO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
	<link href="stil.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>
    /* CSS Varijable za Boje (Lako prebacivanje između svijetlih i tamnih tema) */
    :root {
        /* Svijetli mod */
        --bg-color: #f8f9fa; /* Vrlo svijetlo siva pozadina */
        --card-bg: #ffffff; /* Bijela pozadina kartica */
        --text-color: #343a40; /* Tamno sivi tekst */
        --heading-color: #212529; /* Još tamniji tekst za naslove */
        --border-color: #e9ecef; /* Svijetli obrub */
        --primary-color: #007bff; /* Bootstrap plava za akcent */
        --primary-dark-color: #0056b3; /* Tamnija verzija primarne */
        --secondary-color: #6c757d; /* Sekundarna siva */
        --shadow-color: rgba(0, 0, 0, 0.08); /* Suptilna sjena */
        --input-border-focus: #80bdff; /* Fokus obrub za inpute */
        --input-shadow-focus: rgba(0, 123, 255, 0.25); /* Fokus sjena za inpute */
    }

    body.dark-mode {
        /* Tamni mod */
        --bg-color: #2c3e50; /* Tamno plava/siva pozadina */
        --card-bg: #34495e; /* Tamnija plava/siva za kartice */
        --text-color: #ecf0f1; /* Svijetlo sivi tekst */
        --heading-color: #f8f9fa; /* Bijeli tekst za naslove */
        --border-color: #455a64; /* Tamniji obrub */
        --primary-color: #6da2df; /* Svjetlija plava za akcent */
        --primary-dark-color: #5d90cc; /* Tamnija verzija primarne za tamni mod */
        --secondary-color: #adb5bd; /* Svjetlija sekundarna siva */
        --shadow-color: rgba(0, 0, 0, 0.25); /* Jača sjena za tamni mod */
        --input-border-focus: #9bc5ed;
        --input-shadow-focus: rgba(109, 162, 223, 0.3);
    }

    /* Osnovni Stilovi */
    body {
        font-family: 'Roboto', sans-serif;
        background-color: var(--bg-color);
        color: var(--text-color);
        transition: background-color 0.3s ease, color 0.3s ease;
        padding-top: 20px;
        padding-bottom: 20px;
    }

    h1, h2, h3, h4, h5, h6 {
        font-family: 'Montserrat', sans-serif;
        color: var(--heading-color);
    }

    /* Kontejner i Navigacija */
    .container-fluid {
        max-width: 1400px; /* Ograničite širinu za bolju čitljivost na velikim ekranima */
    }

    .nav-tabs {
        border-bottom: 2px solid var(--border-color);
        margin-bottom: 20px;
    }

    .nav-tabs .nav-link {
        color: var(--text-color);
        border: none;
        border-bottom: 3px solid transparent;
        padding: 10px 20px;
        transition: all 0.3s ease;
        font-weight: 500;
    }

    .nav-tabs .nav-link:hover {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }

    .nav-tabs .nav-link.active {
        color: var(--primary-color);
        background-color: transparent;
        border-bottom-color: var(--primary-color);
        font-weight: 600;
    }

    /* Forma za pretragu i inputi */
    .search-form-section {
        background-color: var(--card-bg);
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 8px 16px var(--shadow-color);
        margin-bottom: 30px;
    }

    .form-control {
        border-radius: 8px;
        border: 1px solid var(--border-color);
        padding: 12px 15px;
        background-color: var(--card-bg);
        color: var(--text-color);
        transition: border-color 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .form-control::placeholder {
        color: var(--secondary-color);
        opacity: 0.7;
    }

    .form-control:focus {
        border-color: var(--input-border-focus);
        box-shadow: 0 0 0 0.2rem var(--input-shadow-focus);
        background-color: var(--card-bg); /* Osigurajte da se pozadina ne mijenja */
        color: var(--text-color); /* Osigurajte da se tekst ne mijenja */
    }

    /* Gumbi */
    .btn {
        border-radius: 8px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px; /* Razmak između ikone i teksta */
    }

    .btn-primary {
        background-color: var(--primary-color);
        border-color: var(--primary-color);
    }

    .btn-primary:hover {
        background-color: var(--primary-dark-color);
        border-color: var(--primary-dark-color);
        transform: translateY(-2px); /* Blago podizanje na hover */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .btn-outline-primary {
        color: var(--primary-color);
        border-color: var(--primary-color);
        background-color: transparent;
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color);
        color: #fff;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    /* Search Gumb Loading Animacija */
    #searchButton {
        position: relative;
        overflow: hidden; /* Skriva dijelove spinnera koji izlaze */
    }

    #searchButton #loadingSpinner {
        display: none;
        margin: 0; /* Ukloni marginu koju sam prethodno sugerirao u JS-u */
    }

    #searchButton.loading #searchIcon,
    #searchButton.loading #searchText {
        display: none;
    }

    #searchButton.loading #loadingSpinner {
        display: inline-block !important;
        margin-right: 5px; /* Razmak ako želite spinner prije teksta 'Učitavanje...' (ako bi ga bilo) */
    }

    /* Stilovi za kamere i kartice */
    .camera-card {
        background-color: var(--card-bg);
        border: none;
        border-radius: 12px;
        box-shadow: 0 8px 16px var(--shadow-color);
        overflow: hidden; /* Osigurava da zaobljeni rubovi rade s slikom */
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        margin-bottom: 25px; /* Dodajte razmak između kartica */
    }

    .camera-card:hover {
        transform: translateY(-5px); /* Blago podizanje na hover */
        box-shadow: 0 12px 24px var(--shadow-color); /* Jača sjena na hover */
    }

    .camera-card .camera-image { /* Promijenjeno iz .card-img-top */
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        object-fit: cover;
        width: 100%;
        height: 250px; /* Fiksna visina za slike */
        cursor: pointer; /* Dodajte kursor za klik */
    }

    .camera-card .card-body {
        padding: 20px;
    }

    .camera-card .card-title {
        font-weight: 600;
        margin-bottom: 10px;
        color: var(--heading-color);
    }

    .camera-info p {
        margin-bottom: 5px;
        font-size: 0.95em;
    }

    .camera-actions {
        padding-top: 15px;
        border-top: 1px solid var(--border-color);
        margin-top: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
    }
    
    .camera-actions .btn {
        flex-grow: 1; /* Omogućuje gumbima da se šire */
    }

    /* Ikone */
    .bi {
        margin-right: 5px;
    }

    /* Dark Mode Toggle (opcionalno, dodajte HTML gumb i JS za ovo) */
    .dark-mode-toggle {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background-color: var(--card-bg);
        color: var(--text-color);
        border: 1px solid var(--border-color);
        border-radius: 50%;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        cursor: pointer;
        box-shadow: 0 4px 10px var(--shadow-color);
        transition: all 0.3s ease;
        z-index: 1000;
    }

    .dark-mode-toggle:hover {
        background-color: var(--primary-color);
        color: #fff;
        transform: translateY(-3px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
    }

    /* Stilovi za greške (ako ih koristite) */
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-color: #f5c6cb;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    body.dark-mode .alert-danger {
        background-color: #6a0000;
        color: #fdd;
        border-color: #8b0000;
    }


    /* Responzivnost */
    @media (max-width: 768px) {
        .search-form-section {
            padding: 20px;
        }
        .btn {
            width: 100%; /* Gumbi na mobitelu neka budu pune širine */
        }
        .camera-card .camera-image { /* Promijenjeno iz .card-img-top */
            height: 200px; /* Manja visina slike na mobitelu */
        }
        .camera-actions {
            flex-direction: column; /* Gumbi za akcije ispod slike neka idu jedan ispod drugog */
        }
        .camera-actions .btn {
            margin-bottom: 10px;
        }
    }
</style>
</head>
<body>
    <div class="app-container">
        <div class="app-header">
            <h1><i class="bi bi-camera-video"></i> Hikvision Device INFO</h1>
            <p class="mb-0">Informacije sa Hikvision uređaja</p>
        </div>

        <form method="post" class="mb-4">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="ip" class="form-label">IP Adresa</label>
                    <input type="text" class="form-control" id="ip" name="ip" value="<?= htmlspecialchars($_POST['ip'] ?? '') ?>" required>
                </div>
                <div class="col-md-2">
                    <label for="port" class="form-label">Port</slabel>
                    <input type="text" class="form-control" id="port" name="port" value="<?= htmlspecialchars($_POST['port'] ?? '81') ?>">
                </div>
                <div class="col-md-3">
                    <label for="username" class="form-label">Korisničko ime</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>">
                </div>
                <div class="col-md-3">
                    <label for="password" class="form-label">Lozinka</label>
                    <input type="password" class="form-control" id="password" name="password" value="<?= htmlspecialchars($_POST['password'] ?? 'Admin1234') ?>">
                </div>
<div class="col-md-1 d-flex align-items-end">
    <button type="submit" class="btn btn-primary w-100" id="searchButton">
        <i class="bi bi-search" id="searchIcon"></i>
        <span id="searchText">Pretraži</span>
        <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true" id="loadingSpinner" style="display: none;"></span>
    </button>
</div>
            </div>
        </form>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($deviceInfo)): ?>
            <section class="mb-5">
                <h2 class="mb-3"><i class="bi bi-info-circle"></i> Informacije o uređaju</h2>
                <?= formatDeviceInfo($deviceInfo) ?>
            </section>

            <section class="camera-preview-section">
                <h2 class="mb-3"><i class="bi bi-camera-video"></i> Pregled kamera</h2>
                <div class="camera-preview-container">
                    <?php
                    if (!empty($cameraChannels)) {
                        foreach ($cameraChannels as $channel) {
                            $channelId = $channel['id'] ?? 'unknown';
                            $channelName = $channel['channelName'] ?? "Kamera " . $channelId;
                            echo formatCameraCard($ip, $port, $username, $password, $channelId, $channelName);
                        }
                    } else {
                        echo '<div class="alert alert-info">Nije pronađena konfiguracija kanala ili nema dostupnih kamera.</div>';
                    }
                    ?>
                </div>
            </section>

            <section class="endpoints-container">
                <h2 class="mb-4"><i class="bi bi-list-ul"></i> ISAPI Endpointi</h2>

                <div class="accordion" id="endpointsAccordion">
                    <?php foreach ($endpointData as $name => $data): ?>
                        <div class="endpoint-card">
                            <div class="endpoint-header" data-bs-toggle="collapse" data-bs-target="#endpoint-<?= md5($name) ?>">
                                <h3 class="endpoint-title"><?= htmlspecialchars($name) ?></h3>
                                <span class="badge <?= $data['type'] === 'error' ? 'bg-danger' : 'bg-success' ?> endpoint-badge">
                                    <?= $data['type'] === 'error' ? 'Greška' : 'Uspješno' ?>
                                </span>
                            </div>

                            <div id="endpoint-<?= md5($name) ?>" class="collapse" data-bs-parent="#endpointsAccordion">
                                <div class="endpoint-content">
                                    <?php if ($data['type'] === 'error'): ?>
                                        <div class="alert alert-warning">
                                            <?= htmlspecialchars($data['message']) ?>
                                        </div>
                                    <?php else: ?>
                                        <ul class="nav nav-tabs mb-3" id="endpoint-tab-<?= md5($name) ?>" role="tablist">
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link active" id="view-tab-<?= md5($name) ?>" data-bs-toggle="tab" data-bs-target="#view-<?= md5($name) ?>" type="button" role="tab">
                                                    Pregled
                                                </button>
                                            </li>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link" id="raw-tab-<?= md5($name) ?>" data-bs-toggle="tab" data-bs-target="#raw-<?= md5($name) ?>" type="button" role="tab">
                                                    XML
                                                </button>
                                            </li>
                                        </ul>

                                        <div class="tab-content">
                                            <div class="tab-pane fade show active" id="view-<?= md5($name) ?>" role="tabpanel">
                                                <?php
                                                if ($name === 'Network Settings') {
                                                    echo formatNetworkSettings($data['data']);
                                                } else {
                                                    echo formatGenericData($data['data']);
                                                }
                                                ?>
                                            </div>
                                            <div class="tab-pane fade" id="raw-<?= md5($name) ?>" role="tabpanel">
                                                <div class="xml-viewer"><?= htmlspecialchars($data['raw']) ?></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php foreach ($errorMessages as $msg): ?>
                    <div class="alert alert-warning mt-3"><?= htmlspecialchars($msg) ?></div>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Slika kamere</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zatvori"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" id="fullSizeImage" class="img-fluid" alt="Slika kamere">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Zatvori</button>
                    <a href="#" id="downloadImageBtn" class="btn btn-primary" download>
                        <i class="bi bi-download"></i> Preuzmi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Otvori prvi endpoint automatski
            const firstEndpoint = document.querySelector('.endpoint-header');
            if (firstEndpoint) {
                const collapseTarget = firstEndpoint.getAttribute('data-bs-target');
                const collapseElement = document.querySelector(collapseTarget);
                new bootstrap.Collapse(collapseElement, {toggle: true});
            }

            // Poboljšanje korisničkog iskustva s tabovima
            const tabLinks = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    const target = this.getAttribute('data-bs-target');
                    document.querySelector(target).scrollIntoView({behavior: 'smooth'});
                });
            });

            // Dark Mode Toggle logika
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (darkModeToggle) {
                const currentTheme = localStorage.getItem('theme') ? localStorage.getItem('theme') : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

                if (currentTheme === 'dark') {
                    document.body.classList.add('dark-mode');
                    darkModeToggle.innerHTML = '<i class="bi bi-sun-fill"></i>';
                } else {
                    darkModeToggle.innerHTML = '<i class="bi bi-moon-fill"></i>';
                }

                darkModeToggle.addEventListener('click', function() {
                    document.body.classList.toggle('dark-mode');
                    let theme = 'light';
                    if (document.body.classList.contains('dark-mode')) {
                        theme = 'dark';
                        this.innerHTML = '<i class="bi bi-sun-fill"></i>';
                    } else {
                        this.innerHTML = '<i class="bi bi-moon-fill"></i>';
                    }
                    localStorage.setItem('theme', theme);
                });
            }

            // Search Button Loading Animation
            const searchForm = document.querySelector('form');
            const searchButton = document.getElementById('searchButton');
            const searchIcon = document.getElementById('searchIcon');
            const searchText = document.getElementById('searchText');
            const loadingSpinner = document.getElementById('loadingSpinner');

            if (searchForm && searchButton) {
                searchForm.addEventListener('submit', function() {
                    searchButton.disabled = true;
                    searchButton.classList.add('loading');
                });
            }

            // Funkcija za osvježavanje slike kamere
            function refreshCameraImage(button) {
                const channelId = button.getAttribute('data-channel-id');
                const ip = button.getAttribute('data-ip');
                const port = button.getAttribute('data-port');
                const user = button.getAttribute('data-user');
                const pass = button.getAttribute('data-pass');

                const cameraImage = document.getElementById('camera-image-' + channelId);
                if (cameraImage) {
                    const newSrc = `camera_proxy.php?ip=${encodeURIComponent(ip)}&port=${encodeURIComponent(port)}&user=${encodeURIComponent(user)}&pass=${encodeURIComponent(pass)}&channel=${encodeURIComponent(channelId)}&_t=${new Date().getTime()}`;

                    cameraImage.src = newSrc;

                    button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Osvježavanje...';
                    button.disabled = true;

                    setTimeout(() => {
                        button.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Osvježi';
                        button.disabled = false;
                    }, 1000);
                }
            }
            // Učinite refreshCameraImage globalno dostupnom ako nije
            window.refreshCameraImage = refreshCameraImage;

            // Logika za Image Modal
            const imageModal = document.getElementById('imageModal');
            const fullSizeImage = document.getElementById('fullSizeImage');
            const downloadImageBtn = document.getElementById('downloadImageBtn');

            if (imageModal && fullSizeImage && downloadImageBtn) {
                imageModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget; 
                    const imageUrl = button.getAttribute('data-image-url');
                    const cameraName = button.getAttribute('data-camera-name'); // Dohvatite ime kamere iz custom atributa

                    fullSizeImage.src = imageUrl;
                    downloadImageBtn.href = imageUrl + '&download=true'; // Dodajte download=true za preuzimanje

                    const modalTitle = imageModal.querySelector('.modal-title');
                    if (cameraName && modalTitle) {
                        modalTitle.textContent = 'Slika: ' + cameraName;
                    } else if (modalTitle) {
                        modalTitle.textContent = 'Slika kamere';
                    }
                });

                imageModal.addEventListener('hidden.bs.modal', function () {
                    fullSizeImage.src = '';
                    downloadImageBtn.href = '#';
                });
            }
        });
    </script>
	<div class="dark-mode-toggle" id="darkModeToggle">
    <i class="bi bi-moon-fill"></i> </div>
</body>
</html>