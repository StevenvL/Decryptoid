<?php
require_once 'login.php';
ini_set('session.gc_maxlifetime', 60 * 60 * 24);

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die(mysql_fatal_error());
}

// Allows admin to upload 'malware' so that users can check to see if their files are malware.
if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
    $un_temp = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_USER']);
    $pw_temp = mysql_entities_fix_string($conn, $_SERVER['PHP_AUTH_PW']);
    
    $query = "SELECT * FROM userInformation WHERE username='$un_temp'";
    $result = $conn->query($query);
    
    if (! $result) {
        $result->close();
        die(mysql_fatal_error());
    } elseif ($result->num_rows) {
        $row = $result->fetch_array(MYSQLI_NUM);
        
        $salt1 = getSalt($conn, 1, $un_temp);
        $salt2 = getSalt($conn, 2, $un_temp);
        
        $token = hash('ripemd128', "$salt1$pw_temp$salt2");
        if ($token == $row[2]) // All admin usages go within these brackets.
        {
            echo "<center><marquee>Hello $row[0], you are now logged in</marquee></center>";
            session_start();
            $_SESSION['username'] = $un_temp;
            $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'] .$_SERVER['HTTP_USER_AGENT']);
            die("<p><a href = decrpytoid.php> Click here to login to Decrpytoid</a></p>");
            
            
        }
    } else {
        die("Invalid username/password combination");
    }
    $conn->close();
    
    
} else {
    header('WWW-Authenticate: Basic realm="Restricted Section"');
    header('HTTP/1.0 401 Unauthorized');
    destroy_session_and_data();
    die(mysql_fatal_error());
}



// If input is 1. It returns salt1.
// If input is 2. It returns salt2.
function getSalt($conn, $saltNumber, $userName)
{
    $salt = "";
    if ($saltNumber == 1) {
        $stmt = $conn->prepare("SELECT salt_front FROM userInformation WHERE username = '$userName'");
    }
    if ($saltNumber == 2) {
        $stmt = $conn->prepare("SELECT salt_end FROM userInformation WHERE username = '$userName'");
    }
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $rows = $result->num_rows;
        if ($rows == 0) {
            echo "No results were found!";
        } else {
            $result->data_seek(0);
            $row = $result->fetch_array(MYSQLI_NUM);
            $salt = $row[0];
        }
    } else {
        $stmt->close();
        die(mysql_fatal_error());
    }
    $stmt->close();
    return $salt;
}

function mysql_fatal_error()
{
    echo <<< _END
    Sorry!
    <br>
    _END;
}


function isEmpty($input)
{
    $input = trim($input);
    if ($input == "") {
        return true;
    }
    return false;
}

function mysql_entities_fix_string($connection, $string)
{
    return htmlentities(mysql_fix_string($connection, $string));
}

function mysql_fix_string($connection, $string)
{
    if (get_magic_quotes_gpc())
        $string = stripslashes($string);
        return $connection->real_escape_string($string);
}

function sanitize($input)
{
    $input = stripslashes($input);
    $input = strip_tags($input);
    $input = htmlentities($input);
    return $input;
}

// returns user input after sanitizing it.
function get_post($conn, $var)
{
    return sanitize($conn->real_escape_string($_POST[$var]));
}

function destroy_session_and_data()
{
    $_SESSION = array();
    setcookie(session_name(), '', time() - 259200, '/');
    session_destroy();
}


?>
