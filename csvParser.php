<?php

$csvFile = fopen("sovereignCards.csv", 'r');

$numeric_headers = ['attack', 'armor', 'health', 'cost'];
$array_headers = ['sub_types'];
$headers = fgetcsv($csvFile);

while ($line = fgetcsv($csvFile)) {
    $jsonArray = array();
    for ($i = 0; $i < count($headers); $i++) {
        $field_name = $headers[$i];
        $element = $line[$i];

        if (in_array($field_name, $array_headers)) {
            $element = $element !== '' ? explode(';', $element) : [];
            if (in_array($field_name, $numeric_headers)) {
                $element = array_map(function($value) {
                    return $value !== '' ? intval($value) : null;
                }, $element);
            }
        } elseif (in_array($field_name, $numeric_headers)) {
            $element = $element !== '' ? intval($element) : null;
        }

        if (strpos($field_name, "raw") === false) {
            $jsonArray[$field_name] = $element;
        }
    }
    $jsonString = json_encode($jsonArray, JSON_PRETTY_PRINT);
    $fileName = $line[0];
    file_put_contents(sprintf('opt/data/cards/%s.json', $fileName), $jsonString);
}
