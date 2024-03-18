<?php
$basePath = dirname(__DIR__);
$dataPath = $basePath . '/data/case';

$filePool = [];
foreach (glob($dataPath . '/*.json') as $dataFile) {
    $data = json_decode(file_get_contents($dataFile), true);
    $target = "";
    if (is_array($data['裁罰對象'])) {
        foreach ($data['裁罰對象'] as $k => $v) {
            $target .= "{$k}: {$v}\n";
        }
    } else {
        print_r($data);
        $target .= "{$data['裁罰對象']}\n";
    }

    $data['裁罰對象'] = trim($target);
    if (!isset($filePool[$data['縣市名稱']])) {
        $cityFile = $basePath . '/data/' . $data['縣市名稱'] . '.csv';
        $filePool[$data['縣市名稱']] = fopen($cityFile, 'w');
        fputcsv($filePool[$data['縣市名稱']], array_keys($data));
    }
    fputcsv($filePool[$data['縣市名稱']], $data);
}
