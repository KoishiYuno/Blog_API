<?php

require_once('../controller/db.php');
require_once('../model/Response.php');

try{
  $writeDB = DB::connectionWriteDB();
  $readDB = DB::connectionReadDB();
  $response = new Response();
  $response->setSuccess(true);
  $response->setHttpStatusCode(200);
  $response->addMessage("Database cannot be connected");
  $response->send();

} catch(PDOException $ex) {
  $response = new Response();
  $response->setSuccess(false);
  $response->setHttpStatusCode(500);
  $response->addMessage("Database cannot be connected");
  $response->toCache(false);
  $response->send();
  exit;
}

?>
