<?php ob_start(); session_start(); ?>
<html>
	<head>
		<meta charset="utf-8"/>
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
	$sql = "SELECT publicationName FROM Publication WHERE publicationName = \"" . urldecode($_GET['id']) . "\"";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			if (!empty($row["publicationName"])){
				$_GET['name'] = $row["publicationName"];
			} else {
				$error = true;						
			}
		}
	} else {
		$error = true;
	}
}
if ($error) {
	echo "<title>Page Not Found | Untitled Review Website</title>";
} else {
	echo "<title>" . $_GET['name'] . " | Untitled Review Website</title>";
}

$conn->close();		
?>
		<link href="style.css" rel="stylesheet" type="text/css">
		
		<div id="top">
			<a class="header-link" href="<?php echo "http://" . $_SERVER['HTTP_HOST']; ?>"><h1>Untitled Review Website</h1></a>
			<span class="separator"><b>|</b></span>
			<h2>The “leading” source for TV reviews, ratings, and awards</h2>
		</div>
		
	</head>
	<body>
		<div class="column-left">
<?php
$publicationID = $_GET['id'];
$selfURL = $_SERVER['PHP_SELF'] . "?id=" . $publicationID;

$servername = "localhost:3306";
$username = "application";
$password = "applicationPASSWORD";
$dbname = "untitled_review_website";
	  
$conn = new mysqli($servername, $username, $password, $dbname);

if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
	$username = $_SESSION['username'];
	$password = $_SESSION['password'];

	$adminConn = new mysqli($servername, $username, $password, $dbname);
}

