<?php

require_once('db.php');
require_once('../model/Response.php');
require_once('../model/Blog.php'); 
require_once('./Response_controller.php');

header('Access-Control-Allow-Origin: *');

header('Access-Control-Allow-Methods: *');

header("Access-Control-Allow-Headers", "*");

if (isset($_SERVER['HTTP_ORIGIN'])) {

    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

    throwErrorMessage("Database connection faired",200);
}

try{
    //connect to the database;
    $writeDB = DB::connectionWriteDB();
    $readDB = DB::connectionReadDB();
} catch (PDOException $ex){
    //return the error message
    throwErrorMessage("Database connection faired",500);
    exit();
}

//   /blog/1/image/1/attributes
if(array_key_exists("blogid", $_GET) && array_key_exists("imageid", $_GET) && array_key_exists("attributes", $_GET)) {
    $blogid = $_GET['blogid'];
    $imageid = $_GET['imageid'];
    $attributes = $_GET['attributes'];

    if(!is_numeric($blogid) || !is_numeric($imageid)){
        throwErrorMessage("blog id and image id must be number",400);
        exit();
    }

    if($blogid == '' ||$imageid == ''){
        throwErrorMessage("blog id and image id cannot be null",400);
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){

    } elseif ($_SERVER['REQUEST_METHOD'] === 'PATH'){

    } else {
        throwErrorMessage("Not a valid request method",405);
    }

} 
//   /blog/1/image/1
elseif(array_key_exists("blogid", $_GET) && array_key_exists("imageid", $_GET)){
    $blogid = $_GET['blogid'];
    $imageid = $_GET['imageid'];

    if(!is_numeric($blogid) || !is_numeric($imageid)){
        throwErrorMessage("blog id and image id must be number",400);
        exit();
    }

    if($blogid == '' ||$imageid == ''){
        throwErrorMessage("blog id and image id cannot be null",400);
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){

    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE'){

    } else {
        throwErrorMessage("Not a valid request method",405);
    }
}

elseif(array_key_exists("blogid", $_GET) && !array_key_exists("imageid", $_GET)){
    if(!is_numeric($blogid) || !is_numeric($imageid)){
        throwErrorMessage("blog id and image id must be number",400);
        exit();
    }

    if($blogid == '' ||$imageid == ''){
        throwErrorMessage("blog id and image id cannot be null",400);
        exit();
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST'){

    } else {
        throwErrorMessage("Not a valid request method",405);
    }
}

