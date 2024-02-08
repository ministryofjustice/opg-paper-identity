<?php

// router.php
if ($_SERVER["REQUEST_URI"] === "/health-check") {
    http_response_code(200);
    echo "ok";
} elseif ($_SERVER["REQUEST_URI"] === "/") {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://api:8000/data");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $body = curl_exec($ch);
    curl_close($ch);

    http_response_code(200);
    header("Content-type: text/html");
    echo "<!doctype html><html><body>From server: " . $body;
} else {
    http_response_code(404);
    echo "Could not find " . $_SERVER["REQUEST_URI"];
}