if (isset($_GET['name']) && !$conn->connect_error && (!isset($adminConn) || !$adminConn->connect_error)) {
	if (isset($_POST['update'])) {
		if (!isset($adminConn)){
			$publicationError = "<p>You are not authorized to perform that action.</p>";
		} else {
			$name = empty($_POST['name']) ? "NULL" : $_POST['name'];
			
			$sql = "UPDATE Publication
					SET publicationName = \"" . $name . "\"
					WHERE publicationName = \"" . $publicationID . "\"";
			$adminConn->query($sql);
			
			$_GET['id'] = $name;
			
			unset($_POST['update']);
			unset($_POST['name']);
			header('Location: /publication?id=' . htmlspecialchars($name));
		}
	} else if (isset($_POST['delete'])) {
		if (!isset($adminConn)){
			$publicationError = "<p>You are not authorized to perform that action.</p>";
		} else {			
			$sql = "DELETE FROM Publication
					WHERE publicationName = \"" . $publicationID . "\"";
			$adminConn->query($sql);
			
			unset($_POST['delete']);
			header('Location: /');
		}		
	}

	if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
		echo "<form action=\"" . $selfURL . "\" method=\"post\">
				<input type=\"hidden\" name=\"id\" value=\"" . $publicationID . "\">";
	}
	
	$title = (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) ? 
		("<input type=\"text\" class=\"title\" name=\"name\" size=\"30\" value=\"" . $publicationID . "\">") : 
		$publicationID;
		
	echo "<span class=\"content-header\"><h3><i>" . $title . "&nbsp;</i></h3></span>
		<div class=\"wide column-left\">
			<table class=\"plain\">";
		
	$missing = "<span class=\"no-rating\">N/A</span>";
	$sql = "SELECT C.name
			FROM Review R, Publishes P, CriticWrites CW, Critic C
			WHERE R.reviewID = P.reviewID
				AND CW.reviewID = R.reviewID
				AND CW.reviewerID = C.reviewerID
				AND P.publicationName = \"" . $publicationID . "\"
			GROUP BY C.reviewerID";
	$result = $conn->query($sql);
	
	if ($result->num_rows > 0) {
		$numRows = $result->num_rows;
		
		echo "<tr><th rowspan=\"" . $numRows . "\">Associated Reviewers</th>";
		
		while ($row = $result->fetch_assoc()){
			echo "<td>" . $row['name'] . "</td></tr><tr>";
		}
		
		echo "</tr>";
	} else {
		echo "<tr><th>Associated Publications</th><td>" . $missing . "</td></tr>";
	}
		
	echo "</table>";
	
	if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
		echo "<button type=\"submit\" name=\"update\">Update</button>
			<button type=\"submit\" name=\"delete\" class=\"right\">Delete</button>";
	}
	
	echo "<p>Here is a list of reviews that <i>" . $publicationID . "</i> has published.</p>
		</div>";
		
	$sql = "SELECT AVG(R.rating) AS rating, COUNT(R.reviewID) AS count
			FROM Review R, Publishes P
			WHERE R.reviewID = P.reviewID
				AND P.publicationName = \"" . $publicationID . "\"";
	$result = $conn->query($sql);
	
	$row = $result->fetch_assoc();	
	$criticRating = isset($row['rating']) ? round($row['rating'], 2) : -1;
	$criticCount = empty($row['count']) ? 0 : $row['count'];
		
	echo "<div class=\"wide column-right\">
			<table class=\"plain right centered\" align=\"right\">
				<tr>
					<th><span class=\"rating\">" .
					(($criticRating == -1) ? ($missing . "</span>") : ($criticRating . "</span> %")) .
					"</th>
				</tr>
				<tr><th>Average Rating from Critics</th></tr>
				<tr><td width=\"150px\">Based on " . $criticCount . " reviews</td></tr>
				<tr><td>&nbsp;</td></tr>
			</table>
		</div>";
	
	if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
		echo "</form>";
	}
		
	if (isset($publicationError)) {
		echo $publicationError;
	}

	                                                                                    /* (5,6)  */	
	$seasonSql = "SELECT R.reviewID, S.serialID, SE.name AS showName, S.seasonID, CR.title, R.rating, P.date
					FROM Review R 
					JOIN CriticReview CR ON R.reviewID = CR.reviewID  
					JOIN Publishes P ON CR.reviewID = P.reviewID
					JOIN Season S ON R.serialID = S.serialID AND R.seasonID = S.seasonID
					JOIN Serial SE ON S.serialID = SE.serialID
					WHERE P.publicationName = \"" . $_GET['name'] . "\"
					ORDER BY P.date DESC, R.reviewID DESC";
	$seasonResult = $conn->query($seasonSql);
	
	$programSql = "SELECT R.reviewID, R.progID, ES.serialID, ES.name AS showName, 
						ES.seasonID, ES.epID, P.name AS programName, R.rating, CR.title, PU.date
					FROM Review R 
					JOIN CriticReview CR ON R.reviewID = CR.reviewID 
					JOIN Publishes PU ON CR.reviewID = PU.reviewID
					JOIN Program P ON R.progID = P.progID
					LEFT JOIN (SELECT E.progID, E.serialID, E.seasonID, E.epID, S.name
						FROM Episode E
						LEFT JOIN Serial S
						ON S.serialID = E.serialID) ES
					ON ES.progID = P.progID
					WHERE PU.publicationName = \"" . $_GET['name'] . "\"
					ORDER BY PU.date DESC, R.reviewID DESC";
	$programResult = $conn->query($programSql);
	
	$type = "Season";
	$result = $seasonResult;
	
	if ($seasonResult->num_rows > 0 || $programResult->num_rows > 0) {		
		for ($i=1; $i<=2; $i++) {
			if ($result->num_rows > 0) {
				echo "<span class=\"content-header\"><h3>" . $type . " Reviews</h3></span>
						<table align=\"center\" class=\"listing\">
							<tr>
								<th>Program Name</th>
								<th>Authors</th>
								<th>Title</th>
								<th>Date</th>
								<th>Rating</th>
							</tr>";
				while ($row = $result->fetch_assoc()) {
					if ($row["programName"] == null) {
						$showLink = "http://" . $_SERVER['HTTP_HOST']. "/season?serialID=" . $row["serialID"] . "&seasonID=" . $row["seasonID"];
						$showDescriptor = "";
						$showTitle = "<a href=\"" . $showLink . "\"><b><i>" . $row["showName"] . "</i> S" . $row["seasonID"] . "</b></a>";
					} else {
						$showLink = "http://" . $_SERVER['HTTP_HOST']. "/program?id=" . $row["progID"];
						$showDescriptor = ($row["showName"] === null) ? "" : "<span class=\"show-descriptor\"><i>" . $row["showName"]. "</i> S" . 
							$row["seasonID"]. "E" . $row["epID"]. "</span><br/>";
						$showTitle = "<a href=\"" . $showLink . "\">" . $row["programName"] . "</a>";
					}
					
					echo "<tr class=\"table-entry\">
							<td>" . $showDescriptor . $showTitle . "</td>
							<td align=\"center\">";
					
					$authorSql = "SELECT C.reviewerID, C.name
									FROM Critic C, CriticWrites CW
									WHERE C.reviewerID = CW.reviewerID
										AND CW.reviewID = " . $row["reviewID"];
					$authorResult = $conn->query($authorSql);
					
					while ($innerRow = $authorResult->fetch_assoc()) {
						echo "<p><a href=\"http://" . $_SERVER['HTTP_HOST'] . "/critic?id=" . $innerRow['reviewerID'] . "\">
								" . $innerRow['name'] . "</p>";
					}
							
					echo "</td>
							<td style=\"width:200px\">" . $row['title'] . "</td>
							<td align=\"center\">" . $row['date'] . "</td>
							<td align=\"center\">" . $row['rating'] . "%</td>
						  </tr>";
				}
				echo "</table>";
			}
			
			$type = "Program";
			$result = $programResult;
		}
	} else {
		echo "<span class=\"content-header\"><h3>Reviews</h3></span>
			<p>Review listing is not available...</p>";
	}
}

if ($error) {
	echo "<p>Review listing is not available...</p>";
}
$conn->close();
?>
		</div>
		<div class="column-right">
<?php
$publicationID = isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
$selfURL = $_SERVER['PHP_SELF'] . "?id=" . $publicationID;

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
			<input type=\"hidden\" name=\"id\" value=\"" . $publicationID . "\">
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
		header('Location: ' . $selfURL);
	}
	
	$servername = "localhost:3306";
	$username = htmlspecialchars($_POST['username']);
	$password = htmlspecialchars($_POST['password']);
	$dbname = "untitled_review_website";
	
	echo "<div id=\"login-form\">
		  <span class=\"content-header\"><h3>Login</h3></span>
		  <form action=\"" . $_SERVER['PHP_SELF'] . "\" method=\"post\">
			<input type=\"hidden\" name=\"id\" value=\"" . $publicationID . "\">
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
				header('Location: ' . $selfURL);
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
	</body>
</html>