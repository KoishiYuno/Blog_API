<?php 
header("Access-Control-Allow-Method: *");
header("Access-Control-Allow-Origin: *");
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

require_once('db.php');
require_once('../model/Response.php');
require_once('Response_controller.php');
require '../vendor/autoload.php';
use \Firebase\JWT\JWT;

//delay all the request for 1s
sleep(1);

if (isset($_SERVER['HTTP_ORIGIN'])) {

    header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400');    // cache for 1 day
}

// Access-Control headers are received during OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
        // may also be using PUT, PATCH, HEAD etc
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

    if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
        header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

    exit(0);
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

//HTTP DELETE METHOD: delete a session by ID and access token
if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
    $id = trim($jsonData->id);
    $access_token = trim($jsonData->access_token);
    try{
        $query = $writeDB->prepare('update jwt_user set access = null, refresh = null where id = :id and access = :access_token');
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->bindParam(':access_token', $access_token, PDO::PARAM_STR);
        $query->execute();

        $count = checkQuerySuccess($query);

        $data = array();
        $data['session_id'] = intval($id);
        $data['message'] = "Success logout";
        
        returnData(200,$data,true);

    } catch(BlogException $ex){
        echo "erro ".$ex->getMessage();
        throwErrorMessage("Service object process faired",500);
        exit();
    
    } catch(PDOException $ex){
        $writeDB->rollBack();
        echo "erro ".$ex;
        throwErrorMessage("Service database process faired",500);
        exit();
    }
}
//HTTP PATCH METHOD: renew access token and refresh token (handle permanent login)
else if($_SERVER['REQUEST_METHOD'] === 'PATCH'){
    $postData = file_get_contents('php://input');
    $jsonData = json_decode($postData);
    
    //check if JSON is valid
    if(!$jsonData){
        throwErrorMessage("The JSOn object is not valid",400);
        exit();
    }

    if(!isset($jsonData->access_token)){
        throwErrorMessage("API call missed required parameter(s)",400);
        exit();
    }

    try{
        $access_token = $jsonData->access_token;

        $query = $writeDB->prepare('select * from jwt_user where access = :access_token');
        $query->bindParam(':access_token', $access_token, PDO::PARAM_STR);
        $query->execute();

        $count = $query->rowCount();

        //throw error message for no rows are found in database
        if($count === 0){
            throwErrorMessage("this token is not exist",400);
            exit();
        }
        $key = "The super secret key";

        $jwt_data = JWT::decode($access_token, $key, array('HS256'));
        if($jwt_data->exp > time()){
            $data = array();
            $data['access_token'] = $access_token;
            $data['data'] = true;
            
            returnData(200,$data,true);
            exit();
        }

        $row = $query->fetch(PDO::FETCH_ASSOC);

        $id = $row['id'];
        $username = $row['username'];
        $access = $row['access'];
        $refresh = $row['refresh'];
        $active = $row['active'];
        $attempt = $row['attempt'];
        $email = $row['email'];

        if($active === 'false'){
            throwErrorMessage("this user is not active",401);
            exit();
        }

        if($attempt >= 5){
            throwErrorMessage("This user has been lock down, please contact the admin",401);
            exit();
        }

        $key = "The super secret key";
        $jwt_data = JWT::decode($refresh, $key, array('HS256'));

        //check if the refresh token is expiray
        if($jwt_data->exp < time()){
            throwErrorMessage("The refresh token has expired, please login again",401);
            exit();
        }

        //create JWT token for access token
        $access_token = generateJWT($id, $username, $email, (60*60*3));

        //create JWT token for refresh token
        $refresh_token = generateJWT($id, $username, $email, (60*60*24*1.5));

        $query = $writeDB->prepare('update jwt_user set access = :access_token, refresh = :refresh_token where id = :id and username = :username');
        $query->bindParam(':access_token', $access_token, PDO::PARAM_STR);
        $query->bindParam(':refresh_token', $refresh_token, PDO::PARAM_STR);
        $query->bindParam(':id', $id, PDO::PARAM_INT);    
        $query->bindParam(':username', $username, PDO::PARAM_INT);
        $query->execute();

        //return the result
        $data = array();
        $data['access_token'] = $access_token;
        $data['data'] = true;
        
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

}
//HTTP POST METHOD: log into the system, generate new access token and refresh token
else if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(!isset($jsonData->password)||!isset($jsonData->username)){
        returnData(200,$jsonData,true);
        exit();
    }

    if(strlen($jsonData->username) < 1 || strlen($jsonData->username) > 255 || strlen($jsonData->password) < 8 || strlen($jsonData->password) > 255){
        throwErrorMessage("The parameter is invalid",400);
        exit();
    }
    $username = trim($jsonData->username);
    $passwordJSON = $jsonData->password;

    try{
        $query = $readDB->prepare('select * from jwt_user where username = :username');
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();

        $count = checkQuerySuccess($query);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        $id = $row['id'];
        $name = $row['name'];
        $username = $row['username'];
        $email = $row['email'];
        $password = $row['password'];
        $active = $row['active'];
        $attempt = $row['attempt'];

        if($active !== 'true'){
            throwErrorMessage("This user is not active",401);
            exit();
        }

        if($attempt >= 5){
            throwErrorMessage("User account is locked out, please contact admin to unlock",401);
            exit();
        }
        
        if(!password_verify($passwordJSON, $password)){
            $query = $writeDB->prepare('update jwt_user set attempt = attempt + 1 where id = :id');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();

            throwErrorMessage("password is not correct",401);
            exit();
        }

        $query = $writeDB->prepare('update jwt_user set attempt = 0 where id = :id');
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        //create JWT token for access token
        $access_token = generateJWT($id, $username, $email, (60*60*3));

        //create JWT token for refresh token
        $refresh_token = generateJWT($id, $username, $email, (60*60*24*1.5));

        //store the access token and refresh token into the database
        $query = $writeDB->prepare('update jwt_user set access = :access_token, refresh = :refresh_token where id = :id and username = :username');
        $query->bindParam(':access_token', $access_token, PDO::PARAM_STR);
        $query->bindParam(':refresh_token', $refresh_token, PDO::PARAM_STR);
        $query->bindParam(':id', $id, PDO::PARAM_INT);    
        $query->bindParam(':username', $username, PDO::PARAM_INT);
        $query->execute();

        //return the result
        $data = array();
        $data['id'] = intval($id);
        $data['username'] = intval($username);
        $data['access_token'] = $access_token;
        $data['refresh_token'] = $refresh_token;
        
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
}

function generateJWT($id, $username, $email, $duration){
    $payload = array(
        "iss"=> "localhost",
        "iat"=> time(),
        "nbf"=> time() + 10,
        "exp"=> time() + $duration,
        "aud"=> "users",
        "data"=> array(
            "id"=> $id,
            "username"=> $username,
            "email" => $email
        )
    );
    $key = "The super secret key";
    $access_token = JWT::encode($payload, $key);

    return $access_token;
}

?>