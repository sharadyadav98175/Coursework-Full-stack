<?php

  $mysqli = new mysqli("localhost","2413835","@Sharad12345","db2413835");

  if ($mysqli -> connect_errno) {
    echo "Failed to connect to MySQL: " . $mysqli -> connect_error;
    exit();
  }
?>
