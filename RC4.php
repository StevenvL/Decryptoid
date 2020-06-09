<?php
// Symmetric Cipher


function encrypt($input, $key)
{
    $cipher = KSA($key);
    $cipherStream = PRGA($cipher, $input);

    $result = "";
    for ($i = 0; $i < strlen($input); $i ++) {
        $result .= $input[$i] ^ $cipherStream[$i];
    }

    $result = bin2hex($result);

    return $result;
}

function decrypt($input, $key)
{
    $input = str_replace(" ", "", $input);
    if (ctype_xdigit($input)) {
        $test = hexToStr($input);
        $hexResult = encrypt($test, $key);
    }
    $asciiResult = hex2str($hexResult);
    return $asciiResult;
}

/*
 * Function from stackoverflow
 * coverts hex to ascii
 * https://stackoverflow.com/questions/7488538/convert-hex-to-ascii-characters
 */
function hex2str($hex) {
    $str = '';
    for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
    return $str;
}

/*
 * Function from stackoverflow
 * Converts hex to Ascii
 * https://stackoverflow.com/questions/57572019/convert-a-hex-string-to-ascii-string-in-php
 */
function hexToStr($hex)
{
    $hex = trim($hex, " ");
    $string = '';
    for ($i = 0; $i < strlen($hex) - 1; $i += 2) {
        $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));
    }
    return $string;
}

function printDecimal($input)
{
    $result = "";
    for ($i = 0; $i < strlen($input); $i ++) {
        if ($i != (strlen($input) - 1))
            $result .= ord($input[$i]) . " ";
        else
            $result .= ord($input[$i]);
    }
    return $result;
}

function KSA($key)
{
    $keyLength = strlen($key);
    $temp = array();

    for ($i = 0; $i < 256; $i ++) {
        $temp[$i] = $i;
    }

    $j = 0;

    for ($i = 0; $i < 256; $i ++) {
        $j = ($j + $temp[$i] + ord($key[$i % $keyLength])) % 256;
        $swapTemp = $temp[$i];
        $temp[$i] = $temp[$j];
        $temp[$j] = $swapTemp;
    }
    return $temp;
}

function PRGA($arr, $input)
{
    $i = 0;
    $j = 0;
    $result = "";
    $inputLength = strlen($input);

    while ($inputLength > 0) {
        $inputLength --;
        $i = ($i + 1) % 256;
        $j = ($j + $arr[$i]) % 256;
        $temp = $arr[$i];
        $arr[$i] = $arr[$j];
        $arr[$j] = $temp;
        $toAppend = chr($arr[($arr[$i] + $arr[$j]) % 256]);
        $result = $result . $toAppend;
    }

    return $result;
}

?>