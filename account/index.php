<?php
require_once('../class.php');
$session = new sessionManage();
$logged_in = $session->checkIsLoggedIn(true, '' . configAndConnect::URL . 'login/');//If not logged redirect to login page
$is_activated = $session->IsAccountActivated();
if ($is_activated) {//Account is activated
    $user_details = new accountDetails();
    $user_details_array = $user_details->accountData();//Array for user account details
    $html = new htmlStructure();
    $html->outputString("<title>{$user_details_array['username']}'s account</title>");
    $html->outputString("Welcome {$user_details_array['username']}<br>");
    $html->outputString("Your user id: {$user_details_array['uid']}<br>");
    $html->outputString("Account created: {$user_details_array['created']}<br>");
    $html->outputString("Account email: {$user_details_array['email']}<br>");
    $html->outputString("Logged in: {$user_details_array['login_count']} times<br>");
    $html->outputString("Failed login attempts: {$user_details_array['login_fails']}<br>");
    $html->outputString("Last Failed login attempt: {$user_details_array['last_fail']}<br>");
    $html->outputString("<a href='../logout/'>Logout</a>");
} else {
    $html->outputString("Please check for activation email");
}