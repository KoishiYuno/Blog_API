<?php 
header("Access-Control-Allow-Method: *");
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

require_once('db.php');
require_once('../model/Response.php');
require_once('Response_controller.php');

try{
    //connect to the database;
    $writeDB = DB::connectionWriteDB();
    $readDB = DB::connectionReadDB();
} catch (PDOException $ex){
    //return the error message
    throwErrorMessage("Database connection faired",500);
    exit();
}

//http://localhost:80/Blog_API/v1/signup
//HTTP GET method: create a new user in user table
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    throwErrorMessage("Not an allowed request method",405);
    exit();
}

//check if the request body is in JSON
if($_SERVER['CONTENT_TYPE'] !== 'application/json'){
    throwErrorMessage("The content must be JSON type",400);
    exit();
}

$postData = file_get_contents('php://input');
$jsonData = json_decode($postData);

//check if JSON is valid
if(!$jsonData){
    throwErrorMessage("The JSOn object is not valid",400);
    exit();
}

if(!isset($jsonData->name)||!isset($jsonData->username)||!isset($jsonData->password)){
    throwErrorMessage("The JSOn object misses required parameter",400);
    exit();
}

//vaLidate parameters
if(strlen($jsonData->name) < 1 || strlen($jsonData->name) > 255 || strlen($jsonData->password) < 8 || strlen($jsonData->password) > 255){
    throwErrorMessage("The parameter is invalid",400);
    exit();
}

$name = trim($jsonData->name);
$username = trim($jsonData->username);
$password = $jsonData->password;

try{
    //check if the user name is exit in user table
    $query = $readDB->prepare('select id from user where username = :username');
    $query->bindParam(':username', $username, PDO::PARAM_INT);
    $query->execute();

    $count = $query->rowCount();

    //throw error message for no rows are found in database
    if($count !== 0){
        throwErrorMessage("Content not found",404);
        exit();
    }

    //hash the password 
    $passwordHashed = password_hash($password, PASSWORD_DEFAULT);

    $query = $readDB->prepare('insert into user (name, username, password) values (:name, :username, :password)');
    $query->bindParam(':username', $username, PDO::PARAM_STR);
    $query->bindParam(':name', $name, PDO::PARAM_STR);
    $query->bindParam(':password', $passwordHashed, PDO::PARAM_STR);    
    $query->execute();

    $count = checkQuerySuccess($query);
    

    $id = $writeDB->lastInsertID();

    $data['id'] = $id;
    $data['username'] = $username;
    $data['name'] = $name;

    returnData(200,$data,true);

} catch(BlogException $ex){
    echo "erro ".$ex->getMessage();
    throwErrorMessage("Service object process faired",500);
    exit();

} catch(PDOException $ex){
    echo "erro ".$ex;
    throwErrorMessage("Service database process faired",500);
    exit();
}
?>