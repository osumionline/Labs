<?php
define('OMODEL_PK', 1);
define('OMODEL_PK_STR', 10);
define('OMODEL_CREATED', 2);
define('OMODEL_UPDATED', 3);
define('OMODEL_NUM', 4);
define('OMODEL_TEXT', 5);
define('OMODEL_DATE', 6);
define('OMODEL_BOOL', 7);
define('OMODEL_LONGTEXT', 8);
define('OMODEL_FLOAT', 9);

#[Attribute]
class OTable {
	public function __construct(public string $name) {}
}

#[Attribute]
class OTableField {
	public function __construct(
		public string $name,
		public int $type,
		public mixed $default = null,
		public ?string $comment = null,
		public bool $incr = true
	) {}
}

class OModel {
	protected bool    $initialized = false;
	protected bool    $loaded      = false;
	protected array   $model       = [];
	protected string  $table_name  = '';
	protected string  $model_name  = '';
	protected array   $pk          = [];
	protected ?string $created     = null;
	protected ?string $updated     = null;

	function __construct() {
		$this->model_name = get_class($this);
		$rc = new ReflectionClass(get_class($this));
		$attributes = $rc->getAttributes(OTable::class);

		if (!empty($attributes)) {
			$table_attribute = $attributes[0]->newInstance();
			$this->table_name = $table_attribute->name;
		}

		$this->initialize();
	}

	private function initialize(): void {
		if (!$this->initialized) {
			$rc = new ReflectionClass($this);
			$properties = $rc->getProperties();

			foreach ($properties as $property) {
				$attributes = $property->getAttributes(OTableField::class);
				if (!empty($attributes)) {
					$field_attribute = $attributes[0]->newInstance();
					$property_name = $property->name;

					$this->model[$property_name] = [
						'modified' => false,
						'initial'  => null,
						'type'     => $field_attribute->type,
						'default'  => $field_attribute->default,
						'comment'  => $field_attribute->comment,
						'incr'     => $field_attribute->incr
					];

					if ($field_attribute->type == OMODEL_PK) {
						array_push($this->pk, $property_name);
						if ($field_attribute->incr && !is_null($field_attribute->default)) {
							throw new Exception('Field "'.$property_name.'" is an autoincremental primary key, it can\'t have a default value.');
						}
						if (gettype($field_attribute->default) !== 'integer' && gettype($field_attribute->default) !== 'NULL') {
							throw new Exception('Field "'.$property_name.'"\'s default value is wrong, integer or null was expected.');
						}
					}
					if ($field_attribute->type == OMODEL_CREATED) {
						$this->created = $property_name;
					}
					if ($field_attribute->type == OMODEL_UPDATED) {
						$this->updated = $property_name;
					}

					$this->$property_name = $field_attribute->default;
				}
			}

			$this->initialized = true;
		}
	}

	public function update(array $object): void {
		foreach ($this->model as $item) {
			$item['modified'] = false;
			$item['initial'] = $item['default'];
		}
		foreach ($object as $key => $value) {
			if (array_key_exists($key, $this->model)) {
				$this->model[$key]['initial'] = $value;
				$this->$key = $value;
			}
		}
	}

	public function find(array $opt=[]): bool {
		if (count($opt) == 0) {
			return false;
		}
		$sql = "SELECT * FROM `".$this->table_name."` WHERE ";
		$search_fields = [];
		foreach ($opt as $key => $value) {
			if (!array_key_exists($key, $this->model)) {
				throw new Exception('Field "'.$key.'" was not found on model '.$this->model_name.'.');
			}
			if (!is_null($value)) {
				if ($this->model[$key]['type'] != OMODEL_BOOL) {
					array_push($search_fields, "`".$key."` = '".$value."' ");
				}
				else {
					array_push($search_fields, "`".$key."` = ".($value ? 1 : 0)." ");
				}
			}
			else {
				array_push($search_fields, "`".$key."` IS NULL ");
			}
		}
		$sql .= implode("AND ", $search_fields);

		echo "SQL FIND: ".$sql."<br>\n";
		//$this->log('find - Query: '.$sql);

		//$this->db->query($sql);
		//$res = $this->db->next();

		//if ($res) {
			//$this->update($res);
			return true;
		//}

		return false;
	}

	public function delete(): void {
		$sql = "DELETE FROM `".$this->table_name."` WHERE ";
		$delete_fields = [];
		foreach ($this->pk as $pk_field) {
			array_push($delete_fields, "`".$pk_field."` = '".$this->$pk_field."' ");
		}
		$sql .= implode("AND ", $delete_fields);

		//$this->db->query($sql);

		//$this->log('delete - Query: '.$sql);
		echo "SQL DELETE: ".$sql."<br>\n";
	}

	function __set($name, $value) {
		try {
			$this->$name = $value;
			if (array_key_exists($name, $this->model)) {
				$this->model[$name]['modified'] = true;
			}
		}
		catch (Exception $ex) {}
	}
}

#[OTable(name: "user")]
class User extends OModel {
	#[OTableField(
		name: "id",
		type: OMODEL_PK,
		comment: "Id del usuario"
	)]
	protected int | null $id;

	#[OTableField(
		name: "nombre",
		type: OMODEL_TEXT,
		comment: "Nombre del usuario"
	)]
	protected string | null $nombre;

	#[OTableField(
		name: "apellidos",
		type: OMODEL_TEXT,
		comment: "Apellidos del usuario"
	)]
	protected string | null $apellidos;

	#[OTableField(
		name: "created_at",
		type: OMODEL_CREATED,
		comment: "Fecha de creación del registro"
	)]
	protected string | null $created_at;

	#[OTableField(
		name: "updated_at",
		type: OMODEL_UPDATED,
		comment: "Fecha de última modificación del registro"
	)]
	protected string | null $updated_at;

	public string $auxiliar;
}

$user = new User();
exit;

// Asignación de campos
$user->nombre = "prueba";
$user->apellidos = "lalala";
$user->auxiliar = "aux";
$user->otro = "otro?";

// Guardar datos
$user->save();

// Carga de un modelo completo
$user->update([
	'id' => 1,
	'nombre' => 'Iñigo',
	'apellidos' => 'Gorosabel',
	'created_at' => '1981-07-02 03:00:00',
	'updated_at' => '2023-09-14 23:12:00'
]);

echo "<pre>\n";
var_dump($user);
echo "</pre>\n";

// Búsqueda
try {
	$user->find(['id' => 3, 'nombre' => 'pako']);
}
catch (Exception $e) {
	echo "<pre>\n";
	var_dump($e);
	echo "<pre><br>\n";
}

// Borrado
$user->delete();
