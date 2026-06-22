<?php
session_start();
session_destroy();
header('Location: /testing_system/index.php');
exit();
?>