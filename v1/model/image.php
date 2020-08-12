<?php

class imageException extends Exception { }

class Image {
    private $_id;
    private $_title;
    private $_filename;
    private $_mimetype;
    private $_blogid;
    private $_location;

    public function __construct($id, $title, $filename, $mimetype, $blogid){
        $this->setID($id);
        $this->setTitle($title);
        $this->setFilename($filename);
        $this->setMimetype($mimetype);
        $this->setBlogid($blogid);
        $this->_location = "../../../../blogimagesforbayleys/";
    }

    public function getID(){
        return $this->_id;
    }
    public function getTitle(){
        return $this->_title;
    
    }
    public function getFilename(){
        return $this->_filename;
    }
    public function getFileExtension(){
        $filenameParts = explode(".", $this->_filename);
        $lastArrayElement = count($filenameParts)-1;
        $fileExtension = $filenameParts[$lastArrayElement];
        return $fileExtension;
    }
    public function getMimetype(){
        return $this->_mimetype;
    }
    public function getLocation(){
        return $this->_location;
    }
    public function getBlogid(){
        return $this->_blogid;
    }
    public function getUrl(){
        $prefix = (isset($_SERVE['HTTPS']) && $_SERVE['HTTPS'] === on?"https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $url = "/v1/blog/".$this->getBlogid()."/image/".$this->getID();

        return $prefix."://".$host.$url;
    }

    public function setID($id){
        if(($id !==null) && (!is_numeric($id) || $id < 0 || $this->_id !== null)){
            throw new imageException('image id is not avaliable');
        }

        $this->_id = $id;
    }
    public function setTitle($title){
        if(strlen($title) > 255 || strlen($title) <= 0){
            throw new imageException('image title is not valid');
        }

        $this->_title = $title;
    }
    public function setFilename($filename){
        if (strlen($filename) < 1 || strlen($filename) > 255 || preg_match("/^[a-zA-Z0-9_-]+(.jpg|.gif|.png)$/", $filename) != 1){
            throw new imageException("invalid image filename");
        }

        $this->_filename = $filename;
    }
    public function setMimetype($mimetype){
        if(strlen($mimetype) > 255 || strlen($mimetype) <= 0){
            throw new imageException('image mimetype is not valid');
        }

        $this->_mimetype = $mimetype;
    }
    public function setBlogid($blogid){
        if(($id !==null) && (!is_numeric($id) || $id < 0 || $this->_id !== null)){
            throw new imageException('image id is not avaliable');
        }
        
        $this->_blogid = $blogid;
    }

    public function returnImageAsArray(){
        $image = array();
        $image['id'] = $this->getID();
        $image['title'] = $this->getTitle();
        $image['filename'] = $this->getFilename();
        $image['Mimetype'] = $this->getMimetype();
        $image['blogid'] = $this->getBlogid();
        $image['url'] = $this->getUrl();

        return $image;
    }

}