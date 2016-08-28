<?php

include_once __DIR__ . '/../_shared/output-formatting.php';

spg("Fetching all Properties...");
$cacheKey = __DIR__ . '/starwood.html';
$output = __DIR__ . '/starwood.json';
$allPropertiesURL = 'http://www.starwoodhotels.com/preferredguest/directory/hotels/all/list.html?language=en_US';
$prefixSinglePropertyURL = 'http://www.starwoodhotels.com/preferredguest/property/overview/index.html?language=en_US&propertyID=';

$saved = @file_get_contents($cacheKey);

if (!$saved) {
    $result = get_url($allPropertiesURL);

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

        if ($saved) {
            $key = array_search($propertyId, array_column($saved, 'propertyId'));

            if (false !== $key) {
                spg("Parsing " . $saved[$key]['name'] . ": ");
                echo green('√') . " (already saved)";
                array_push($results, $saved[$key]);
                continue;
            }
        }

        $result = get_url($url);

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