<?php
// Double Transposition

function encrypt($input, $key)
{
    $arr2D = create2DMatrix($input, $key);
    $order = getOrderForKey($key);
    $firstRotationString = rotate($arr2D, $order);
    $rotated2DArray = create2DMatrix($firstRotationString, $key);
    $result = printResult($rotated2DArray, $order);
    return $result;
}

function create2DMatrix($input, $key)
{
    $result = array();
    $keyLength = strlen($key);
    $inputLength = strlen($input);
    $totalSize = findTotalSize($inputLength, $keyLength);

    $nRows = $totalSize / $keyLength;

    $inputIndex = 0;

    for ($i = 0; $i < $nRows; $i ++) {
        $row = array();
        for ($j = 0; $j < $keyLength; $j ++) {
            if ($inputIndex < $inputLength) {

                $row[$j] = $input[$inputIndex];
                $inputIndex ++;
            } else {
                $row[$j] = "EMPTY";
            }
        }
        $result[] = $row;
    }

    return $result;
}

function rotate($array, $order)
{
    $input = arrayToString($array);
    $inputLength = strlen($input);
    $keyLength = sizeof($order);
    $totalSize = findTotalSize($inputLength, $keyLength);

    $nRows = $totalSize / $keyLength;

    $toString = "";
    for ($i = 0; $i < sizeof($order); $i ++) {
        $colIndex = $order[$i];

        for ($j = 0; $j < $nRows; $j ++) {
            if ($array[$j][$colIndex] != "EMPTY") {
                $toString .= $array[$j][$colIndex];
            }
        }
    }
    return $toString;
}

function decrypt($input, $key)
{
    $arr2D = create2DMatrix($input, $key); // Original

    $reverse2D = create2DMatrixReverse($input, $key, $arr2D); // First Reverse

    $order = getOrderForKey($key);
    $cipher = reverseRotate($reverse2D, $order);
    $res = create2DMatrixReverse($cipher, $key, $arr2D);

    $result = arrayToString($res);
    return $result;
}

function create2DMatrixReverse($input, $key, $ogArray)
{
    // Read from array row by row
    // Insert into order column
    $result = array();
    $keyLength = strlen($key);
    $inputLength = strlen($input);
    $totalSize = findTotalSize($inputLength, $keyLength);
    $order = getOrderForKey($key);

    $nRows = $totalSize / $keyLength;

    $inputIndex = 0;

    for ($i = 0; $i < sizeof($order); $i ++) {
        $colIndex = $order[$i];
        for ($j = 0; $j < $nRows; $j ++) {
            

            if ($ogArray[$j][$colIndex] == "EMPTY") {
                $j ++;
                break;
            }

            if ($inputIndex < $inputLength) {
                $result[$j][$colIndex] = $input[$inputIndex];
                $inputIndex ++;
            } else {
                $result[$j][$colIndex] = "EMPTY";
            }
        }
    }
    return $result;
}

function reverseRotate($array, $order)
{
    $temp = arrayToString($array);

    return $temp;
}

function getOrderForKey($key)
{
    $result = array();
    $sortedKey = array();
    for ($i = 0; $i < strlen($key); $i ++) {
        $sortedKey[] = $key[$i];
    }
    sort($sortedKey);

    for ($i = 0; $i < sizeof($sortedKey); $i ++) {
        $curLetter = $sortedKey[$i];
        for ($j = 0; $j < strlen($key); $j ++) {
            if ($curLetter == $key[$j]) {
                $result[$i] = $j;
                $key[$j] = "0";
                break;
            }
        }
    }

    return $result;
}

function arrayToString($array)
{
    $temp = "";

    for ($i = 0; $i < sizeof($array); $i ++) {
        for ($j = 0; $j < sizeof($array[$i]); $j ++) {
            if ($array[$i][$j] != "EMPTY")
                $temp .= $array[$i][$j];
        }
    }
    return $temp;
}

//Debug purposes
function printArr($arr)
{
    foreach ($arr as $var) {
        print_r($var);
        echo "<br>";
    }
}

function printResult($arr2D, $order)
{
    $result = "";
    for ($i = 0; $i < sizeof($order); $i ++) {
        $colIndex = $order[$i];
        for ($j = 0; $j < sizeof($arr2D); $j ++) {
            if ($arr2D[$j][$colIndex] != "EMPTY") {
                if ($arr2D[$j][$colIndex] == " ")
                    $result .= "&nbsp;";
                else
                    $result .= $arr2D[$j][$colIndex];
            }
        }
    }
    return $result;
}

// Gives us exactly how many entries there should be in the 2D matrix
// This will come in handy later when we need to put blank spaces
function findTotalSize($inputLength, $keyLength)
{
    $totalSize = ceil($inputLength / $keyLength);
    $totalSize *= $keyLength;
    return $totalSize;
}

?>