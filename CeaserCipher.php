<?php

// Simple Substitution using Ceaser's cipher.
// Shifts by 3.
// Leaves spaces and other non alphabet elements the same.
function encrypt($input)
{
    $shift = 3;
    $shiftedString = "";

    for ($i = 0; $i < strlen($input); $i ++) {
        $ascii = ord($input[$i]);
        
        //If current letter is apart of alphabet, edit it.
        //Otherwise leave it alone
        if (($ascii >= 65 && $ascii <= 90) || ($ascii >= 88 && $ascii <= 122)) {
            //This deals with wrap around.
            if (($ascii >= 120 && $ascii <= 122) || ($ascii >= 88 && $ascii <= 90)) {
                $shiftedChar = chr($ascii - 23);
            } else
                $shiftedChar = chr($ascii + $shift);
            
                
        } else {
            $shiftedChar = chr($ascii);
        }
        $shiftedString .= $shiftedChar;
    }

    return $shiftedString;
}

function decrypt($input)
{
    $shift = - 3;
    $shiftedString = "";

    for ($i = 0; $i < strlen($input); $i ++) {
        $ascii = ord($input[$i]);
        
        //If current letter is apart of alphabet, edit it.
        //Otherwise leave it alone
        if (($ascii >= 65 && $ascii <= 90) || ($ascii >= 88 && $ascii <= 122)) {
            //This deals with wrap around.
            if (($ascii >= 97 && $ascii <= 99) || ($ascii >= 65 && $ascii <= 67)) {
                $shiftedChar = chr($ascii + 23);
            } else
                $shiftedChar = chr($ascii + $shift);
            
                
        } else {
            $shiftedChar = chr($ascii);
        }
        $shiftedString .= $shiftedChar;
    }

    return $shiftedString;
}

?>

