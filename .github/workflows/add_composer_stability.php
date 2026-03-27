<?php

declare(strict_types=1);

/**
 * 在 composer.json 中添加 minimum-stability 和 prefer-stable 属性
 */

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__,2));



$jsonFile = BASE_PATH.'/composer.json';
// 读取 JSON 文件
$jsonContent = file_get_contents($jsonFile);
if ($jsonContent === false) {
    echo "错误：无法读取文件 '{$jsonFile}'\n";
    exit(1);
}

// 解码 JSON
$data = json_decode($jsonContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "错误：JSON 格式不正确 - " . json_last_error_msg() . "\n";
    exit(1);
}

// 添加属性
$data['minimum-stability'] = 'dev';
$data['prefer-stable'] = true;

// 编码为 JSON (保持格式)
$newJsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// 写入文件
if (file_put_contents($jsonFile, $newJsonContent) === false) {
    echo "错误：无法写入文件 '{$jsonFile}'\n";
    exit(1);
}

echo "已成功添加属性到 {$jsonFile}:\n";
echo "  - \"minimum-stability\": \"dev\"\n";
echo "  - \"prefer-stable\": true\n";
