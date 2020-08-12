<?php 

require_once('../model/Blog.php');

try{
    $blog = new Blog(1, "title", "author",  new DateTime(),  "description", "type",  "image", "content");
    header('Content-type: application/json; charset=utf-8');
    echo json_encode($blog->returnBlogAsArray());

}catch(BlogException $ex){
    echo "erro ".$ex.getMessage();
}
?>