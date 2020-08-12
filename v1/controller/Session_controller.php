<?php 

require_once('db.php');
require_once('../model/Response.php');
require_once('Response_controller.php');
require_once('../vendor/autoload.php');

try{
    //connect to the database;
    $writeDB = DB::connectionWriteDB();
    $readDB = DB::connectionReadDB();
} catch (PDOException $ex){
    //return the error message
    throwErrorMessage("Database connection faired",500);
    exit();
}

//http://localhost:80/Blog_API/v1/session/$session
if(array_key_exists("sessionid",$_GET)){
    $id = $_GET['sessionid'];
    
    if($id === ''||!is_numeric($id)){
        throwErrorMessage("session id must be provided",404);
    }

    if(!isset($_SERVER['HTTP_AUTHORIZATION'])||strlen($_SERVER['HTTP_AUTHORIZATION'])<1){
        throwErrorMessage("The authorization must be provided",401);
        exit();
    }

    $accesstoken = $_SERVER['HTTP_AUTHORIZATION'];

    //HTTP DELETE METHOD: delete a session by ID
    if($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        try{
            $query = $writeDB->prepare('delete from session where id = :id and token = :token');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->bindParam(':token', $accesstoken, PDO::PARAM_STR);
            $query->execute();

            $count = checkQuerySuccess($query);

            $data = array();
            $data['session_id'] = intval($id);
            
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
    
    //
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH' ){

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
    
        if(!isset($jsonData->refreshToken)||strlen($jsonData->refreshToken) < 1){
            throwErrorMessage("Username and password must be contained",400);
            exit();
        }

        try{
            $refreshtoken = $jsonData->refreshToken;

            $query = $writeDB->prepare('select session.id as session_id, session.userid as user_id, token, refresh, active, attempt, expiry, refreshExpiry from user, session where user.id = session.userid and session.id = :id and refresh = :refresh');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->bindParam(':refresh', $refreshtoken, PDO::PARAM_STR);
            $query->execute();

            $count = checkQuerySuccess($query);
            $row = $query->fetch(PDO::FETCH_ASSOC);

            $session_id = $row['session_id'];
            $user_id = $row['user_id'];
            $token = $row['token'];
            $refresh = $row['refresh'];
            $active = $row['active'];
            $attempt = $row['attempt'];
            $expiry = $row['expiry'];
            $refreshExpiry = $row['refreshExpiry'];

            if($active === 'false'){
                throwErrorMessage("this user is not active",401);
                exit();
            }

            if($attempt >= 5){
                throwErrorMessage("This user has been lock down, please contact the admin",401);
                exit();
            }

            if(strtotime($refreshExpiry) < time()){
                throwErrorMessage("The refresh token has expired, please login again",401);
                exit();
            }

            $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
            $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

            $access_token_expiry_second = 1200;
            $refresh_token_expiry_second = 1209600;

            $query = $writeDB->prepare('update session set token = :token, expiry = date_add(NOW(), INTERVAL :expiry SECOND), refresh = :refresh, refreshExpiry = date_add(NOW(), INTERVAL :freshExpiry SECOND) where session.id = :id and session.userid = :userid');
            $query->bindParam(':token', $accesstoken, PDO::PARAM_STR);
            $query->bindParam(':expiry', $access_token_expiry_second, PDO::PARAM_INT);
            $query->bindParam(':refresh', $refreshtoken, PDO::PARAM_STR);
            $query->bindParam(':freshExpiry', $refresh_token_expiry_second, PDO::PARAM_INT);
            $query->bindParam(':id', $session_id, PDO::PARAM_INT);    
            $query->bindParam(':userid', $user_id, PDO::PARAM_INT);
            $query->execute();

            echo "NMSL";

            $count = checkQuerySuccess($query);
            $row = $query->fetch(PDO::FETCH_ASSOC);

            $data = array();
            $data['session_id'] = intval($session_id);
            $data['access_token'] = $accesstoken;
            $data['aceess_token_expiry'] = $access_token_expiry_second;
            $data['refresh_token'] = $refreshtoken;
            $data['refresh_token_expiry'] = $refresh_token_expiry_second;

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

    //handle unexcepted method
    else{
        throwErrorMessage("Not allowed request method",404);
    exit();
    }
}

//http://localhost:80/Blog_API/v1/session
elseif(empty($_GET)){
    if($_SERVER['REQUEST_METHOD'] != 'POST'){
        throwErrorMessage("Not an allowed request method",405);
        exit();
    }
    
    //delay all the request for 1s
    sleep(1);

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

    if(!isset($jsonData->username)||!isset($jsonData->password)){
        throwErrorMessage("Username and password must be contained",400);
        exit();
    }

    if(strlen($jsonData->username) < 1 || strlen($jsonData->username) > 255 || strlen($jsonData->password) < 8 || strlen($jsonData->password) > 255){
        throwErrorMessage("The parameter is invalid",400);
        exit();
    }

    try{

        $username = trim($jsonData->username);
        $passwordJSON = $jsonData->password;

        $query = $readDB->prepare('select * from user where username = :username');
        $query->bindParam(':username', $username, PDO::PARAM_STR);
        $query->execute();

        $count = checkQuerySuccess($query);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        $id = $row['id'];
        $name = $row['name'];
        $username = $row['username'];
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
            $query = $writeDB->prepare('update user set attempt = attempt + 1 where id = :id');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();

            throwErrorMessage("password is not correct",401);
            exit();
        }

        $accesstoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());
        $refreshtoken = base64_encode(bin2hex(openssl_random_pseudo_bytes(24)).time());

        $access_token_expiry_second = 1200;
        $refresh_token_expiry_second = 1209600;

    } catch (PDOException $ex){
        //return the error message
        throwErrorMessage("Database connection faired",500);
        exit();
    }

    try{
        $writeDB->beginTransaction();

        $query = $writeDB->prepare('update user set attempt = 0 where id = :id');
        $query->bindParam(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $query = $writeDB->prepare('insert into session (userid, token, expiry, refresh, refreshExpiry) values (:userid, :token, date_add(NOW(), INTERVAL :expiry SECOND), :refresh, date_add(NOW(), INTERVAL :freshExpiry SECOND))');      
        $query->bindParam(':userid', $id, PDO::PARAM_INT);
        $query->bindParam(':token', $accesstoken, PDO::PARAM_STR);
        $query->bindParam(':expiry', $access_token_expiry_second, PDO::PARAM_INT);
        $query->bindParam(':refresh', $refreshtoken, PDO::PARAM_STR);
        $query->bindParam(':freshExpiry', $refresh_token_expiry_second, PDO::PARAM_INT);    
        $query->execute();

        $id = $writeDB->lastInsertID();

        $writeDB->commit();

        $data = array();
        $data['session_id'] = intval($id);
        $data['access_token'] = $accesstoken;
        $data['aceess_token_expiry'] = $access_token_expiry_second;
        $data['refresh_token'] = $refreshtoken;
        $data['refresh_token_expiry'] = $refresh_token_expiry_second;

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

//Handle unexcepted endpoint
else{
    throwErrorMessage("Not allowed request method",404);
    exit();
}

?>

      