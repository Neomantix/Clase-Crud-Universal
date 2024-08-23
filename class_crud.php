<?php

	class Crud{

		/************
		* ATRIBUTOS *
		*************/

		private $table;								// Nombre de la tabla con la que se va a operar. Ej: string(8) "palabras"  
		private $connection;					// Objeto de la clase mysqli. Se le pasa al constructor como parámetro. Ej: object(mysqli)#1 (18) { ["affected_rows"]=> int(2) ["client_info"]=> string(14) "mysqlnd 8.0.30" ["client_version"]=> int(80030) ["connect_errno"]=> int(0) ["connect_error"]=> NULL ["errno"]=> int(0) ["error"]=> string(0) "" ["error_list"]=> array(0) { } ["field_count"]=> int(6) ["host_info"]=> string(25) "Localhost via UNIX socket" ["info"]=> NULL ["insert_id"]=> int(0) ["server_info"]=> string(15) "10.4.32-MariaDB" ["server_version"]=> int(100432) ["sqlstate"]=> string(5) "00000" ["protocol_version"]=> int(10) ["thread_id"]=> int(25) ["warning_count"]=> int(0) } 
		private $id_field;						// Almacena un string con el campo de la id de la tabla
		private $string_fields;				// Almacena un string con los nombres de los campos separados por comas. Ej: string(26) "id_palabra,palabra_palabra" 
		private $array_fields;				// Almacena un array con los nombres de los campos en cada posición. Ej: array(2) { [0]=> string(10) "id_palabra" [1]=> string(15) "palabra_palabra" } 
		private $string_values;				// Almacena un string con los nombres de los campos precedidos de ":" y separados por ",". Ej: string(28) ":id_palabra,:palabra_palabra" 


		/**********
		* MÉTODOS *
		***********/
		
		/************************************************************************************************************
		* Constructor. Se le pasa como parámetros un objeto de clase PDO para la conexión, y un string con la tabla *
		*************************************************************************************************************/
		public function __construct($connection, $table){
			$this->connection = $connection;
			$this->table = $table;
			$this->get_fields();
		}
		
		// Este método se encarga de rellenar los atributos que quedan vacios en el constructor
		private function get_fields(){
			$this->array_fields = array();
			$this->string_values = "";
			$this->string_fields = "";
			$this->id_field = "";
			
			$sql = "SHOW COLUMNS FROM ".$this->table;
			$fields = $this->connection->query($sql);
			
			foreach($fields as $index => $field){
				// $index es el indice del array obtenido en la consulta, es decir de $fields. Será 0 en la primera vuelta, 1 en la segunda, etc...
				// $field es toda la información disponible acerca del campo en esa posición en concreto. Es decir, su nombre("Field"), su tipo de dato("varchar"), etc...
				// Con el operador => podemos iterar en cada vuelta sobre ambos parametros simultaneamente $index y $field

				// Con este if eliminamos la id en todos los atributos
				if($index != 0){ 
					// Array de los campos en bruto
					array_push($this->array_fields, $field["Field"]);

					// String de "?" separadas por "," para los values de la sentencia SQL
					$this->string_values .= "?,";

					// String de los campos separados por comas (campo, campo) para los campos de la sentencia SQL
					$this->string_fields .= $field["Field"].",";
				}else{
					$this->id_field .= $field["Field"];
				}
			}

			// Eliminación de las comas finales de los strings
			$this->string_values = substr($this->string_values, 0, -1);
			$this->string_fields = substr($this->string_fields, 0, -1);
		}

		// Este método se encarga de bindear una serie de valores ($array_values) a una sentencia sql preparada
		private function link_parameters($stmt, $array_values){
			/***************************************************************************************************************************
				* Este bucle for llama a bindParam() tantas veces como campos haya en la tabla para asignarle los valores correspondientes *
				* El método bindParam() asocia los campos con sus respectivos valores ?. Tiene 3 parámetros: campo, valor y tipo de dato   *
				* Primer parámetro: en cada vuelta le asigna el valor de $j que va a ser un indice de 1 en adelante. Esto va a 						 *
				* 	representar la ? que sustituirá el valor pasado en el segundo parámetro dentro de la sentencia SQL										 *
				* Segundo parámetro: se trata del valor que queremos pasar para esa posición en concreto																		 *
				* Tercer parámetro: simplemente le indica el tipo de dato del valor pasado como segundo argumento 												 *
				****************************************************************************************************************************/
			for($i=0; $i<count($array_values);$i++){	
				// $j representa la posicion de las ? dadas en la sentencia SQL. Las posiciones empiezan en 1 para la primera ?, 2 para la segunda, etc... Por este motivo debe ser igual a $i+1
				$j=$i+1;
				$type = getType($array_values[$i]);

				// Ejecutamos el bindParam adecuado para cada tipo de dato
				switch($type){
					case "integer":
						$stmt->bindParam($j, $array_values[$i], PDO::PARAM_INT);
						break;
					case "string":
						$stmt->bindParam($j, $array_values[$i], PDO::PARAM_STR);
						break;
					case "boolean":
						$stmt->bindParam($j, $array_values[$i], PDO::PARAM_BOOL);
						break;
					default:
						echo "Error en el switch";
				}
			}
			return $stmt;
		}
		
		// Esta función recibe en un array los valores de los campos para crear la tupla
		public function create_tuple($values){
			try{
				// Analizamos cada posición del array buscando campos con nombre password, si se encuentra lo hasheamos
				for($i=0; $i<count($values); $i++){
					if($this->array_fields[$i] == "password"){
						$array_values[$i] = password_hash($values[$i],PASSWORD_DEFAULT);
					}
				}

				// Creación de la sentencia SQL
				$sql = "INSERT INTO ".$this->table."(".$this->string_fields.") VALUES (".$this->string_values.")";

				// Preparación de la sentencia SQL anterior en esta conexión
				$stmt = $this->connection->prepare($sql);

				//$this->link_parameters($stmt, $array_values)->execute(); //Otra forma de hacer las dos siguientes líneas
				$linked_stmt = $this->link_parameters($stmt, $values);
				$linked_stmt->execute();
			}catch(PDOException $e){
				echo "Error en método create_tuple(): ".$e->getMessage();
			}
		}

		public function read_table(){
			try{
				$sql = "SELECT * FROM ".$this->table;
				$stmt = $this->connection->prepare($sql);
				$stmt->execute();

				// Esto devuelve un array asociativo con los datos de la consulta que luego se podrá recorrer fuera con un foreach para el tratamiento de los datos
				return $stmt->fetchAll(PDO::FETCH_ASSOC); 	
			}catch(PDOException $e){
				echo "Error en el método read_tuple(): ".$e->getMessage();
			}
		}

		// Recibe un array con los campos y otro con los valores que deben tomar. Además debe recibir la id a modificar
		public function update_tuple($fields, $values, $id){
			try{
					$updates = "";
					// Necesito preparar una cadena de este estilo: campo = ?, campo = ?
					for ($i = 0; $i<count($fields); $i++){
						$updates .= $fields[$i]."=?,";
					}

					array_push($values, $id);

					// Eliminamos la última coma de la cadena de asignaciones del update
					$updates = substr($updates, 0, -1);

					// Montamos la sentencia SQL
					$sql = "UPDATE $this->table SET ".$updates." WHERE ".$this->id_field."=?";

					// Preparamos la sentencia SQL
					$stmt =$this->connection->prepare($sql);

					// La linkamos con sus valores con link_parameters
					$linked_stmt = $this->link_parameters($stmt, $values);

					// Ejecutamos la sentencia completa
					$linked_stmt->execute();
			}catch(PDOException $e){
				echo "Error en el método update_tuple: ".$e->getMessage();
			}
		}

		public function delete_tuple($id){
			try{
				$sql = "DELETE FROM ".$this->table." WHERE ".$this->id_field." = :id";  
				$stmt = $this->connection->prepare($sql);
				$stmt->bindParam(":id", $id, PDO::PARAM_INT);
				return $stmt->execute();
			}catch(PDOException $e){
				echo "Error en el método delete_tuple(): ".$e->getMessage();
			}
		}
		

		/**********
		* GETTERS *
		***********/

		public function get_table(){
			return $this->table;
		}

		public function get_connection(){
			return $this->connection;
		}

		public function get_id_field(){
			return $this->id_field;
		}

		public function get_string_fields(){
			return $this->string_fields;
		}

		public function get_array_fields(){
			return $this->array_fields;
		}

		public function get_string_values(){
			return $this->string_values;
		}
	}
?>