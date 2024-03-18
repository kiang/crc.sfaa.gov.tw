<?php
$basePath = dirname(__DIR__);
require __DIR__ . '/vendor/autoload.php';

$rawPath = $basePath . '/raw/case';
$dataPath = $basePath . '/data/case';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

$browser = new HttpBrowser(HttpClient::create());

$crawler = $browser->request('GET', 'https://crc.sfaa.gov.tw/ChildYoungLaw/Sanction?page=1&pagesize=30&name=&target=&city=0&startDate=&endDate=');
$page = $crawler->html();
$pos = strpos($page, '/ChildYoungLaw/Detail/');
while (false !== $pos) {
    $posEnd = strpos($page, '"', $pos);
    $url = substr($page, $pos, $posEnd - $pos);
    $parts = explode('/', $url);
    $rawId = array_pop($parts);
    $rawId = intval($rawId);
    $data = [
        'id' => $rawId,
    ];
    $rawFile = $rawPath . '/' . $rawId . '.html';
    if (!file_exists($rawFile)) {
        $crawler = $browser->request('GET', 'https://crc.sfaa.gov.tw/ChildYoungLaw/Detail/' . $rawId);
        file_put_contents($rawFile, $crawler->html());
    }
    $pos = strpos($page, '/ChildYoungLaw/Detail/', $posEnd);

    $raw = file_get_contents($rawFile);
    if (false !== strpos($raw, '文章不再存在')) {
        continue;
    }
    $rawPos = strpos($raw, '<div id="main" class="main">');
    $rawPosEnd = strpos($raw, '<div class="btnArea">', $rawPos);
    $main = substr($raw, $rawPos, $rawPosEnd - $rawPos);
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
    file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
