<?php

require __DIR__ . '/engine/start.php';

$GLOBALS['_ELGG_MICROTIMES']['build page'][':begin'] = microtime();

$body = elgg_view_layout('content', array());
echo elgg_view_page('Title', $body);

//echo "<pre>";
//$profiler = new \Elgg\Profiler();
//
//$tree = $profiler->buildTree();
//$tree = $profiler->formatTree($tree);
//
//echo json_encode($tree, JSON_PRETTY_PRINT);
//
//$list = [];
//$profiler->flattenTree($list, $tree);
//
//echo json_encode($list, JSON_PRETTY_PRINT);
