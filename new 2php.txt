<!doctype html>
<html lang="en">
<body>
<h1>List of ALL my games!!!</h1>
<?php
// Connect to database
include("db.php");
// Run SQL query
$sql = "SELECT * FROM videogames ORDER BY released_date";
$results = mysqli_query($mysqli, $sql);
?>
<table>
<?php while($a_row = mysqli_fetch_assoc($results)):?>
<tr>
<td><?=$a_row['game_name']?></td>
<td><?=$a_row['rating']?></td>
</tr>
<?php endwhile;?>
</table>
</body>
</html>