<?php
$password = 'admin';
$hash = password_hash($password, PASSWORD_BCRYPT);
echo "Hash gerado: " . $hash . PHP_EOL;
