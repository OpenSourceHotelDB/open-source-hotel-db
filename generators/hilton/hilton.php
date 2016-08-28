<?php

/**
 * http://www3.hilton.com/en_US/hi/ajax/cache/regions.json
 * http://www3.hilton.com/en_US/hi/ajax/cache/regionHotels.json?regionId=136&subregionId=null&hotelStatus=null
 * url . / index.html => to get category at class="rating_component_point_category_value"
 */

include_once __DIR__ . '/../_shared/output-formatting.php';

function getHotelCategory($url) {
    $category = '?';
    if ($result = @file_get_contents($url)) {
        preg_match("|class=\"rating_component_point_category_value\">(\d+)|m", $result, $categoryMatch);
        $category = $categoryMatch[1];
    }
    return $category;
}

hilton("Fetching all Properties...");
$output = __DIR__ . '/hilton.json';
$tmpDir = __DIR__ . '/tmp/';
$regionsFile = $tmpDir . 'regions.json';

$saved = @json_decode(@file_get_contents($output), true);

$domain = 'http://www3.hilton.com';
$allRegions = $domain . '/en_US/hi/ajax/cache/regions.json';
$regionPrefix = $domain . '/en_US/hi/ajax/cache/regionHotels.json?subregionId=null&hotelStatus=null&regionId=';

$regions = @file_get_contents($regionsFile);
if (!$regions) {
    hilton('Regions file not found, fetching from internet...');
    if ($regions = @json_decode(get_url($allRegions), true)) {
        $regions = $regions['region'];
        file_put_contents($regionsFile, json_encode($regions, JSON_PRETTY_PRINT));
        echo green('√');
    } else {
        echo red('x') . ' - failed.';
    }
} else {
    hilton('Regions cached file found.');
    $regions = json_decode($regions, true);
}

$results = array();

foreach ($regions as $i => $region) {
    $id = $region['id'];
    $regionFile = $tmpDir . $id . '.json';
    $regionHotels = @json_decode(@file_get_contents($regionFile), true);

    if (!$regionHotels) {
        hilton('Fetching Region ID ' . $id);
        if ($result = @json_decode(get_url($regionPrefix . $id), true)) {
            $regionHotels = $result['hotels'];
            file_put_contents($regionFile, json_encode($regionHotels, JSON_PRETTY_PRINT));
            echo green('√');
        } else {
            echo red('x') . ' - failed. Skipping';
            continue;
        }
    } else {
        hilton('Region ID ' . $id . ' loaded from cache.');
    }

    foreach ($regionHotels as $hotel) {
        // TODO: check if the following key is UNIQUE
        $propertyId = $hotel['ctyhocn'];
        $url = $domain . $hotel['url'] . '/index.html';

        if ($saved) {
            $key = array_search($propertyId, array_column($saved, 'propertyId'));

            if (false !== $key) {
                hilton("Parsing " . $saved[$key]['name'] . ": ");
                echo green('√') . " (already saved)";
                array_push($results, $saved[$key]);
                continue;
            }
        }

        hilton("Parsing " . $hotel['name'] . ": ");

        $data = array(
            "propertyId" => $propertyId,
            "name" => $hotel['name'],
            "url" => $url,
            "category" => getHotelCategory($url),
            "address" => $hotel['address1'],
            "city" => $hotel['city'],
            "state" => $hotel['state'],
            "country" => $hotel['country'],
            "zip" => $hotel['zip'],
            "phone" => $hotel['phone'],
            "latitude" => $hotel['lat'],
            "longitude" => $hotel['lng']
        );

        array_push($results, $data);

        echo green('√');
    }

    file_put_contents($output, json_encode($results, JSON_PRETTY_PRINT));

    hilton(green(count($results)) . ' total results saved (after ' . ($i + 1) . ' regions)');
}


hilton(green("√") . " All Done.");

