<?php

class BlogException extends Exception{ }

class Blog{
    private $_id;
    private $_title;
    private $_author;
    private $_date;
    private $_description;
    private $_type;
    private $_image;
    private $_content;
    private $_view;

    public function __construct($id, $title, $author,  $date,  $description, $type,  $image, $content, $view){
        $this->setID($id);
        $this->setTitle($title);
        $this->setDescription($description);
        $this->setAuthor($author);
        $this->setDate($date);
        $this->setType($type);
        $this->setImage($image);
        $this->setContent($content);
        $this->setView($view);
    }

    public function getID(){
        return $this->_id;
    }
    public function getTitle(){
        return $this->_title;
    }
    public function getAuthor(){
        return $this->_author;
    }
    public function getDate(){
        return $this->_date;
    }
    public function getDescription(){
        return $this->_description;
    }
    public function getType(){
        return $this->_type;
    }
    public function getImage(){
        return $this->_image;
    }
    public function getContent(){
        return $this->_content;
    }

    public function getView(){
        return $this->_view;
    }

    public function setID($id){
        if(($id !==null) && (!is_numeric($id) || $id < 0 || $this->_id !== null)){
            throw new BlogException('Blog id is not avaliable');
        }

        $this->_id = $id;
    }
    public function setTitle($title){
        if(strlen($title) > 255 || strlen($title) <= 0){
            throw new BlogException('Blog title is not valid');
        }

        $this->_title = $title;
    }
    public function setDescription($description){
        if(strlen($description) === 0){
            throw new BlogException('Blog description is not valid');
        }

        $this->_description = $description;
    }
    public function setAuthor($author){
        if(strlen($author) === 0){
            throw new BlogException('Blog author is not valid');
        }

        $this->_author = $author;
    }
    public function setDate($date){
        if($date === null){
            throw new BlogException('Blog date is not valid');
        }

        $this->_date = $date;
    }
    public function setType($type){
        if(strlen($type) === 0){
            throw new BlogException('Blog type is not valid');
        }

        $this->_type = $type;
    }
    public function setImage($image){
        if(strlen($image) === 0){
            throw new BlogException('Blog image is not valid');
        }

        $this->_image = $image;
    }
    public function setContent($content){
        if(strlen($content) === 0){
            throw new BlogException('Blog content is not valid');
        }

        $this->_content = $content;
    }

    public function setView($view){
        $this->_view = $view;
    }

    //return data which is easy to be used in JSON
    public function returnBlogAsArray(){
        $blog = array();
        $blog['id'] = $this->getID();
        $blog['title'] = $this->getTitle();
        $blog['description'] = $this->getDescription();
        $blog['type'] = $this->getType();
        $blog['date'] = $this->getDate();
        $blog['image'] = $this->getImage();
        $blog['content'] = $this->getContent();
        $blog['author'] = $this->getAuthor();
        $blog['view'] = $this->getView();

        return $blog;
    }

    public function returnBlogAsArrayNoContent(){
        $blog = array();
        $blog['id'] = $this->getID();
        $blog['title'] = $this->getTitle();
        $blog['description'] = $this->getDescription();
        $blog['type'] = $this->getType();
        $blog['date'] = $this->getDate();
        $blog['image'] = $this->getImage();
        $blog['author'] = $this->getAuthor();
        $blog['view'] = $this->getView();

        return $blog;
    }
        
}

 ?>
