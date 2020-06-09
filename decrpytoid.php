<?php
require_once 'login.php';
ini_set('session.gc_maxlifetime', 60 * 60 * 24);
ini_set('session.use_only_cookies', 1);

session_start();

if (isset($_POST['logout'])) {
    destroy_session_and_data();
}
if (isset($_SESSION['username']) && isset($_SESSION['check'])) {

    if (($_SESSION['check'] == hash('ripemd128', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']))) {
        $username = $_SESSION['username'];

        echo <<<_session
            <form method="post"action="decrpytoid.php" enctype="multipart/form-data">
            <center><marquee>Hello $username, you are now logged in
            <input type= "submit" name="logout" class="button" value="Click Here to Logout!"/>
            </marquee></center></form>
        _session;
    } else {
        // Tries to prevent session hijacking.
        different_user();
    }
}

echo <<<_END
<html>
    <head><title>Decryptoid</title></head><body>
        <form method="post"action="decrpytoid.php" enctype="multipart/form-data">
<pre>
Welcome to Decryptiod. You need to allow cookies, for this site to work properly.
Looking to sign in? Click <a href="authenticate.php">here</a>.

New user, sign up here!
Username: <input type = "text" name = "username">

E-mail:   <input type = "text" name = "email">

Password: <input type = "password" name = "password" autocomplete="off">

<input type= "submit" name="createAccount" class="button" value="Create Account!"/>

<input type= "submit" name="showUsers" class="button" value="Show Users"/>  <input type= "submit" name="showRecords" class="button" value="Show Uploads"/>  <input type= "submit" name="deleteRecords" class="button" value="Delete Uploads"/>

</form>


<form method="post"action="decrpytoid.php" enctype="multipart/form-data">

Enter plaintext/ciphertext or submit a .txt file:
<input type="file" name= "file" size="10">

<textarea name = "encryptBox" cols = "50" rows = "10" wrap = "soft"></textarea>

<select name="dropdown" id = "dropdown" value = "dropdown"> 
  <option value="Simple Subtitution">Simple Subtitution</option>
  <option value="Double Transposition">Double Transposition (Key Needed!)</option>
  <option value="RC4">RC4 (Key Needed!)</option>
  <option value="Ceaser">Ceaser Shift</option>
  <option value="Brute">Brute Force (Not Complete)</option>
</select> Key (For Double Transposition OR RC4): <input type = "text" name = "key">

<input type="submit" name="encryptButton" value="Encrypt"/> <input type="submit" name="decryptButton" value="Decrypt"/>


</form></body></html>
<br>
_END;

$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) {
    die(mysql_fatal_error());
}

if (isset($_POST['createAccount'])) {
    $username = get_post($conn, 'username');
    $password = get_post($conn, 'password');
    $email = get_post($conn, 'email');

    if (isEmpty($username) || isEmpty($password) || isEmpty($email)) {
        echo "All fields need to be filled out.";
        die();
    }

    if (! checkUsername($username)) {
        die("Username is not valid. 'a-zA-Z0-9\-\_' only.");
    }

    if (isEmailTaken($conn, $email)) {
        die("There is an account associated with this email. Please login.");
    }

    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $emailErr = "Invalid email format";
        die($emailErr);
    }

    $saltLength = 8; // Fixed size in table
    $salt1 = generateSalt($saltLength);
    $salt2 = generateSalt($saltLength);
    $token = hash('ripemd128', "$salt1$password$salt2");

    $stmt = $conn->prepare("INSERT INTO userInformation VALUES(?,?,?,?,?)");
    $stmt->bind_param("sssss", $username, $email, $token, $salt1, $salt2);

    if ($stmt->execute()) {
        echo "New account created with username: <b>$username</b><br>";
    } else {
        $stmt->close();
        echo "There was an error creating your account. <br>";
        die(mysql_fatal_error());
    }

    $stmt->close();
}

if (isset($_POST['showUsers'])) {
    printOutUsers($conn);
}

if (isset($_POST['showRecords'])) {
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        printOutRecords($conn, $username);
    } else
        die("<b>You are not logged in, please sign in to see stored data</b>");
}

if (isset($_POST['deleteRecords'])) {
    $stmt = $conn->prepare("TRUNCATE TABLE storedInfo");
    if ($stmt->execute()) {
        echo "Stored records deleted";
    } else {
        $stmt->close();
        echo "There was an error deleting records. <br>";
        die(mysql_fatal_error());
    }

    $stmt->close();
}

