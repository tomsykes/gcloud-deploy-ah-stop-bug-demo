<?php
http_response_code(200);
switch($x = @parse_url($_SERVER['REQUEST_URI'])['path']) {
    case '/_ah/stop' :
        echo "OK";
        break;

    default :
        echo "<html lang=\"en\"><body>Response body for {$x}</body>";
}
