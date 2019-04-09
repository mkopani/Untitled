<?php session_start(); ?>
<html>		
	<head>
		<meta charset="utf-8"/>		
		<title>All Networks | Untitled Review Website</title>		
		<link href="style.css" rel="stylesheet" type="text/css">
		
		<div id=top>
			<a class="header-link" href="<?php echo "http://" . $_SERVER['HTTP_HOST']; ?>"><h1>Untitled Review Website</h1></a>
			<span class="separator"><b>|</b></span>
			<h2>The “leading” source for TV reviews, ratings, and awards</h2>
		</div>		
	</head>	
	<body>
		<div class="column-left">			
			<span class="content-header"><h3>All Networks</h3></span>
<?php	
$servername = "localhost:3306";
$username = "application";
$password = "applicationPASSWORD";
$dbname = "untitled_review_website";

$conn = new mysqli($servername, $username, $password, $dbname);
$error = false;
if ($conn->connect_error) {
    $error = true;
}
else {
	$sql = "SELECT platformID, name, isNetwork
			FROM Platform";
	$result = $conn->query($sql);
	
	echo "<div class=\"content\">
				<table align=\"center\" class=\"listing\">";

	if ($result->num_rows > 0) {
		$missing = "<span class=\"no-rating\">N/A</span>";
		
		echo "<tr><th>Platform Name</th><th>Platform Type</th><th>Ordered Seasons</th><th>Broadcast Programs</th></tr>";
		while ($row = $result->fetch_assoc()) {
			$platformID = $row["platformID"];
			
			echo "<tr class=\"table-entry\">
					<td><a href=\"http://" . $_SERVER['HTTP_HOST'] . "/platform?id=" . $platformID . "\">" . $row['name'] . "</a></td>
					<td align=\"center\">" . ($row['isNetwork'] ? "Television Network" : "Streaming Service") . "</td>
					<td align=\"center\">";
			
			$innerSql = "SELECT COUNT(seasonID) AS count
						FROM Season
						WHERE orderingPlatformID = " . $platformID;
			$innerResult = $conn->query($innerSql);
			
			if ($innerResult->num_rows > 0) {
				$innerRow = $innerResult->fetch_assoc();
				echo "<p>" . $innerRow['count'] . "</p>";
			} else {
				echo $missing;
			}
			
			echo "</td><td align=\"center\">";
			                                                                             /* (8) additional sql */
			$innerSql = "SELECT COUNT(DISTINCT progID) AS count
						FROM " . ($row['isNetwork'] ? "Broadcast" : "Stream") . " R
						WHERE R.platformID = " . $platformID;
			$innerResult = $conn->query($innerSql);
			
			if ($innerResult->num_rows > 0) {
				$innerRow = $innerResult->fetch_assoc();
				echo "<p>" . $innerRow['count'] . "</p>";
			} else {
				echo $missing;
			}
			
			echo "</td></tr>";
		}
	} else {
		$error = true;
	}
}
if ($error) {
	echo "<p>Program listing is not available...</p>";
}
$conn->close();
?>
				</table>
			</div>
		</div>
		<div class="column-right">
<?php
if (isset($_SESSION['logged_in']) && !isset($_POST['logout'])) {	
	$user_descriptor = "<b>" . $_SESSION['username'] . "</b>";
	
	if (isset($_SESSION['is_critic']) && $_SESSION['is_critic']) {
		$servername = "localhost:3306";
		$username = "application";
		$password = "applicationPASSWORD";
		$dbname = "untitled_review_website";

		$conn = new mysqli($servername, $username, $password, $dbname);
		if (!$conn->connect_error) {
			$sql = "SELECT name FROM Critic WHERE dbUsername = '" . $_SESSION['username'] . "'";
			$result = $conn->query($sql);

			if ($result->num_rows > 0) {
				$row = $result->fetch_assoc();
				$user_descriptor = "<b>" . $row['name'] . "</b> (" . $_SESSION['username'] . ")";
			}
		}
		$conn->close();
	}
	
	echo "<div id=\"account\">
			<span class=\"content-header\"><h3>Account</h3></span>
					<p>Welcome, " . $user_descriptor . "</b>!</p>";
			
	if (isset($_SESSION['is_critic']) && $_SESSION['is_critic']) {
		echo "<p>Search for a program to add a review to, or click 
			<a href=\"http://" . $_SERVER['HTTP_HOST'] . "/critic_management\">here</a>
			to view and update your reviews.</p>";
	}
	if(isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
		echo "<p>Click <a href=\"http://" . $_SERVER['HTTP_HOST'] . "/admin_management\">here</a>
			to add data to the review website database.</p>";		
	}
	
	echo "<form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\">
			<button type=\"submit\" name=\"logout\">Logout</button>
		  </form>
		  </div>";
} else { 
	if (isset($_POST['logout'])){
		unset($_SESSION['logged_in']);
		unset($_SESSION['username']);
		unset($_SESSION['password']);
		unset($_SESSION['is_critic']);
		unset($_SESSION['is_admin']);
		header('Refresh: 0');
	}
	
	$servername = "localhost:3306";
	$username = htmlspecialchars($_POST['username']);
	$password = htmlspecialchars($_POST['password']);
	$dbname = "untitled_review_website";
	
	echo "<div id=\"login-form\">
		  <span class=\"content-header\"><h3>Login</h3></span>
		  <form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\">
			<table class=\"plain\">
				<tr>
					<td>Username</td>
					<td><input type=\"text\" name=\"username\" 
						value=\"" . (isset($_POST['username']) ? $_POST['username'] : '') . "\"</td>
				</tr>
				<tr>
					<td>Password</td>
					<td><input type=\"password\" name=\"password\" 
						value=\"" . (isset($_POST['password']) ? $_POST['password'] : '') . "\"</td>
				</tr>
				<tr>
					<td><button type=\"submit\" name=\"login\">Login</button></td>
				</tr>
			</table>
		  </form>";

	if (isset($_POST['login'])) {
		if (!empty($username) && !empty($password)) {
			$verify_error = false;
			
			$conn = new mysqli($servername, $username, $password, $dbname);		
			if ($conn->connect_error) {
				$verify_error = true;
			} else {
				$_SESSION['logged_in'] = true;
				$_SESSION['username'] = $username;
				$_SESSION['password'] = $password;
			}		
			$conn->close();
			$conn = new mysqli($servername, "application", "applicationPASSWORD", $dbname);		
			if ($conn->connect_error) {
				$verify_error = true;
			} else {
				$sql = "SELECT FROM_USER FROM mysql.role_edges WHERE TO_USER = '" . $username . "'";
				$result = $conn->query($sql);
				
				$_SESSION['is_critic'] = false;
				$_SESSION['is_admin'] = false;		
				
				if ($result->num_rows > 0) {
					while ($row = $result->fetch_assoc()) {
						if ($row['FROM_USER'] == 'Critic'){
							$_SESSION['is_critic'] = true;
						} else if ($row['FROM_USER'] == 'Administrator'){
							$_SESSION['is_admin'] = true;
						}
					}
				} else {
					$verify_error = true;
				}
			}		
			$conn->close();
			
			if ($verify_error) {
				echo "<p>Invalid login, please try again...</p>";
			} else {
				header('Refresh: 0');
			}
		} else if (empty($username)) {
			echo "<p>Please enter a username.</p>";
		} else {
			echo "<p>Please enter a password.</p>";
		}
	}
}
?>
			  </div>
		</div>
	</body>
</html>