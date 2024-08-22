<?php

	class Crud{

		/************
		* ATRIBUTOS *
		*************/

		private $table;								// Nombre de la tabla con la que se va a operar. Ej: string(8) "palabras"  
		private $connection;					// Objeto de la clase mysqli. Se le pasa al constructor como parámetro. Ej: object(mysqli)#1 (18) { ["affected_rows"]=> int(2) ["client_info"]=> string(14) "mysqlnd 8.0.30" ["client_version"]=> int(80030) ["connect_errno"]=> int(0) ["connect_error"]=> NULL ["errno"]=> int(0) ["error"]=> string(0) "" ["error_list"]=> array(0) { } ["field_count"]=> int(6) ["host_info"]=> string(25) "Localhost via UNIX socket" ["info"]=> NULL ["insert_id"]=> int(0) ["server_info"]=> string(15) "10.4.32-MariaDB" ["server_version"]=> int(100432) ["sqlstate"]=> string(5) "00000" ["protocol_version"]=> int(10) ["thread_id"]=> int(25) ["warning_count"]=> int(0) } 
// AÑADO EL ATRIBUTO DE CLASE id_field PARA ALMACENAR EL CAMPO CON LA ID(PK) DE LA TABLA 
		private $id_field;						// Almacena un string con el campo de la id de la tabla
		private $string_fields;				// Almacena un string con los nombres de los campos separados por comas. Ej: string(26) "id_palabra,palabra_palabra" 
		private $array_fields;				// Almacena un array con los nombres de los campos en cada posición. Ej: array(2) { [0]=> string(10) "id_palabra" [1]=> string(15) "palabra_palabra" } 
//array_fields_type ES PRESCINDIBLE YA QUE EXISTE EL METODO getType que devuelve el tipo de dato de una variable
		private $array_fields_type;		// Almacena un array con los tipos correspondientes a los diferentes campos. Ej: array(2) { [0]=> string(3) "INT" [1]=> string(3) "STR" } 
		//private $array_fields_bind;		// Almacena un array con los nombres de los campos precedidos de ":", entre "'", y separados por ",". Ej: array(2) { [0]=> string(14) "':id_palabra'," [1]=> string(18) "':palabra_palabra'" } 
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
		
		private function get_fields(){
			$this->array_fields = array();
			$this->array_fields_type = array();
			//$this->array_fields_bind = array();
			$this->string_values = "";
			$this->string_fields = "";
			$this->id_field = "";
			
			$sql = "SHOW COLUMNS FROM ".$this->table;
			$fields = $this->connection->query($sql);
			
			foreach($fields as $index => $field){
				// $index es el indice del array obtenido en la consulta, es decir de $fields. Será 0 en la primera vuelta, 1 en la segunda, etc...
				// $field es toda la información disponible acerca del campo en esa posición en concreto. Es decir, su nombre("Field"), su tipo de dato("varchar"), etc...
				// Con el operador => podemos iterar en cada vuelta sobre ambos parametros simultaneamente $index y $field
				if($index != 0){ // Con este if eliminamos la id en todos los atributos
					// Array de los campos en bruto
					array_push($this->array_fields, $field["Field"]);

					// Array de campos para usar como primer parámetro de la función bindParam() de forma que cada index contiene: ':campo'
					// array_push($this->array_fields_bind, "':".$field["Field"]."'");

					// String de campos sin '' para los values de la sentencia SQL
					$this->string_values .= "?,";

					// String de los campos separados por comas (campo, campo) para los campos de la sentencia SQL
					$this->string_fields .= $field["Field"].",";

					// Sacamos el tipo de dato de cada campo de la tabla para crear un array con ellos y usarlo como 3º parámetro de bindParam()
					// $field["Type"] trae el formato, por ejemplo varchar(100) por lo tanto con el explode por el parentesis obtenemos un array que en su posicion 0 solo tiene el tipo de dato
					$type = explode("(", $field["Type"]);
// ESTE SWITCH PUEDE SER PRESCINDIBLE, YA QUE PODEMOS USAR getType en su lugar en el momento de analizar el tipo de dato sin tener que almacenar un array					
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
					// Array con los tipos de dato de cada campo en un formato comprensible para PDO::PARAM_
					array_push($this->array_fields_type, $type);
				}else{
					$this->id_field .= $field["Field"];
				}

			}

			// Eliminación de las comas finales de los strings
			$this->string_values = substr($this->string_values, 0, -1);
			$this->string_fields = substr($this->string_fields, 0, -1);
		}
		
		// Esta función recibe en un array los valores de los campos para crear la tupla
		public function create_tuple($values){
			$array_values = json_decode($values);				// Decodificamos el parámetro values recibido en el método
			try{
				// Analizamos cada posición del array buscando campos con nombre password, si se encuentra lo hasheamos
				for($i=0; $i<count($array_values); $i++){
					if($this->array_fields[$i] == "password"){
						$array_values[$i] = password_hash($array_values[$i],PASSWORD_DEFAULT);
//AQUI PODEMOS CREAR string_values como una variable local del método Y ELIMINARLA COMO PROPIEDAD DE LA CLASE
					}
				}
//AQUI IRÍA EL EXPLODE DEL NUEVO string_values para eliminar la coma final

				// Creación de la sentencia SQL
				$sql = "INSERT INTO ".$this->table."(".$this->string_fields.") VALUES (".$this->string_values.")";
				//echo "SENTENCIA SQL: ".$sql."<hr> CONTENIDO DE array_values: ";	

				// Preparación de la sentencia SQL anterior en esta conexión
				$stmt = $this->connection->prepare($sql);
				
				/***************************************************************************************************************************
				* Este bucle for llama a bindParam() tantas veces como campos haya en la tabla para asignarle los valores correspondientes *
				* El método bindParam() asocia los campos con sus respectivos valores ?. Tiene 3 parámetros: campo, valor y tipo de dato   *
				* Primer parámetro: en cada vuelta le asigna el valor de $j que va a ser un indice de 1 en adelante. Esto va a 						 *
				* 	representar la ? que sustituirá el valor pasado en el segundo parámetro dentro de la sentencia SQL										 *
				* Segund parámetro: se trata del valor que queremos pasar para esa posición en concreto																		 *
				* Tercer parámetro: simplemente le indica el tipo de dato del valor pasado como segundo argumento 												 *
				****************************************************************************************************************************/
				//var_dump($this->array_fields_type);
				for($i=0; $i<count($array_values);$i++){	
					// $j representa la posicion de las ? dadas en la sentencia SQL. Las posiciones empiezan en 1 para la primera ?, 2 para la segunda, etc... Por este motivo debe ser igual a $i+1
					$j=$i+1;

					// En función del tipo de dato que tenga el valor que se quiere impactar utilizaremos un PDO::PARAM_ u otro para adaptarnos a este tipo
					switch($this->array_fields_type[$i]){
						case "INT":
							$stmt->bindParam($j, $array_values[$i], PDO::PARAM_INT);
							break;
						case "STR":
							$stmt->bindParam($j, $array_values[$i], PDO::PARAM_STR);
							break;
						case "BOOL":
							$stmt->bindParam($j, $array_values[$i], PDO::PARAM_BOOL);
							break;
						default:
							echo "Error en el switch";
					}
				}

				// Se ejecuta la sentencia preparada 
				$stmt->execute(); 
							
			}catch(PDOException $e){
				echo "Error en método create_tuple(): ".$e->getMessage();
			}
		}

//HE RENOMBRADO ESTE METODO DE read_tuple() a read_table(). Tiene más sentido ya que escupe la tabla entera. Estaría bien crear un método para escupir una tupla determinada (para búsquedas)
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
// MÉTODO UPDATE TERMINADO. CONSULTAR CON SARA FORMA DE PREPARAR LA PARTE DEL WHERE
		// Recibe un array con los campos y otro con los valores que deben tomar. Además debe recibir un string con la condición que va después del WHERE
		public function update_tuple($array_fields, $array_values, $condition){
			try{
				if($condition != ""){
					$updates = "";
					// Necesito preparar una cadena de este estilo: campo = ?, campo= ?
					for ($i = 0; $i<count($array_fields); $i++){
						$updates .= $array_fields[$i]."=?,";
					}

					// Eliminamos la última coma de la cadena de asignaciones del update
					$updates = substr($updates, 0, -1);

					// Montamos la sentencia SQL
					$sql = "UPDATE $this->table SET ".$updates." WHERE ".$condition;

					// Preparamos la sentencia SQL
					$stmt =$this->connection->prepare($sql);

					// Con el for bindeamos cada posición de valores
					for($i = 0; $i<count($array_values); $i++){
						$j=$i+1;
						// Analizamos el tipo de dato de cada value con getType. Puede devolver: "integer","string","boolean","double","array","object","resource","NULL" o "unknown type"
						$type = getType($array_values[$i]);

						// Ejecutamos el bindParam adecuado para cada tipo de dato
// CREO QUE PODRIAMOS CREAR UN METODO PRIVADO QUE REALICE ESTA TAREA, YA QUE SE REPITE EN VARIAS OCASIONES
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
					$stmt->execute();
				}else{
					echo "Sin condición para UPDATE";
				}
			}catch(PDOException $e){
				echo "Error en el método update_tuple: ".$e->getMessage();
			}
		}

		public function delete_tuple($id){
			try{
//AQUí UTILIZO EL NUEVO ATRIBUTO DE LA CLASE id_field QUE CONTIENE LA ID QUE DESCARTAMOS SIEMPRE EN get_fields()
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

// AÑADIDO ESTE GETTER
		public function get_id_field(){
			return $this->id_field;
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
// ESTE GETTER YA NO ES NECESARIO
		//public function get_array_fields_bind(){
		//	return $this->array_fields_bind;
		//}

		public function get_string_values(){
			return $this->string_values;
		}
	}
?>