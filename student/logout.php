<?php
session_start();
session_unset();
session_destroy();
header('Location: cl-login.php');
exit;
