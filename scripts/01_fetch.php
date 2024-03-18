<?php
$basePath = dirname(__DIR__);
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

$browser = new HttpBrowser(HttpClient::create());

$rawPath = $basePath . '/raw/case';
if (!file_exists($rawPath)) {
    mkdir($rawPath, 0777, true);
}
$rawId = 3870;
while ($rawId > 0) {
    $rawFile = $rawPath . '/' . $rawId . '.html';
    if (!file_exists($rawFile)) {
        $crawler = $browser->request('GET', 'https://crc.sfaa.gov.tw/ChildYoungLaw/Detail/' . $rawId);
        file_put_contents($rawFile, $crawler->html());
    }
    $rawId -= 1;
}