if (isset($_POST['encryptButton'])) {
    echo "encrypt choosen\n";

    $textBoxContents = get_post($conn, 'encryptBox');
    $key = "";
    $cipherText = "";
    $event = "";

    if (isEmpty($textBoxContents)) {
        if (isset($_FILES['file'])) {
            if ($_FILES['file']['size'] == 0 && $_FILES['file']['error'] == 4) {
                echo "<b> No file uploaded. </b>";
            } else if ($_FILES) {
                $file = $_FILES["file"]["name"];
                $file = sanitize($file);

                switch ($_FILES["file"]["type"]) {
                    case "text/plain":
                        $ext = "txt";
                        break;
                    default:
                        $ext = '';
                        break;
                }
                if ($ext) {
                    $n = "file.$ext";

                    $contents = file_get_contents($file);
                    $contents = sanitize($contents);
                    $textBoxContents = $contents;
                } else
                    die("<b>Please upload a .txt file</b>");
            }
        }
    }

    if (isEmpty($textBoxContents)) {
        die("No inputs were given.");
    }

    echo "Input: '$textBoxContents'<br>";

    if (isset($_POST['dropdown'])) {
        $event = $_POST['dropdown'];

        // Simple Substitution Cipher
        if ($event == "Simple Subtitution") {
            require_once 'SimpleCipher.php';

            $cipherText = encrypt($textBoxContents);
            echo "Ciphertext: '$cipherText'<br>";
        }

        // Double Transposition Cipher
        if ($event == "Double Transposition" && ! isEmpty(get_post($conn, 'key'))) {
            require_once 'DoubleTrans.php';
            $key = get_post($conn, 'key');

            $cipherText = encrypt($textBoxContents, $key);
            echo "Ciphertext: '$cipherText'<br>";
        } else if ($event == "Double Transposition" && isEmpty(get_post($conn, 'key'))) {
            echo "Key is needed for this algorithm<br>";
        }

        // RC4 Cipher
        if ($event == "RC4" && ! isEmpty(get_post($conn, 'key'))) {
            require_once 'RC4.php';
            $key = get_post($conn, 'key');
            $cipherText = encrypt($textBoxContents, $key);
            echo "Ciphertext: '$cipherText'<br>";
        } else if ($event == "RC4" && isEmpty(get_post($conn, 'key'))) {
            echo "Key is needed for this algorithm<br>";
        }

        // Ceaser Cipher
        if ($event == "Ceaser") {
            require_once 'CeaserCipher.php';

            $cipherText = encrypt($textBoxContents);
            echo "Ciphertext: '$cipherText'<br>";
        }

        // If logged in, store information
        if (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
            storeRecordsPlain($conn, $username, $textBoxContents, $cipherText, $key, $event);
        }
    }
}

if (isset($_POST['decryptButton'])) {
    echo "decrypt choosen\n";

    $textBoxContents = get_post($conn, 'encryptBox');
    $key = "";
    $plainText = "";
    $event = "";

    if (isEmpty($textBoxContents)) {
        if (isset($_FILES['file'])) {
            if ($_FILES['file']['size'] == 0 && $_FILES['file']['error'] == 4) {
                echo "<b> No file uploaded. </b>";
            } else if ($_FILES) {
                $file = $_FILES["file"]["name"];
                $file = sanitize($file);

                switch ($_FILES["file"]["type"]) {
                    case "text/plain":
                        $ext = "txt";
                        break;
                    default:
                        $ext = '';
                        break;
                }
                if ($ext) {
                    $n = "file.$ext";

                    $contents = file_get_contents($file);
                    $contents = sanitize($contents);
                    $textBoxContents = $contents;
                } else
                    die("<b>Please upload a .txt file</b>");
            }
        }
    }

    if (isEmpty($textBoxContents)) {
        die("No inputs were given.");
    }

    echo "Input: '$textBoxContents'<br>";

    if (isset($_POST['dropdown'])) {
        $event = $_POST['dropdown'];

        // Simple Substitution Cipher
        if ($event == "Simple Subtitution") {
            require_once 'SimpleCipher.php';

            $plainText = decrypt($textBoxContents);
            echo "Plaintext: '$plainText'<br>";
        }

        // Double Transposition Cipher
        if ($event == "Double Transposition" && isset($_POST['key'])) {
            require_once 'DoubleTrans.php';
            $key = get_post($conn, 'key');

            $plainText = decrypt($textBoxContents, $key);
            echo "Plaintext: '$plainText'<br>";
        } else if ($event == "Double Transposition" && ! isset($_POST['key'])) {
            echo "Key is needed for this algorithm<br>";
        }

        // RC4 Cipher
        if ($event == "RC4" && ! isEmpty(get_post($conn, 'key'))) {
            require_once 'RC4.php';
            $key = get_post($conn, 'key');
            $plainText = decrypt($textBoxContents, $key);
            echo "Plaintext: '$plainText'<br>";
        } else if ($event == "RC4" && isEmpty(get_post($conn, 'key'))) {
            echo "Key is needed for this algorithm<br>";
        }

        // Ceaser Cipher
        if ($event == "Ceaser") {
            require_once 'CeaserCipher.php';

            $plainText = decrypt($textBoxContents);
            echo "Plaintext: '$plainText'<br>";
        }
        if (isset($_SESSION['username'])) {
            $username = $_SESSION['username'];
            storeRecordsCipher($conn, $username, $textBoxContents, $plainText, $key, $event);
        }
    }
}
$conn->close();

