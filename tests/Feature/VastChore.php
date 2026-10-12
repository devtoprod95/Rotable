<?php

$domain = "https://adserver.vrixon.com";
$domain = "https://devadserver.vrixon.com";

$endpoint                = $domain . "/adAmagi.vrix";
$contentCustom1ParamList = [
    'animated_overlay' => 'overlay',
    'aston_band'       => 'aston',
    'l_band'           => 'lband',
    'pip'              => 'pip',
    'video'            => '',
];

$query = [
    'invenid'       => 'JULWE',
    'ip'            => '87.121.60.28',
    'content_genre' => 'Entertainment',
    'content_custom_1_param' => $contentCustom1ParamList['pip'],
];

$url = $endpoint . '?' . http_build_query($query);

// 1. 순수 PHP cURL 통신
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$isValidXml = false;

// 2. XML 반환 여부 및 광고(Ad) 존재 여부 검사
if ($response !== false && $httpCode === 200 && !empty(trim($response))) {
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    if ($dom->loadXML($response)) {
        // XML 형식이 맞더라도 광고(<Ad>) 태그가 존재하지 않으면(예: 에러 태그만 반환된 경우) fallback 처리
        if ($dom->getElementsByTagName('Ad')->length > 0) {
            $isValidXml = true;
        }
    }
    libxml_clear_errors();
}

if (!$isValidXml) {
    $fallbackFile = __DIR__ . '/pip_linear_bak.html';
    if (file_exists($fallbackFile)) {
        include $fallbackFile;
    } else {
        echo "Error: Fallback HTML file not found.";
    }
} else {
    // XML이 정상 반환된 경우 출력
    header('Content-Type: application/xml; charset=utf-8');
    echo $response;
}
