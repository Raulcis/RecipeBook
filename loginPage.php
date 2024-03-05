<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24);
session_start();
require_once 'login.php';
$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) return "An error occurred please try again";

displaySignInForm();
displayRegisterForm();
displayLogout();
register($conn);


if (isset($_POST['logoutBtn']))
{
    if (isset($_SESSION['email']) && strval($_SESSION['ip']) == strval($_SERVER['REMOTE_ADDR']) && $_SESSION['check'] == hash('ripemd128', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']))
    {
        destroy_session_and_data();
        echo "Successfully logged out";
    }
}

if (isset($_POST['loginBtn']))
{
    if (isset($_SESSION['email']) && strval($_SESSION['ip']) == strval($_SERVER['REMOTE_ADDR']) && $_SESSION['check'] == hash('ripemd128', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']))
    {
        echo "Please log out of current account first";
    }else{
	login($conn);
   }
}

function login($conn)
{

    if (isset($_POST['login_Email']) && isset($_POST['login_password']))
    {
        if (empty($_POST['login_Email']) == false && empty($_POST['login_password']) == false)
        {

            $Email = get_post($conn, 'login_Email');
            $Email = mysql_entities_fix_string($conn, $Email);
            $Password = get_post($conn, 'login_password');
            $Password = mysql_entities_fix_string($conn, $Password);

            $query = "SELECT * FROM Credentials WHERE EMail ='$Email'";
            $result = $conn->query($query);
            $rows = $result->num_rows;
            $result->data_seek(0);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            if ($rows > 0)
            {
                if (password_verify($Password, $row['Password']))
                {
                    $_SESSION['email'] = $Email;
		    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
		    $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
                    echo "Log in successful" . "<br>";
                    die("<p><a href=mainPage.php>Click here to continue</a></p>");
                }
                else
                {
                    echo "Error, please use a different username/password";
                }
            }
            else
	    {
		echo "Error, please use a different username/password";
	    }
            $result->close();
        }
    }
}

function register($conn)
{

    if (isset($_POST['reg_email']) && isset($_POST['reg_password']))
    {
        if (empty($_POST['reg_email']) == false && empty($_POST['reg_password']) == false)
        {

            $Email = get_post($conn, 'reg_email');
            $Email = mysql_entities_fix_string($conn, $Email);
            $Password = get_post($conn, 'reg_password');
            $Password = mysql_entities_fix_string($conn, $Password);
            $existEmail = false;

            $existEmail = checkEmail($Email, $conn);
            if ($existEmail == false)
            {
                createAccount($Email, $Password, $conn);
            }
            else
            {
                echo " Creating account failed, check Email input and try again." . "<br>";
            }
        }
    }
}

function checkEmail($Email, $conn)
{

    $email = get_post($conn, 'reg_email');
    $email = mysql_entities_fix_string($conn, $Email);

    $query = "SELECT Email FROM Credentials WHERE Email = '$email' ";

    try
    {
        $result = $conn->query($query);
    }
    catch(Exception $e)
    {
        $result->close();
        $conn->close();
        return false;
    }
    if (!$result)
    {
        echo "Failed to complete request, please try again";
        return false;
    }

    if ($result->num_rows > 0)
    {
        $result->close();
        return true;
    }
    else
    {
        return false;
    }

}

function createAccount($Email, $Password, $conn)
{
    $Email = get_post($conn, 'reg_email');
    $Email = mysql_entities_fix_string($conn, $Email);
    $Password = get_post($conn, 'reg_password');
    $Password = mysql_entities_fix_string($conn, $Password);
    $hashedPass = password_hash($Password, PASSWORD_DEFAULT);

    $query = "INSERT INTO Credentials VALUES" . "('$Email', '$hashedPass')";

    $result = $conn->query($query);
    if (!$result)
    {
        echo "Creation account failed, please try again";
        return false;
    }
    else
    {
        echo "Account created";
        return true;
    }
}

function displaySignInForm()
{
    echo <<<_END
		<hr/>
		<pre>
		█░░ █▀█ █▀▀ █ █▄░█
		█▄▄ █▄█ █▄█ █ █░▀█
		</pre>
		_END;
		echo <<<_END
		<form action="loginPage.php" method="post">
		<pre>
		<label for="login_Email">Email</label>
		<input type="text" name="login_Email" value="" required>
		<label for="login_password">Password</label>
		<input type="password" name="login_password" value="" required>
		<input type="submit" name="loginBtn" value="Login">
		</pre>
		</form>
		<p><a href=mainPage.php>Click here to go to the main page with or without logining in</a></p>
		_END;
}

function displayLogout()
{
		echo <<<_END
		<form action="loginPage.php" method="post">
		<input type='submit' name='logoutBtn' value="Logout">
		</form>
		_END;
		echo "<hr/>";
}

function displayRegisterForm()
{	
		echo "<hr/>";
		echo <<<_END
		<pre>
		█▀█ █▀▀ █▀▀ █ █▀ ▀█▀ █▀█ ▄▀█ ▀█▀ █ █▀█ █▄░█
		█▀▄ ██▄ █▄█ █ ▄█ ░█░ █▀▄ █▀█ ░█░ █ █▄█ █░▀█
		</pre>
		_END;
		echo <<<_END
		<html>
		<head>
		<body>
		<script>
		function validate(form){
		fail += validatePassword(form.reg_password.value)
		fail += validateEmail(form.reg_email.value)
		if (fail == "") return true
		else { alert(fail); return false }
		}
		function validateEmail(field){
		if (field == "") return "No Email was entered. ";
		else if (!((field.indexOf(".") > 0) && (field.indexOf("@") > 0)) || /[^a-zA-Z0-9.@_-]/.test(field))
		return "The Email address is invalid.";
		return "";
		}
		function validatePassword(field)
		{
		if (field == "") return "No Password was entered. "
		else if (field.length < 6)
		return "Passwords must be at least 6 characters. ";
		else if (!/[a-z]/.test(field) || ! /[A-Z]/.test(field) ||!/[0-9]/.test(field))
		return "Passwords require one each of a-z, A-Z and 0-9.";
		return "";
		}
		</script>
		</head>
		<form name = "form" action="loginPage.php" method="post" onsubmit="return validate(this)">
		<pre>
		<label for="reg_email">Email</label>
		<input type="email" name="reg_email" value="" autoComplete='off' required>
		<label for="reg_password">Password</label>
		<input type="password" name="reg_password" value="" required>
		<input type="submit" name="registerBtn" value="Register">
		</pre>
		</form>
		</body>
		</html>
		_END;

}

function get_post($conn, $var)
{
    return $conn->real_escape_string($_POST[$var]);
}

function mysql_entities_fix_string($conn, $string) {
		return htmlentities(mysql_fix_string($conn, $string));
}

function mysql_fix_string($conn, $string) {
		return $conn->real_escape_string($string);
}

function destroy_session_and_data() {
	$_SESSION = array();
	setcookie(session_name(), '', time() - 2592000, '/');
	session_destroy();
}

?>