// ===================================FUNCTIONS BELOW===================================
function printOutUsers($conn)
{
    // This should be safe because we wrote the query ourselves and it does not take in any user inputs.
    $stmt = $conn->prepare("SELECT * FROM userInformation");
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $rows = $result->num_rows;

        if ($rows == 0) {
            echo "No results were found!";
        }

        for ($j = 0; $j < $rows; ++ $j) {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            echo <<<_END
            <pre>
            User Name: $row[0]
            E-Mail: $row[1]
            </pre>
            _END;
        }
    } else {
        $stmt->close();
        die(mysql_fatal_error());
    }
    $stmt->close();
}

function storeRecordsPlain($conn, $username, $plainText, $encodedPlainText, $key, $event)
{
    $stmt = $conn->prepare('INSERT INTO storedInfo VALUES(?,CURRENT_TIMESTAMP,?,?,"" ,"",?,?)');
    $stmt->bind_param("sssss", $username, $plainText, $encodedPlainText, $key, $event);

    if ($stmt->execute()) {
        echo "Stored plaintext and encoded-plaintext";
    } else {
        $stmt->close();
        echo "There was an error storing this information <br>";
        die(mysql_fatal_error());
    }

    $stmt->close();
}

function storeRecordsCipher($conn, $username, $cipherText, $decodedCipherText, $key, $event)
{
    $stmt = $conn->prepare('INSERT INTO storedInfo VALUES(?,CURRENT_TIMESTAMP,"","",?,?,?,?)');
    $stmt->bind_param("sssss", $username, $cipherText, $decodedCipherText, $key, $event);

    if ($stmt->execute()) {
        echo "Stored ciphertext and decoded-ciphertext";
    } else {
        $stmt->close();
        echo "There was an error storing this information <br>";
        die(mysql_fatal_error());
    }

    $stmt->close();
}

function printOutRecords($conn, $username)
{
    // This should be safe because we wrote the query ourselves and it does not take in any user inputs.
    $stmt = $conn->prepare("SELECT * FROM storedInfo WHERE username = ?");
    $stmt->bind_param("s", $username);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $rows = $result->num_rows;

        if ($rows == 0) {
            echo "No results were found!";
        }

        for ($j = 0; $j < $rows; ++ $j) {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);

            // Prints out all encryption related messages
            // Prints out Timestamp, plaintext, encoded message, and optional key.
            if (! isEmpty($row[2])) {
                echo <<<_END
                <pre>
                <b>Encoding</b>
                Timestamp: $row[1]
                Cipher: $row[7]
                Plaintext: '$row[2]'
                Encoded: '$row[3]'
                Key (optional): '$row[6]'
                </pre>
                _END;
            } else {
                echo <<<_END
                <pre>
                <b>Decoding</b>
                Timestamp: $row[1]
                Cipher: $row[7]
                Ciphertext: '$row[4]'
                Decoded: '$row[5]'
                Key (optional): '$row[6]'
                </pre>
                _END;
            }
        }
    } else {
        $stmt->close();
        die(mysql_fatal_error());
    }
    $stmt->close();
}

function checkUsername($username)
{
    $regex = '/^[a-zA-Z0-9\-\_]*$/';
    if (preg_match($regex, $username)) {
        return true;
    }
    return false;
}

function isEmailTaken($conn, $email)
{
    $stmt = $conn->prepare('SELECT email FROM userInformation');
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $rows = $result->num_rows;

        for ($j = 0; $j < $rows; ++ $j) {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            if ($row[0] == $email) {

                return true;
            }
        }
    } else {
        $stmt->close();
        echo "There creating your account. Please try again. <br>";
        die(mysql_fatal_error());
    }

    $stmt->close();
    return false;
}

function isEmpty($input)
{
    $input = trim($input);
    if ($input == "") {
        return true;
    }
    return false;
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

function generateSalt($n)
{
    $result = "";
    $saltLength = $n;

    while ($n > 0) {
        $salt = rand();
        $salt = md5($salt);
        $result = $result . $salt;
        $n = $n - 32;
    }
    return substr($result, 0, $saltLength);
}

function mysql_fatal_error()
{
    echo <<< _END
    Sorry!
    <br>
    _END;
}

function different_user()
{
    destroy_session_and_data();
    die("There was an error logging in. Please try again!");
}

function destroy_session_and_data()
{
    $_SESSION = array();
    setcookie(session_name(), '', time() - 259200, '/');
    session_destroy();
}

?>