<?php
require __DIR__ . '/../src/helpers/auth.php';
logoutUser();
header('Location: /mini-blog/public/login.php');
exit;
