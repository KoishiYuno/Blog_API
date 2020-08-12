<?php

class UserException extends Exception{ }

class User{
    private $_id;
    private $_name;
    private $_username;
    private $_password;
    private $_email;

    public function __construct($id,$name,$username,$password,$email){
        $this->setID($id);
        $this->setName($name);
        $this->setUsername($username);
        $this->setPassword($password);
        $this->setEmail($email);
    }
    
    public function getID(){
        return $this->_id;
    }
    public function getName(){
        return $this->_name;
    }
    public function getUsername(){
        return $this->_username;
    }
    public function getPassword(){
        return $this->_password;
    }
    public function getEmail(){
        return $this->_email;
    }
    
    public function setID($id){
        if(($id !==null) && (!is_numeric($id) || $id < 0 || $this->_id !== null)){
            throw new UserException('User id is not avaliable');
        }
    
        $this->_id = $id;
    }
    public function setName($name){
        if(strlen($name) > 255 || strlen($name) <= 0){
            throw new UserException('name is not valid');
        }
    
        $this->_name = $name;
    }
    public function setUsername($username){
        if(strlen($username) === 0){
            throw new UserException('Username is not valid');
        }
    
        $this->_username = $username;
    }
    public function setPassword($password){
        if(strlen($password) === 0){
            throw new UserException('User password is not valid');
        }
    
        $this->_password = $password;
    }
    public function setEmail($email){
        if($email === null){
            throw new UserException('User email is not valid');
        }
        $this->_email = $email;
    }
}




 ?>
