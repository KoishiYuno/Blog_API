<?php 

require_once('Response_controller.php');
require_once('db.php');
require_once('../model/Blog.php');
require_once('../model/Response.php');

try{
    //connect to the database;
    $writeDB = DB::connectionWriteDB();
    $readDB = DB::connectionReadDB();
    
}catch(PDOException $ex){
    //return the error message
    throwErrorMessage("Database connection faired",500);
    exit();
}

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


//http://localhost:80/Blog_API/v1/blog/$id
if(array_key_exists("blogid",$_GET)){
    //get blog id from url 
    $blog_id = $_GET['blogid'];
    
    //chekc if the blog id is valid
    if($blog_id == '' || !is_numeric($blog_id)){
        throwErrorMessage("Blog ID can not be blanked and it must be a INT",400);
        exit;
    }

    //HTTP GET method: get a specific blog from database by ID
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
       
        try{
            $query = $writeDB->prepare('update blogs set view = view + 1 WHERE blogs.id = :blogid;');
            $query->bindParam(':blogid', $blog_id, PDO::PARAM_INT);
            $query->execute();

            
            $query = $readDB->prepare('select * from blogs where id = :blogid');
            $query->bindParam(':blogid', $blog_id, PDO::PARAM_INT);
            $query->execute();

            $count = checkQuerySuccess($query);

            
            while($row = $query -> fetch(PDO::FETCH_ASSOC)){
                $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content'],  $row['view']);

                $BlogArray[]=$Blog->returnBlogAsArray();
            }
            $data['count'] = $count;
            $data['data'] = $BlogArray;

            returnData(200,$data,true);
        }
        catch(BlogException $ex){
            echo "erro ".$ex->getMessage();
            throwErrorMessage("Service object process faired",500);
            exit();

        }catch(PDOException $ex){
            echo "erro ".$ex;
            throwErrorMessage("Service database process faired",500);
            exit();
        }
    }
    //HTTP DELETE METHOD: delete a specific blog from database by ID
    elseif($_SERVER['REQUEST_METHOD'] === 'DELETE'){
        try{
            $query = $writeDB->prepare('delete from blogs where id = :blogid');
            $query->bindParam(':blogid', $blog_id, PDO::PARAM_INT);
            $query->execute();

            checkQuerySuccess($query);

            returnData(200,"delete success",true);

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
    //HTTP PATCH METHOD: update a specific blog from database by ID
    elseif($_SERVER['REQUEST_METHOD'] === 'PATCH'){
        try{
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

            //check if JSON miss require parameters
            if(!isset($jsonData->title)||!isset($jsonData->author)||!isset($jsonData->type)||!isset($jsonData->image)||!isset($jsonData->description)||!isset($jsonData->content)){
                if(!isset($jsonData->title)){
                    returnData(400,$jsonData,true);
                    exit();
                }
                if(!isset($jsonData->author)){
                    throwErrorMessage("The JSOn object misses author",400);
                    exit();
                }
                if(!isset($jsonData->type)){
                    throwErrorMessage("The JSOn object misses type",400);
                    exit();
                }
                if(!isset($jsonData->description)){
                    throwErrorMessage("The JSOn object misses description",400);
                    exit();
                }
                if(!isset($jsonData->image)){
                    throwErrorMessage("The JSOn object misses image",400);
                    exit();
                }
                if(!isset($jsonData->content)){
                    throwErrorMessage("The JSOn object misses content",400);
                    exit();
                }
            }

            $blog = new Blog(null, $jsonData->title, $jsonData->author, $jsonData->date,$jsonData->description,  $jsonData->type, $jsonData->image, $jsonData->content, $jsonData->view);
            if(!$blog){
                throwErrorMessage("Parameter in JSON parameter is not able to create a new object",400);
                exit();
            }

            $id = $blog_id;
            $title = $blog->getTitle();
            $author = $blog->getAuthor();
            $date = $blog->getDate();
            $description = $blog->getDescription();
            $type = $blog->getType();
            $image = $blog->getImage();
            $content = $blog->getContent();

            $query = $writeDB->prepare("update blogs set name = :blogname, author = :blogauthor, date = :blogdate, description = :blogdescription, type = :blogtype, image = :blogimage, content = :blogcontent where id = :blogid");
            $query->bindParam(':blogname', $title, PDO::PARAM_STR);
            $query->bindParam(':blogauthor', $author, PDO::PARAM_STR);
            $query->bindParam(':blogdate', $date, PDO::PARAM_STR);
            $query->bindParam(':blogdescription', $description, PDO::PARAM_STR);
            $query->bindParam(':blogtype', $type, PDO::PARAM_STR);
            $query->bindParam(':blogimage', $image, PDO::PARAM_STR);
            $query->bindParam(':blogcontent', $content, PDO::PARAM_STR);
            $query->bindParam(':blogid', $id, PDO::PARAM_INT);
            $query->execute();

            $query = $readDB->prepare('select * from blogs where id = :blogid');
            $query->bindParam(':blogid', $id, PDO::PARAM_INT);
            $query->execute();

            $count = checkQuerySuccess($query);
            
            while($row = $query -> fetch(PDO::FETCH_ASSOC)){
                $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content'], $row['view']);

                $BlogArray[]=$Blog->returnBlogAsArray();
            }
            $data['counts'] = $count;
            $data['datas'] = $BlogArray;

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
    //handle other illegel HTTP METHOD
    else{
        throwErrorMessage("This HTTP request method is not allowed in this API", 405);
    }
}

else if(array_key_exists("popular",$_GET)){

    $limite = 3;

    //HTTP GET method: get all blog from database with pagination
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
       
        try{
            $offset = 0;

            $query = $readDB->prepare('select * from blogs limit :limit offset :offset');
            $query->bindParam(':limit', $limite, PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->execute();

            $count = checkQuerySuccess($query);
            
            while($row = $query -> fetch(PDO::FETCH_ASSOC)){
                $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content'], $row['view']);

                $BlogArray[]=$Blog->returnBlogAsArray();
            }
            $data['count'] = $count;
            $data['data'] = $BlogArray;

            returnData(200,$data,true);
        }
        catch(BlogException $ex){
            echo "erro ".$ex->getMessage();
            throwErrorMessage("Service object process faired",500);
            exit();

        }catch(PDOException $ex){
            echo "erro ".$ex;
            throwErrorMessage("Service database process faired",500);
            exit();
        }
    }

}

else if(array_key_exists("allPage",$_GET)){
    try{
        $query = $readDB->prepare('select count(*) as allPage from blogs');
        $query->execute();

        $count = checkQuerySuccess($query);
        
        while($row = $query -> fetch(PDO::FETCH_ASSOC)){
            $count = $row['allPage'];
        }
        $data['count'] = $count;

        returnData(200,$data,true);
    }
    catch(BlogException $ex){
        echo "erro ".$ex->getMessage();
        throwErrorMessage("Service object process faired",500);
        exit();

    }catch(PDOException $ex){
        echo "erro ".$ex;
        throwErrorMessage("Service database process faired",500);
        exit();
    }
}

else if(array_key_exists("search",$_GET)){
    $title = $_GET['search'];

    if($title == '' || !is_string($title)){
        throwErrorMessage("type can not be blanked and it must be a STRING",400);
        exit();
    }
    $title = "%".$title."%";
    try{
        $query = $readDB->prepare("select * from blogs WHERE name LIKE :title");
        $query->bindParam(':title', $title, PDO::PARAM_INT);
        $query->execute();

        $count = checkQuerySuccess($query);
        
        while($row = $query -> fetch(PDO::FETCH_ASSOC)){
            $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content'], $row['view']);

            $BlogArray[]=$Blog->returnBlogAsArray();
        }
        $data['count'] = $count;
        $data['data'] = $BlogArray;

        returnData(200,$data,true);
    }
    catch(BlogException $ex){
        echo "erro ".$ex->getMessage();
        throwErrorMessage("Service object process faired",500);
        exit();

    }catch(PDOException $ex){
        echo "erro ".$ex;
        throwErrorMessage("Service database process faired",500);
        exit();
    }
}

//http://localhost:80/Blog_API/v1/blog/type/$type
else if(array_key_exists("type",$_GET)){
    //get type from url 
    $type = $_GET['type'];
    

    //chekc if the type is valid
    if($type == '' || !is_string($type)){
        throwErrorMessage("type can not be blanked and it must be a STRING",400);
        exit();
    }

    //HTTP GET method: get all same type blogs from database
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
       
        try{
            
            $query = $readDB->prepare('select * from blogs where type = :type');
            $query->bindParam(':type', $type, PDO::PARAM_INT);
            $query->execute();

            $count = checkQuerySuccess($query);
            
            while($row = $query -> fetch(PDO::FETCH_ASSOC)){
                $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content']);

                $BlogArray[]=$Blog->returnBlogAsArray();
            }
            $data['count'] = $count;
            $data['data'] = $BlogArray;

            returnData(200,$data,true);
        }
        catch(BlogException $ex){
            echo "erro ".$ex->getMessage();
            throwErrorMessage("Service object process faired",500);
            exit();

        }catch(PDOException $ex){
            echo "erro ".$ex;
            throwErrorMessage("Service database process faired",500);
            exit();
        }
    }
    //handle all other unexcepted HTTP method
    else{
        throwErrorMessage("This HTTP request method is not allowed in this API", 405);
    }
}

//http://localhost:80/Blog_API/v1/blog/page/$page
else if(array_key_exists("page",$_GET)){
    //get page from url 
    $page = $_GET['page'];
    
    //chekc if the page is valid
    if($page == '' || $page === 0 || !is_numeric($page)){
        throwErrorMessage("paged can not be blanked, cannot equal to 0 and it must be a INT",400);
        exit;
    }
    $limite = 5;

    //HTTP GET method: get all blog from database with pagination
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
       
        try{
            
            $query = $readDB->prepare('select count(id) as total from blogs');
            $query->execute();

            $row = $query -> fetch(PDO::FETCH_ASSOC);
            $total = intval($row['total']);
            $totalPage = ceil($total/$limite);

            if($totalPage < $page){
                throwErrorMessage("The page that you requested is exceed the total number of page");
                exit();
            }

            $offset = ($page == 1 ? 0 : ($limite * ($page - 1)));

            $query = $readDB->prepare('select * from blogs limit :limit offset :offset');
            $query->bindParam(':limit', $limite, PDO::PARAM_INT);
            $query->bindParam(':offset', $offset, PDO::PARAM_INT);
            $query->execute();

            $count = checkQuerySuccess($query);
            
            while($row = $query -> fetch(PDO::FETCH_ASSOC)){
                $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content'], $row['view']);

                $BlogArray[]=$Blog->returnBlogAsArrayNoContent();
            }
            $data['count'] = $count;
            $data['data'] = $BlogArray;

            returnData(200,$data,true);
        }
        catch(BlogException $ex){
            echo "erro ".$ex->getMessage();
            throwErrorMessage("Service object process faired",500);
            exit();

        }catch(PDOException $ex){
            echo "erro ".$ex;
            throwErrorMessage("Service database process faired",500);
            exit();
        }
    }
}

//http://localhost:80/Blog_API/v1/blog
else if(empty($_GET)){
    //HTTP GET method: get all blogs from database
    if($_SERVER['REQUEST_METHOD'] === 'GET'){
       
        try{
            $query = $readDB->prepare('select * from blogs');
            $query->bindParam(':blogid', $blog_id, PDO::PARAM_INT);
            $query->execute();

            $count = checkQuerySuccess($query);
            
            while($row = $query -> fetch(PDO::FETCH_ASSOC)){
                $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content'], $row['view']);

                $BlogArray[]=$Blog->returnBlogAsArray();
            }
            $data['count'] = $count;
            $data['data'] = $BlogArray;

            returnData(200,$data,true);
        }
        catch(BlogException $ex){
            echo "erro ".$ex->getMessage();
            throwErrorMessage("Service object process faired",500);
            exit();

        }catch(PDOException $ex){
            echo "erro ".$ex;
            throwErrorMessage("Service database process faired",500);
            exit();
        }
    }

    //HTTP GET method: insert blogs to database
    else if($_SERVER['REQUEST_METHOD'] === 'POST'){
        try{
            //check if the body content from request is JSON
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

            //check if JSON miss require parameters
            if(!isset($jsonData->title)||!isset($jsonData->author)||!isset($jsonData->date)||!isset($jsonData->type)||!isset($jsonData->image)||!isset($jsonData->description)||!isset($jsonData->content)){
                throwErrorMessage("The JSOn object misses required parameter",400);
                exit();
            }

            $blog = new Blog(null, $jsonData->title, $jsonData->author, $jsonData->date,$jsonData->description,  $jsonData->type, $jsonData->image, $jsonData->content, 0);
            if(!$blog){
                throwErrorMessage("Parameter in JSON parameter is not able to create a new object",400);
                exit();
            }

            $query = $readDB->prepare('select max(id) as total from blogs');
            $query->execute();

            $row = $query -> fetch(PDO::FETCH_ASSOC);
            $total = intval($row['total'])+1;

            $title = $blog->getTitle();
            $author = $blog->getAuthor();
            $date = new DateTime();
            $date = $date->format('Y-m-d H:i:s');
            $description = $blog->getDescription();
            $type = $blog->getType();
            $image = $blog->getImage();
            $content = $blog->getContent();

            $query = $writeDB->prepare("insert into blogs (id, name, author, date, description, type, image, content) VALUES (:blogid, :blogname, :blogauthor, :blogdate, :blogdescription, :blogtype, :blogimage, :blogcontent)");
            $query->bindParam(':blogname', $title, PDO::PARAM_STR);
            $query->bindParam(':blogauthor', $author, PDO::PARAM_STR);
            $query->bindParam(':blogdate', $date, PDO::PARAM_STR);
            $query->bindParam(':blogdescription', $description, PDO::PARAM_STR);
            $query->bindParam(':blogtype', $type, PDO::PARAM_STR);
            $query->bindParam(':blogimage', $image, PDO::PARAM_STR);
            $query->bindParam(':blogcontent', $content, PDO::PARAM_STR);
            $query->bindParam(':blogid', $total, PDO::PARAM_INT);
            $query->execute();

            $query = $readDB->prepare('select * from blogs where id = :blogid');
            $query->bindParam(':blogid', $total, PDO::PARAM_INT);
            $query->execute();

            $count = checkQuerySuccess($query);
            
            while($row = $query -> fetch(PDO::FETCH_ASSOC)){
                $Blog = new Blog($row['id'], $row['name'], $row['author'],$row['date'],  $row['description'], $row['type'],  $row['image'], $row['content'], $row['view']);

                $BlogArray[]=$Blog->returnBlogAsArray();
            }
            $data['counts'] = $count;
            $data['datas'] = $BlogArray;

            returnData(200,$data,true);
        }
        catch(BlogException $ex){
            
            throwErrorMessage("erro ".$ex->getMessage(),500);
            exit();

        }catch(PDOException $ex){
            
            throwErrorMessage("erro ".$ex->getMessage() ,500);
            exit();
        }
    }

    //handle all other unexcepted HTTP method
    else{
        throwErrorMessage("This HTTP request method is not allowed in this API", 405);
    }
}

//Handle other unpredic wrong endpoints
else{
    throwErrorMessage("This endpoint is not exist", "404");
}

//Throw error message from API

?> 