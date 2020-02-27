<?php
require_once('../class.php');
if (!empty($_POST['username'])) {
    //Hidden form was filled in...
    //Do anything EXCEPT attempt register
    exit;
}
if (isset($_POST['THE_username']) && isset($_POST['THE_password']) && isset($_POST['THE_email'])) {
    $register = new doRegisterAttempt($_POST['THE_username'], $_POST['THE_password'], $_POST['THE_email']);
    echo $register->attemptRegister();
} else {
    echo "None of Username, Password or Email can be empty";
}