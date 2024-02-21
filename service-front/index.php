<?php

// router.php
if ($_SERVER["REQUEST_URI"] === "/health-check") {
    http_response_code(200);
    echo "ok";
} elseif ($_SERVER["REQUEST_URI"] === "/data") {
    http_response_code(200);
    header("Content-type: application/json");
    echo '{"ok": true, "timestamp": ' . time() . '}';
} else {
    http_response_code(404);
    echo "Could not find " . $_SERVER["REQUEST_URI"];
}
