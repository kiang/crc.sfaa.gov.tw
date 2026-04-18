<?php
$basePath = dirname(__DIR__);
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;

$rawPath = $basePath . '/raw/case';
$dataPath = $basePath . '/data/case';
$baseUrl = 'https://crc.sfaa.gov.tw';
$listUrl = $baseUrl . '/ChildYoungLaw/Sanction?page=1&pagesize=30&name=&target=all&city=0&startDate=&endDate=&dosearch=true';

$browser = new HttpBrowser(HttpClient::create());
$crawler = $browser->request('GET', $listUrl);

$detailLinks = $crawler->filter('a[href*="/ChildYoungLaw/Detail/"]');
$detailLinks->each(function ($node) use ($browser, $baseUrl, $rawPath, $dataPath) {
    $href = $node->attr('href');
    $rawId = intval(basename($href));
    if ($rawId === 0) {
        return;
    }

    $rawFile = $rawPath . '/' . $rawId . '.html';
    if (!file_exists($rawFile)) {
        $crawler = $browser->request('GET', $baseUrl . '/ChildYoungLaw/Detail/' . $rawId);
        file_put_contents($rawFile, $crawler->html());
    }

    $raw = file_get_contents($rawFile);
    if (strpos($raw, '文章不再存在') !== false) {
        return;
    }

    $data = parseDetailPage($raw, $rawId, $baseUrl);
    $dataFile = $dataPath . '/' . $rawId . '.json';
    file_put_contents($dataFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
});

function parseDetailPage(string $raw, int $rawId, string $baseUrl): array
{
    $data = ['id' => $rawId];

    $rawPos = strpos($raw, '<div id="main" class="main">');
    $rawPosEnd = strpos($raw, '<div class="btnArea">', $rawPos);
    $main = substr($raw, $rawPos, $rawPosEnd - $rawPos);

    $rows = explode('<div class="tr" role="row">', $main);
    array_shift($rows);

    foreach ($rows as $row) {
        $cols = explode('</div>', $row);
        if (strpos($cols[0], 'columnheader') === false) {
            continue;
        }

        $header = strip_tags(trim($cols[0]));
        $data[$header] = parseColumnContent($cols[1], $baseUrl);
    }

    return $data;
}

function parseColumnContent(string $html, string $baseUrl)
{
    if (strpos($html, '<span class="detailTitle">') !== false) {
        return parseDetailSpans($html);
    }

    if (strpos($html, 'href="') !== false) {
        $parts = explode('"', $html);
        return $baseUrl . $parts[3];
    }

    return trim(strip_tags($html));
}

function parseDetailSpans(string $html): array
{
    $contentParts = explode('<span class="col-12">', $html);
    if (!isset($contentParts[1])) {
        $contentParts = explode('<span class="col">', $html);
    }

    $content = [];
    foreach ($contentParts as $contentPart) {
        $spans = explode('</span>', $contentPart);
        if (count($spans) === 3) {
            $key = strip_tags(trim($spans[0]));
            $value = strip_tags(trim($spans[1]));
            $content[$key] = $value;
        }
    }

    return $content;
}
