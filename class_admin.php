<?php

  class Admin{

    /************
    * ATRIBUTOS *
    *************/
    private $connection;
    private $id;
    private $name;
    private $email;
    private $password;
    private $crud;

    /**********
    * METODOS *
    ***********/
    public function __construct($connection, $id, $name, $email, $password){
      $this->connection = $connection;
      $this->name = $name;
      $this->email = $email;
      $this->password = password_hash($password, PASSWORD_DEFAULT);
      $this->crud = new Crud($this->connection, "admins");
      $this->id = $id;
    }

    public function create_admin(){
      $values = array();
      array_push($values, $this->name);
      array_push($values, $this->email);
      array_push($values, $this->password);
      $this->crud->create_tuple($values);
      return $this->connection->lastInsertId();
    }

    public function delete_admin(){
      $this->crud->delete_tuple($this->id);
    }

    public function read_admins(){

    }

    public function update_admin(){

    }

    // public function login(){
      
    // }

    // public function logout(){

    // }

    /**********
    * GETTERS *
    ***********/

    public function get_id(){
      return $this->id;
    }

    public function get_connection(){
      return $this->connection;
    }

    public function get_name(){
      return $this->name;
    }

    public function get_email(){
      return $this->email;
    }

    public function get_password(){
      return $this->password;
    }
  }
?>