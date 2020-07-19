<?php
require_once('../class.php');
$session = new sessionManage();
$session->redirectIfLoggedIn();//If logged in redirect to account page
$html = new htmlStructure();
$html->outputString('<html lang="en">');
$html->outputString("<head>");
$html->outputString("<title>Login</title>");
$html->outputString("<style>.username {display: none}</style>");
$html->outputString("</head>");
$html->outputString("<body>");
$html->loginForm();
$html->outputString("</body>");
$html->outputString("</html>");