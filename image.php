<?php
header("Access-Control-Allow-Origin: *");
$file = basename($_GET['file'] ?? '');
$folder = $_GET['folder'] ?? 'thumbnail';
$path = __DIR__ . '/' . $folder . '/' . $file;
if (!$file || !file_exists($path)) { http_response_code(404); exit; }
header("Content-Type: " . mime_content_type($path));
readfile($path);
?>