<?php
require __DIR__ . "/../includes/auth.php";
deconnecter();
header("Location: login.php");
exit;
