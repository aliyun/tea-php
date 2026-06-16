<?php

$requestUri = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

header('Content-Type: text/event-stream');
header('Connection: keep-alive');
header('Cache-Control: no-cache');

$count = 0;
if ($path === '/sse') {
    while ($count < 5) {
        echo "data: " . json_encode(['count' => $count]) . "\nevent: flow\nid: sse-test\nretry: 3000\n\n";
        $count++;
        ob_flush();
        flush();
        sleep(1);
    }
} elseif ($path === '/sse_with_no_spaces') {
    while ($count < 5) {
        echo "data:" . json_encode(['count' => $count]) . "\nevent:flow\nid:sse-test\nretry:3000\n\n";
        $count++;
        ob_flush();
        flush();
        sleep(1);
    }
} elseif ($path === '/sse_invalid_retry') {
    while ($count < 5) {
        echo "data:" . json_encode(['count' => $count]) . "\nevent:flow\nid:sse-test\nretry: abc\n\n";
        $count++;
        ob_flush();
        flush();
        sleep(1);
    }
} elseif ($path === '/sse_with_data_divided') {
    while ($count < 5) {
        if ($count === 1) {
            echo 'data:{"count":';
            $count++;
            continue;
        }
        if ($count === 2) {
            echo "$count,\"tag\":\"divided\"}\nevent:flow\nid:sse-test\nretry:3000\n\n";
            $count++;
            continue;
        }
        echo "data:" . json_encode(['count' => $count]) . "\nevent:flow\nid:sse-test\nretry:3000\n\n";
        $count++;
        ob_flush();
        flush();
        sleep(1);
    }
}