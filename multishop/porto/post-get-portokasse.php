<?php
require_once('config.php');
require_once('post-php-class.php');
$internetmarke = new PostInternetmarke();
$internetmarke->get_portokasse();

?>
