<?php


function fillArray($cipherAlphabet)
{
    $cipherArray = array();
    for ($i = 0; $i < strlen($cipherAlphabet); $i ++) {
        $cipherArray[] = $cipherAlphabet[$i];
    }
    return $cipherArray;
}

function encrypt($input)
{
    $cipherAlphabet = "yhkqgvxfoluapwmtzecjdbsnri";
    $cipherArray = fillArray($cipherAlphabet);
    
    $cipherText = "";
    $input = strtolower($input);
   

    for ($i = 0; $i < strlen($input); $i ++) {
        $currentLetter = $input[$i];
        if (ctype_alpha($currentLetter)) {
            $cipherIndex = ord($currentLetter);
            $cipherIndex = $cipherIndex - 97; // a = 97 in ascii.
            $cipherText .= $cipherAlphabet[$cipherIndex];
        }
        else
            $cipherText .= $currentLetter;
    }
    return $cipherText;
}

function decrypt($input){
    $plainTextAlphabet = "abcdefghijklmnopqrstuvwxyz";
    $cipherAlphabet = "yhkqgvxfoluapwmtzecjdbsnri";
    
    $plainText ="";
    $input = strtolower($input);

    
    for ($i = 0; $i < strlen($input); $i ++) {
        $currentLetter = $input[$i];
        if (ctype_alpha($currentLetter)) {
            $plainTextIndex = strpos($cipherAlphabet, $currentLetter);
            $plainText .= $plainTextAlphabet[$plainTextIndex];
        }
        else
            $plainText .= $currentLetter;
    }
    return $plainText;
}

?>