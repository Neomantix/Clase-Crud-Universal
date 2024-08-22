<?php
  // Esto sirve para que php muestre por pantalla todos los errores y warnings
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  include("class_crud.php");

  //$c = new PDO("mysql:host=10.10.10.164;dbname=shop;charset=utf8mb4", "edgar", "1234"); //DEBEMOS USAR UN OBJETO DE LA CLASE PDO PARA PODER TRABAJAR CON SENTENCIAS SQL DE TIPO PDO
  $c = new PDO("mysql:host=localhost;dbname=shop;charset=utf8mb4", "root", "");           //DEBEMOS USAR UN OBJETO DE LA CLASE PDO PARA PODER TRABAJAR CON SENTENCIAS SQL DE TIPO PDO

  $test = new Crud($c, "admins");

  /******* PRUEBAS DE ATRIBUTOS *******/
  // echo "this->table = ";
  // var_dump($test->get_table());

  // echo "<hr>this->connection = ";
  // var_dump($test->get_connection());

  // echo "<hr>this->string_fields = ";
  // var_dump($test->get_string_fields());

  // echo "<hr>this->array_fields = ";
  // var_dump($test->get_array_fields());

  // echo "<hr>this->array_fields_type = ";
  // var_dump($test->get_array_fields_type());

  // echo "<hr>this->string_values = ";
  // var_dump($test->get_string_values());

  // TEST FINAL: OK!!

  /******* PRUEBAS DE METODOS *******/

  // TEST DE create_tuple()
  // $tupla = array("pera","manzana","melón");
  // $test->create_tuple(json_encode($tupla)); 
  // RESULTADO: OK!!

  // TEST DE delete_tuple()
  // $test->delete_tuple(8);
  // RESULTADO: OK!!

  // TEST DE read_table()
  // var_dump($test->read_table());
  // RESULTADO: OK!!

  // TEST DE update_tuple()
  $fields = array("name");
  $values = array("sandia");
  $test->update_tuple($fields, $values, "id_admin=11");
  // RESULTADO: NO OK!!

  // TEST FINAL: NO OK!!
?>