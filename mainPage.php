<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24);
session_start();
require_once 'login.php';
$conn = new mysqli($hn, $un, $pw, $db);
if ($conn->connect_error) return "An error occurred please try again";

	displayForm();

if (isset($_SESSION['email']) && strval($_SESSION['ip']) == strval($_SERVER['REMOTE_ADDR']) && $_SESSION['check'] == hash('ripemd128', $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']))
{
	
	displayAdd();
	displayLogout();
	displayAddRecipe();
	displayFav();
        getRecipes($conn);

}else{displayLogin();}

    if (isset($_POST['Add']))
    {
       buildRecipe($conn);
       displayBuilt();
       displaySearch();
    }
    if (isset($_POST['Search']))
    {
	getRecipesSearch($conn);
        $_SESSION["search"] = "";
    }
    if (isset($_POST['Favorite']))
    {
        addFavorites($conn);
    }
    if (isset($_POST['unFavorite']))
    {
        deleteFavorites($conn);
    }

    if (isset($_POST['logoutBtn']))
    {
        destroy_session_and_data();
        echo "Successfully logged out";
    }
    if (isset($_POST['addRec']))
    {
	addRecipes($conn);
    }

function displayLogout(){
    echo <<<_END
		<form action="loginPage.php" method="post">
		<input type='submit' name='logoutBtn' value="Logout">
		</form>
		_END;
		echo "<hr/>";
		
	}
function displayFav(){
    echo <<<_END
		<h2>Favorite Recipes </h2>
		_END;
		
	}
function displayBuilt(){
    echo <<<_END
		<p><strong>Search for recipes with: </strong></p>
		_END;
		echo $_SESSION["search"];
	}
function displayLogin(){
    echo <<<_END
		<p>Log in to save your favorite recipes.</p>
		<p><a href=loginPage.php>Click here to log in or register</a></p>
		_END;
		echo "<hr/>";
	}
function displaySearch(){
    echo <<<_END
		<form action="mainPage.php" method="post"">
		<pre>
		<input type="submit" name="Search" value="Search for recipes">
		</pre>
		</form>
		_END;
		echo "<hr/>";
		
	}
function displayAdd(){
    echo <<<_END
		<form action="mainPage.php" method="post" onsubmit="return validate(this)">
		<pre>
		<label for="Fav">Input a recipe ID </label>
		<input type="text" name="Fav" value="" required >
		<input type="submit" name="Favorite" value="Add to favorites">
		<input type="submit" name="unFavorite" value="Delete from favorites">
		</pre>
		</form>
		_END;
		echo "<hr/>";
	}

function displayAddRecipe(){
    echo <<<_END
		<h2> Add your own Recipe </h2>
		<form action="mainPage.php" method="post"">
		<pre>
		<label for="Rec">Recipe name </label>
		<input type="text" name="Rec" value="" required >
		<label for="Rec">Ingredients</label>
		<input type="text" name="Ing" value="" required >
		<input type="submit" name="addRec" value="Add Recipe">
		</pre>
		</form>
		_END;
		echo "<hr/>";
	}

function displayForm()
	{
		echo <<<_END
		<pre>
		</pre>
		_END;
		echo <<<_END
		<form action="mainPage.php" method="post">
		<pre>
		<p><strong>Add ingredients one by one or all together with ingredients separated by spaces.</strong></p>
		<label for="Ingredients">Ingredients</label>
		<input type="text" name="Ingredients" value="" required >
		<input type="submit" name="Add" value="Add Ingredient">
		</pre>
		</form>
		_END;
		echo "<hr/>";
	}

function getRecipes($conn)
{
    $get = $_SESSION["email"];

    $query = "select ID,name,recipe from Recipes,link where link.Email = '$get' and link.FK_r = ID";

    try {
        $result = $conn->query($query);
    } catch (Exception $e) {
        $result->close();
        $conn->close();
    }
    if (!$result) {
        echo "Failed to complete request, please try again";
    }
    if ($result->num_rows > 0) {
        echo "<table><tr><th>ID</th><th>Name</th><th>Recipe</th></tr>";
        for ($j = 0; $j < $result->num_rows; $j++) {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            echo "<tr>";
            for ($k = 0; $k < 3; ++$k) {
                echo "<td>$row[$k]</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    $result->close();
}
function getRecipesSearch($conn)
{
    $recipe = $_SESSION["search"];
    $recipe = strtolower($recipe);
    $recipe2 = substr($recipe, 0, -1);

    $query = "select ID,name,recipe from Recipes where Recipe = '$recipe2'";

    try {
        $result = $conn->query($query);
    } catch (Exception $e) {
        $result->close();
        $conn->close();
    }
    if (!$result) {
        echo "Failed to complete request, please try again";
    }
    if ($result->num_rows > 0) {
        echo "<h2> Search Results </h2>";
        echo "<table><tr><th>ID</th><th>Name</th><th>Recipe</th></tr>";
        for ($j = 0; $j < $result->num_rows; $j++) {
            $result->data_seek($j);
            $row = $result->fetch_array(MYSQLI_NUM);
            echo "<tr>";
            for ($k = 0; $k < 3; ++$k) {
                echo "<td>$row[$k]</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No results found";
    }
    $result->close();
}
function buildRecipe($conn)
{
    if (isset($_POST["Ingredients"]) && empty($_POST["Ingredients"]) == false) {
        $add = get_post($conn, "Ingredients");
        $add = mysql_entities_fix_string($conn, $add);

        if (isset($_SESSION["search"]) == false) {
            $_SESSION["search"] = $add . " ";
        } else {
            $_SESSION["search"] = $_SESSION["search"] . $add . " ";
        }
    }
}
function addFavorites($conn)
{
    if (isset($_POST["Fav"]) && empty($_POST["Fav"]) == false) {
        $ID = get_post($conn, "Fav");
        $ID = mysql_entities_fix_string($conn, $ID);
        $user = $_SESSION["email"];
        $exist = false;

        $exist = ID_Exist($conn);

        if ($exist == true) {
            $query = "INSERT INTO link VALUES" . "('$user','$ID')";
            $result = $conn->query($query);
            if (!$result) {
                echo "Failed to complete request, please try again";
            } else {
                header("refresh: 0");
            }
        } else {
            echo "Failed to complete request, please try again";
        }
    }
}
function deleteFavorites($conn)
{
    if (isset($_POST["Fav"]) && empty($_POST["Fav"]) == false) {
        $ID = get_post($conn, "Fav");
        $ID = mysql_entities_fix_string($conn, $ID);
        $user = $_SESSION["email"];

        $query = "DELETE FROM link WHERE Email = '$user' and FK_r = '$ID'";

        $result = $conn->query($query);
        if (!$result) {
            echo "Process failed, please try again";
        } else {
            header("refresh: 0");
        }
    }
}
function ID_Exist($conn)
{
    if (isset($_POST["Fav"]) && empty($_POST["Fav"]) == false) {
        $ID = get_post($conn, "Fav");
        $ID = mysql_entities_fix_string($conn, $ID);

        $query = "SELECT ID FROM Recipes where ID = '$ID'";

        try {
            $result = $conn->query($query);
        } catch (Exception $e) {
            $result->close();
            $conn->close();
            return false;
        }
        if (!$result) {
            echo "Failed to complete request, please try again";
            return false;
        }

        if ($result->num_rows > 0) {
            $result->close();
            return true;
        } else {
            return false;
        }
    }
}

function addRecipes($conn)
{
    if (isset($_POST["Rec"]) && isset($_POST["Ing"])) {
        if (empty($_POST["Rec"]) == false && empty($_POST["Ing"]) == false) {
            $Name = get_post($conn, "Rec");
            $Name = mysql_entities_fix_string($conn, $Name);
            $Ingredients = get_post($conn, "Ing");
            $Ingredients = mysql_entities_fix_string($conn, $Ingredients);
            $Ingredients = strtolower($Ingredients);

            $query = "INSERT INTO recipes VALUES" . "(null,'$Name', '$Ingredients')";

            $result = $conn->query($query);
            if (!$result) {
                echo "Couldn't add recipe, please try again";
            } else {
                echo "<br>";
                echo "<h4> Recipe added! </h4>";
            }
        }
    }
}

function get_post($conn, $var) {
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