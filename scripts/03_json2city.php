<?php
$basePath = dirname(__DIR__);
$dataPath = $basePath . '/data/case';

$header = array(
    0 => 'id',
    1 => '裁罰日期',
    2 => '縣市名稱',
    3 => '裁罰對象',
    4 => '裁罰依據',
    5 => '違法條文',
    6 => '事實摘要',
    7 => '公告檔案',
);
$filePool = [];
foreach (glob($dataPath . '/*.json') as $dataFile) {
    $data = json_decode(file_get_contents($dataFile), true);
    $target = "";
    if (is_array($data['裁罰對象'])) {
        foreach ($data['裁罰對象'] as $k => $v) {
            $target .= "{$k}: {$v}\n";
        }
    } else {
        $target .= "{$data['裁罰對象']}\n";
    }

    $data['裁罰對象'] = trim($target);
    if (!isset($filePool[$data['縣市名稱']])) {
        $cityFile = $basePath . '/data/' . $data['縣市名稱'] . '.csv';
        $filePool[$data['縣市名稱']] = fopen($cityFile, 'w');
        fputcsv($filePool[$data['縣市名稱']], $header);
    }
    $line = [];
    foreach ($header as $k) {
        $line[] = isset($data[$k]) ? $data[$k] : '';
    }
    fputcsv($filePool[$data['縣市名稱']], $line);
}
