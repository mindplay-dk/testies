<?php

require 'vendor/autoload.php';

$code = file_get_contents(__DIR__ . "/vendor/mindplay/readable/src/readable.php");

$code = str_replace("namespace mindplay;", "// This copy was generated at build-time\n\nnamespace mindplay\\testies;", $code);

file_put_contents(__DIR__ . "/src/readable.php", $code);
