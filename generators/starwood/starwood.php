<?php

define('COLOR_CLEAR', "\033[0m");

function green($str) {
    return "\033[0;32m" . $str . COLOR_CLEAR;
}

function red($str) {
    return "\033[0;31m" . $str . COLOR_CLEAR;
}

function purple($str) {
    return "\033[0;35m" . $str . COLOR_CLEAR;
}

function spg($str) {
    echo "\n" . purple("[STARWOOD] ") . $str;
}

function get($url) {
    // create curl resource
    $ch = curl_init();

    // set url
    curl_setopt($ch, CURLOPT_URL, $url);

    //return the transfer as a string
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

    // $output contains the output string
    $output = curl_exec($ch);

    // close curl resource to free up system resources
    curl_close($ch);

    return $output;
}

spg("Fetching all Properties...");
$cacheKey = 'starwood.html';
$output = 'starwood.json';
$allPropertiesURL = 'http://www.starwoodhotels.com/preferredguest/directory/hotels/all/list.html?language=en_US';
$prefixSinglePropertyURL = 'http://www.starwoodhotels.com/preferredguest/property/overview/index.html?language=en_US&propertyID=';
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

$saved = @file_get_contents($cacheKey);

if (!$saved) {
    $result = file_get_contents($allPropertiesURL, false, $context);

    if (!$result) {
        spg(red("Failed to fetch " . $allPropertiesURL));
        exit;
    }

    file_put_contents($cacheKey, $result);
    echo green("Done.") . " (and saved to disk cache)";
} else {
    echo green("Done.") . " (loaded from disk cache)";
    $result = $saved;
}

if (preg_match_all("|<input type=\"hidden\" class=\"propertyId\" value=\"(\d+)\" />|m", $result, $matches)) {
    spg(green(count($matches[1])) . " total properties found.");

    $results = array();
    $failures = 0;
    $saved = @json_decode(@file_get_contents($output), true);

    foreach ($matches[1] as $propertyId) {
        $url = $prefixSinglePropertyURL . $propertyId;
        $key = array_search($propertyId, array_column($saved, 'propertyId'));

        if (false !== $key) {
            spg("Parsing " . $saved[$key]['name'] . ": ");
            echo green('√') . " (already saved)";
            array_push($results, $saved[$key]);
            continue;
        }

        $result = file_get_contents($url, false, $context);

        if (!$result) {
            $failures++;
            echo red('x');
            continue;
        }

        preg_match("|class=\"fn\">([^<]+)|m", $result, $name);
        spg("Parsing " . $name[1] . ": ");

        if (!$name[1]) {
            $failures++;
            echo red('x') . ' (invalid property, it might be redirecting)';
            continue;
        }

        preg_match("|class=\"spgCategory\">SPG Category (\d+)|m", $result, $category);
        preg_match("|class=\"phoneNumber\">([^<]+)|m", $result, $phone);
        preg_match("|class=\"street-address\">(.+)</li>|m", $result, $address);
        preg_match("|class=\"city\">(.+)</li>|m", $result, $city);
        preg_match("|class=\"region\">(.+)</li>|m", $result, $state);
        preg_match("|class=\"postal-code\">(.+)</li>|m", $result, $zip);
        preg_match("|class=\"country-name\">(.+)</li>|m", $result, $country);
        preg_match("|class=\"tel\">(.+)</span>|m", $result, $phone);
        preg_match("|data-latitude=\"([^\"]+)\" data-longitude=\"([^\"]+)\"|m", $result, $latLng);
        echo green("√");

        $data = array(
            "propertyId" => $propertyId,
            "name" => strip_tags($name[1]),
            "url" => strip_tags($url),
            "category" => "SPG" . strip_tags($category[1]),
            "address" => strip_tags($address[1]),
            "city" => strip_tags($city[1]),
            "state" => strip_tags($state[1]),
            "country" => strip_tags($country[1]),
            "zip" => strip_tags($zip[1]),
            "phone" => strip_tags($phone[1]),
            "latitude" => strip_tags($latLng[1]),
            "longitude" => strip_tags($latLng[2])
        );

        array_push($results, $data);

        // Just to prevent being flagged as DoS by Akamai
        sleep(rand(0, 3));
    }

    file_put_contents($output, json_encode($results, JSON_PRETTY_PRINT));

    spg(green(count($results)) . ' total successful results saved.');
    spg(red($failures) . ' total error results.');

} else {
    spg(red("No properties found."));
}

spg("√ All Done.");