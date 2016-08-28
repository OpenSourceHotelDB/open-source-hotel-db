<?php

define('COLOR_CLEAR', "\033[0m");

// @see http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
function green($str) {
    return "\033[0;32m" . $str . COLOR_CLEAR;
}

function red($str) {
    return "\033[0;31m" . $str . COLOR_CLEAR;
}

function purple($str) {
    return "\033[0;35m" . $str . COLOR_CLEAR;
}

function lightblue($str) {
    return "\033[1;34m" . $str . COLOR_CLEAR;
}

function spg($str) {
    echo "\n" . purple("[STARWOOD] ") . $str;
}

function hilton($str) {
    echo "\n" . lightblue("[HILTON] ") . $str;
}

function get_url($url) {
    $options = array(
        'http' => array(
            'method' => 'GET',
            'header' => join("\r\n", array(
                "Accept-language: en-US,en;q=0.8,es;q=0.6",
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/52.0.2743.116 Safari/537.36"
            ))
        )
    );

    $context = stream_context_create($options);

    return file_get_contents($url, false, $context);
}