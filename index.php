<?php
  // Esto sirve para que php muestre por pantalla todos los errores y warnings
  error_reporting(E_ALL);
  ini_set('display_errors', 1);

  require_once("class_crud.php");
  require_once("class_admin.php");


  //$c = new PDO("mysql:host=10.10.10.164;dbname=shop;charset=utf8mb4", "edgar", "1234"); //DEBEMOS USAR UN OBJETO DE LA CLASE PDO PARA PODER TRABAJAR CON SENTENCIAS SQL DE TIPO PDO
  $c = new PDO("mysql:host=localhost;dbname=shop;charset=utf8mb4", "root", "");           //DEBEMOS USAR UN OBJETO DE LA CLASE PDO PARA PODER TRABAJAR CON SENTENCIAS SQL DE TIPO PDO

  $test = new Crud($c, "admins");

  /******* PRUEBAS DE ATRIBUTOS *******/
  // echo "this->table = ";
  // var_dump($test->get_table());

  // echo "<hr>this->connection = ";
  // var_dump($test->get_connection());

  // echo "this->id_field = ";
  // var_dump($test->get_id_field());

  // echo "<hr>this->string_fields = ";
  // var_dump($test->get_string_fields());

  // echo "<hr>this->array_fields = ";
  // var_dump($test->get_array_fields());

  // echo "<hr>this->string_values = ";
  // var_dump($test->get_string_values());

  // TEST FINAL: OK!!

  /******* PRUEBAS DE METODOS *******/

  // TEST DE create_tuple()
  // $tupla = array("naranja","peladillo","melocotón");
  // $test->create_tuple($tupla); 
  // RESULTADO: OK!!

  // TEST DE delete_tuple()
  // $test->delete_tuple(8);
  // RESULTADO: OK!!

  // TEST DE read_table()
  // var_dump($test->read_table());
  // RESULTADO: OK!!

  // TEST DE update_tuple()
  // $fields = array("name");
  // $values = array("melón");
  // $test->update_tuple($fields, $values, 10);
  // RESULTADO: NO OK!!

  // TEST FINAL: NO OK!!

  $admin = new Admin($c, NULL, "edgar", "edgar@edgar.com", "1234");
  //echo $admin->get_id();

?>