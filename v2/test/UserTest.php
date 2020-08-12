<?php 

require_once('../model/User.php');

try{
    $user = new User(1, "1q", "1a", "1", "1");
    header('Content-type: application/json; charset=utf-8');
    echo $user->getUsername();

}catch(UserException $ex){
    echo "erro ".$ex.getMessage();
}
?>