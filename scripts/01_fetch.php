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
$dataPath = $basePath . '/data/case';
if (!file_exists($dataPath)) {
    mkdir($dataPath, 0777, true);
}
$rawId = 6918;
while ($rawId > 0) {
    $rawFile = $rawPath . '/' . $rawId . '.html';
    if (!file_exists($rawFile)) {
        $crawler = $browser->request('GET', 'https://crc.sfaa.gov.tw/ChildYoungLaw/Detail/' . $rawId);
        file_put_contents($rawFile, $crawler->html());
    }
    $data = [
        'id' => $rawId,
    ];
    $rawId -= 1;

    $raw = file_get_contents($rawFile);
    if (false !== strpos($raw, '文章不再存在')) {
        continue;
    }
    $pos = strpos($raw, '<div id="main" class="main">');
    $posEnd = strpos($raw, '<div class="btnArea">', $pos);
    $main = substr($raw, $pos, $posEnd - $pos);
    $parts = explode('<div class="tr" role="row">', $main);
    if (!isset($parts[1])) {
        continue;
    }

    foreach ($parts as $part) {
        $cols = explode('</div>', $part);
        if (false === strpos($cols[0], 'columnheader')) {
            continue;
        }
        $header = strip_tags(trim($cols[0]));
        if (false !== strpos($cols[1], '<span class="detailTitle">')) {
            $contentParts = explode('<span class="col-12">', $cols[1]);
            if (!isset($contentParts[1])) {
                $contentParts = explode('<span class="col">', $cols[1]);
            }
            $content = [];
            foreach ($contentParts as $contentPart) {
                $conteltCols = explode('</span>', $contentPart);
                if (count($conteltCols) === 3) {
                    foreach ($conteltCols as $k => $v) {
                        $conteltCols[$k] = strip_tags(trim($v));
                    }
                    $content[$conteltCols[0]] = $conteltCols[1];
                }
            }
        } elseif (false !== strpos($cols[1], 'href="')) {
            $contentParts = explode('"', $cols[1]);
            $content = 'https://crc.sfaa.gov.tw' . $contentParts[3];
        } else {
            $content = trim(strip_tags($cols[1]));
        }
        $data[$header] = $content;
    }
    $dataFile = $dataPath . '/' . $data['id'] . '.json';
    print_r($data);
    file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
