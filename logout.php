<?php
include('includes/config.php');
$_SESSION['logout_message'] = 'You have been signed out.';
$model->logout();

?>