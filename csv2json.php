#!/usr/bin/php
<?php

$JSON_ENCODE_OPTIONS = 0;
$USE_BASICDATA_FORMAT = false;
$READ_STDIN = false;
$SHOW_MOVED_DAYS_OFF = false;
$csv_filename = '';

array_shift($argv);
while ($arg = array_shift($argv)) {
    if (strlen($arg) > 1 && $arg[0] == '-') {
        switch ($arg) {
        case '--pretty':
        case '-p':
            $JSON_ENCODE_OPTIONS = JSON_PRETTY_PRINT;
            break;

        case '--basicdata-format':
            $USE_BASICDATA_FORMAT = true;
            break;

        case '--show-moved-days-off':
            $SHOW_MOVED_DAYS_OFF = true;
            break;

        default:
            die("Unknown option: '$arg'\n");
        }
    } else {
        if (empty($csv_filename)) {
            $csv_filename = $arg;
        } else {
            die("Unexpected argument: '$arg'\n");
        }
    }
}

if (empty($csv_filename)) {
    echo 'Usage: ' . __FILE__ . ' [--pretty|-p] [--basicdata-format] --show-moved-days-off { <input.csv> | - }' . "\n";
    exit(1);
}

define('DAY_STATUS_SHORT_WORKDAY', 1);
define('DAY_STATUS_DAY_OFF', 2);
define('DAY_STATUS_MOVED_DAY_OFF', $SHOW_MOVED_DAYS_OFF ? 6 : DAY_STATUS_DAY_OFF);

function parse_day_status($day_string)
{
    preg_match('/^[0-9]+(.*)$/', $day_string, $matches);
    $status = $matches[1];
    switch ($status) {
    case '*':
        return DAY_STATUS_SHORT_WORKDAY;
        break;
    case '+':
        return DAY_STATUS_MOVED_DAY_OFF;
        break;
    case '':
        return DAY_STATUS_DAY_OFF;
        break;
    default:
        throw new Exception("Unknown day status: $status");
    }
}

function day_status_to_basicdata_format($day_status_int)
{
    $basicdata_status = 0;
    switch ($day_status_int) {
    case DAY_STATUS_SHORT_WORKDAY:
        $basicdata_status = 3;
        break;
    case DAY_STATUS_DAY_OFF:
    case DAY_STATUS_MOVED_DAY_OFF:
        $basicdata_status = 2;
        break;
    default:
        throw new Exception("Unknown day status: $day_status_int");
    }
    return [ 'isWorking' => $basicdata_status ];
}

function parse_month($month, $use_basicdata_format)
{
    $result = [];
    $days = explode(',', $month);
    foreach ($days as $day) {
        $day_number = intval($day);
        $day_status = parse_day_status($day);
        if ($use_basicdata_format) {
            $day_status = day_status_to_basicdata_format($day_status);
        }
        $result[$day_number] = $day_status;
    }
    return $result;
}

function parse_line($line, $use_basicdata_format)
{
    $matches = [];
    $result = [];
    if (!preg_match('/^([0-9]+)/', $line, $matches)) {
       throw new Exception("Cannot parse year from line '$line'");
    }
    $year = intval($matches[1]);
    
    preg_match_all('/,"([^"]+)"+/', $line, $matches);
    $months = $matches[1];
    foreach ($months as $month_number => $month_string) {
        $result[$month_number+1] = parse_month($month_string, $use_basicdata_format);
    }
    return [ $year => $result ];
}

$months = [];

if ($csv_filename == '-') {
    $csv_data = file('php://stdin');
} elseif (file_exists($csv_filename)) {
    $csv_data = file($csv_filename);
} else {
    throw new Exception("Cannot open file '$csv_filename'");
}

array_shift($csv_data);
$calendar = [];
foreach ($csv_data as $line) {
    $calendar = $calendar + parse_line($line, $USE_BASICDATA_FORMAT);
}
$statuses = [];
$statuses[DAY_STATUS_SHORT_WORKDAY] = 'Short day before a public holiday';
$statuses[DAY_STATUS_DAY_OFF] = 'Day off';
if (DAY_STATUS_MOVED_DAY_OFF != DAY_STATUS_DAY_OFF) {
    $statuses[DAY_STATUS_MOVED_DAY_OFF] = 'Day off, moved';
}

$input_data = [];
if (!$USE_BASICDATA_FORMAT) {
    $input_data['statuses'] = $statuses;
}
$input_data['data'] = $calendar;

echo json_encode($input_data, $JSON_ENCODE_OPTIONS) . "\n";
