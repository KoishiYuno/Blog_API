<?php

require_once('../model/image.php');

try{
    $image = new Image(1,"nmsl","wsnd.jpg","image/jpeg",3);
    header('Content-type: application/json; charset = UTF-8');
    echo json_encode($image->returnImageAsArray());
}catch(imageException $ex){
    echo "erro: ". $ex->getMessage();
}