<<?php

//This file is only used to test the Response.php

require_once('../model/Resonse.php');

$response = new Response();
$response->setSuccess(true);
$response->setHttpStatusCode(200);
$response->addMessage("NMSLWSND");
$response->toCache(true);
$response->send();

?>
