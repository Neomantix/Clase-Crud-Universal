<?php

	class Crud{

		/************
		* ATRIBUTOS *
		*************/

		public $table;								// Nombre de la tabla con la que se va a operar. Ej: string(8) "palabras"  
		public $connection;						// Objeto de la clase mysqli. Se le pasa al constructor como parámetro. Ej: object(mysqli)#1 (18) { ["affected_rows"]=> int(2) ["client_info"]=> string(14) "mysqlnd 8.0.30" ["client_version"]=> int(80030) ["connect_errno"]=> int(0) ["connect_error"]=> NULL ["errno"]=> int(0) ["error"]=> string(0) "" ["error_list"]=> array(0) { } ["field_count"]=> int(6) ["host_info"]=> string(25) "Localhost via UNIX socket" ["info"]=> NULL ["insert_id"]=> int(0) ["server_info"]=> string(15) "10.4.32-MariaDB" ["server_version"]=> int(100432) ["sqlstate"]=> string(5) "00000" ["protocol_version"]=> int(10) ["thread_id"]=> int(25) ["warning_count"]=> int(0) } 
		public $string_fields;				// Almacena un string con los nombres de los campos separados por comas. Ej: string(26) "id_palabra,palabra_palabra" 
		public $array_fields;					// Almacena un array con los nombres de los campos en cada posición. Ej: array(2) { [0]=> string(10) "id_palabra" [1]=> string(15) "palabra_palabra" } 
		public $array_fields_type;		// Almacena un array con los tipos correspondientes a los diferentes campos. Ej: array(2) { [0]=> string(3) "INT" [1]=> string(3) "STR" } 
		public $string_fields_bind;		// Almacena un string con los nombres de los campos precedidos de ":" y separados por ",". Ej: string(28) ":id_palabra,:palabra_palabra" 
		public $array_fields_bind;		// Almacena un array con los nombres de los campos precedidos de ":", entre "'", y separados por ",". Ej: array(2) { [0]=> string(14) "':id_palabra'," [1]=> string(18) "':palabra_palabra'" } 


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
		
		private function get_fields(){
			$this->array_fields = array();
			$this->array_fields_bind = array();
			$this->array_fields_type = array();
			$this->string_fields = "";
			$this->string_fields_bind = "";
			
			$sql = "SHOW COLUMNS FROM ".$this->table;
			$fields = $this->connection->query($sql);
			
			foreach($fields as $index => $field){
				
				// Array de campos
				array_push($this->array_fields, $field["Field"]);
				
				// Array de campos para usar como primer parámetro de la función bindParam() de forma que cada index contiene: ':campo'
				if($index < $fields->num_rows - 1){		// EL NUMROWS PARECE QUE NO ES COMPATIBLE CON PDO -> PREGUNTAR A ALFONSO
					array_push($this->array_fields_bind, "':".$field["Field"]."',");
				}else{
					array_push($this->array_fields_bind, "':".$field["Field"]."'");
				}

				// String de los campos separados por comas (campo, campo)
				$this->string_fields .= $field["Field"].",";
				
				// String de los campos con : delante y separados por comas (:campo, :campo)
				$this->string_fields_bind .=":".$field["Field"].",";

				// Sacamos el tipo de dato de cada campo de la tabla para crear un array con ellos y usarlo como 3º parámetro de bindParam()
				$type = explode("(", $field["Type"]);
				
				// Discriminamos según el tipo de dato para adaptarlo a la sintaxis del 3 parámetro de bindParam() (PDO::PARAM_INT)
				switch($type[0]){
					case "int":
						$type = "INT";
						break;
					case "varchar":
						$type = "STR";
						break;
					case "tinyint":
						$type = "BOOL";
						break;
					default:
						echo "Error en el switch";
				}
				// Array del tipo de dato de cada campo
				array_push($this->array_fields_type, $type);
			}
			
			// Eliminación de las comas finales de los dos strings
			$this->string_fields = substr($this->string_fields, 0, -1);
			$this->string_fields_bind = substr($this->string_fields_bind, 0, -1);
		}
		

		public function create_tuple($values){
			$array_values = json_decode($values);				// Decodificamos el parámetro values recibido en el método
			try{
				$sql = "INSERT INTO ".$this->table." (".$this->string_fields.") VALUES (".$this->string_fields_bind.")";
				$stmt = $this->connection->prepare($sql);		// Preparación de la sentencia SQL anterior en esta conexión				
				for($i=1;$i<count($this->array_fields);$i++){	
					$stmt->bindParam($this->array_fields_bind[$i], $array_values[$i], $this->array_fields_type[$i]);		/*SI NO ME EQUIVOCO AQUI FALTARIA EL PDO::PARAM_ DELANTE DE $this->array_fields_type[$i] */		
				}
				
				// Bucle para llamar a bindParam() tantas veces como campos haya en la tabla x
				// El método bindParam() asocia los campos con sus respectivos valores. Tiene 3 parámetros: campo, valor y tipo de dato
				// Los campos de la tabla x en formato array de forma que cada posición contiene: ':campo',
				// Los valores que insertar en los correspondientes campos anteriores
				// Los tipos de datos de cada campo de la tabla x en formato array (INT, STR ou BOOL)
				
				$stmt->execute(); // Se ejecuta la sentencia preparada 
				
			}catch(PDOException $e){
				echo "Error: ".$e->getMessage();
			}
		}


		public function read_tuple(){
			try{
				$sql = "SELECT * FROM ".$this->table;
				$stmt = $this->connection->prepare($sql);
				return $consulta = $stmt->execute(); 	/* 
																								$consulta = $stmt->execute() DEVUELVE UN BOOLEANO, NO EL ARRAY CON LOS DATOS DE LA CONSULTA. 
																								EL return DEBE HACERSE ASI, DESPUES DEL execute():

																								return $stmt->fetchAll(PDO::FETCH_ASSOC);
																								ESTO DEVUELVE UN ARRAY ASOCIATIVO CON LOS DATOS DE LA CONSULTA
																							*/
			}catch(PDOException $e){
				echo "Error: ".$e->getMessage();
			}
		}


		public function update_tuple(){ // PENDIENTE DE TERMINAR
			try{
				$sql = "UPDATE ";
			}catch(PDOException $e){
				echo "Erro: ".$e->getMessage();
			}
		}


		public function delete_tuple($id){
			try{
				$sql = "DELETE FROM ".$this->table." WHERE ".$this->array_fields[0]." = :id";  //AQUI ARREGLE UN ERROR CON LAS COMILLAS
				$stmt = $this->connection->prepare($sql);
				$stmt->bindParam(":id", $id, PDO::PARAM_INT);
				// echo "SQL: " . $sql . " con ID: " . $id; Muestra la línea sql completa y la id que se quiere borrar
				return $stmt->execute();
			}catch(PDOException $e){
				echo "Error: ".$e->getMessage();
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

		public function get_string_fields(){
			return $this->string_fields;
		}

		public function get_array_fields(){
			return $this->array_fields;
		}

		public function get_array_fields_type(){
			return $this->array_fields_type;
		}

		public function get_string_fields_bind(){
			return $this->string_fields_bind;
		}

		public function get_array_fields_bind(){
			return $this->array_fields_bind;
		}
	}



	// Esto sirve para que php muestre por pantalla todos los errores y warnings
	error_reporting(E_ALL);
	ini_set('display_errors', 1);


	$c = new PDO("mysql:host=localhost;dbname=fichajes;charset=utf8mb4", "root", ""); //DEBEMOS USAR UN OBJETO DE LA CLASE PDO PARA PODER TRABAJAR CON SENTENCIAS SQL DE TIPO PDO
	$obxecto = new Crud($c, "empleados");
	
	if($obxecto->delete_tuple(2)){
		echo "<br><br>borrado";
	}else{
		echo "<br><br>no borrado";
	}
	

?>