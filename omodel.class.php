<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

use OsumiFramework\OFW\DB\OTable;
use \ReflectionClass;

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
					$this->validateField($property_name);

					$this->$property_name = $field_attribute->default;
				}
			}

			$this->initialized = true;
		}
	}

	private function validateField($property_name) {
		// OMODEL_PK
		if ($this->model[$property_name]['type'] == OMODEL_PK) {
			array_push($this->pk, $property_name);
			if ($this->model[$property_name]['incr'] && !is_null($this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'" is an autoincremental primary key, it can\'t have a default value.');
			}
			if (gettype($this->model[$property_name]['default']) !== 'integer' && gettype($this->model[$property_name]['default']) !== 'NULL') {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, integer or null was expected.');
			}
		}
		// OMODEL_PK_STR
		if ($this->model[$property_name]['type'] == OMODEL_PK_STR) {
			array_push($this->pk, $property_name);
			if (gettype($this->model[$property_name]['default']) !== 'string' && gettype($this->model[$property_name]['default']) !== 'NULL') {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, string or null was expected.');
			}
		}
		// OMODEL_CREATED
		if ($this->model[$property_name]['type'] == OMODEL_CREATED) {
			$this->created = $property_name;
		}
		// OMODEL_UPDATED
		if ($this->model[$property_name]['type'] == OMODEL_UPDATED) {
			$this->updated = $property_name;
		}
		// OMODEL_NUM
		if ($this->model[$property_name]['type'] == OMODEL_NUM) {
			if (!is_numeric($this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, numeric value or null was expected.');
			}
			$this->model[$property_name]['default'] = intval($this->model[$property_name]['default']);
		}
		// OMODEL_TEXT
		if ($this->model[$property_name]['type'] == OMODEL_TEXT) {
			if (!is_string($this->model[$property_name]['default']) && !is_null($this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, string value or null was expected.');
			}
			$this->model[$property_name]['default'] = strval($this->model[$property_name]['default']);
		}
		// OMODEL_DATE
		if ($this->model[$property_name]['type'] == OMODEL_DATE) {
			if (!is_string($this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, string value or null was expected.');
			}
			$date_pattern = "/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/";
			if (!is_null($this->model[$property_name]['default']) && !preg_match($date_pattern, $this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, pattern "yyyy-mm-dd hh:ii:ss" expected.');
			}
		}
		// OMODEL_BOOL
		if ($this->model[$property_name]['type'] == OMODEL_BOOL) {
			if (!is_bool($this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, boolean value or null was expected.');
			}
		}
		// OMODEL_LONGTEXT
		if ($this->model[$property_name]['type'] == OMODEL_LONGTEXT) {
			if (!is_string($this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, string value or null was expected.');
			}
			$this->model[$property_name]['default'] = strval($this->model[$property_name]['default']);
		}
		// OMODEL_FLOAT
		if ($this->model[$property_name]['type'] == OMODEL_FLOAT) {
			if (!is_numeric($this->model[$property_name]['default'])) {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, numeric value or null was expected.');
			}
			$this->model[$property_name]['default'] = floatval($this->model[$property_name]['default']);
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
			if (array_key_exists($name, $this->model)) {
				if ($this->model[$property_name]['type'] !== OMODEL_CREATED && $this->model[$property_name]['type'] !== OMODEL_UPDATED) {
					$this->model[$name]['modified'] = true;
					$this->name = $value;
				}
			}
			else {
				$this->name = $value;
			}
		}
		catch (Exception $ex) {}
	}
}