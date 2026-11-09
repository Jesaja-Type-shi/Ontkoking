<?php

$pad = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
if (preg_match('#^/(database|includes|views|\.vscode|\.git)(/|$)#', $pad) || preg_match('#\.(sql|sqlite|md)$#i', $pad)) {
    http_response_code(403);
    echo 'Geen toegang.';
    return true;
}
return false; 
