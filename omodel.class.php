<?php declare(strict_types=1);

namespace OsumiFramework\OFW\DB;

use OsumiFramework\OFW\DB\OTable;
use OsumiFramework\OFW\DB\ODB;
use \ReflectionClass;

class OModel {
	protected ODB | null $db       = null;
	protected bool    $initialized = false;
	protected bool    $loaded      = false;
	protected array   $model       = [];
	protected string  $table_name  = '';
	protected string  $model_name  = '';
	protected array   $pk          = [];
	protected ?string $created     = null;
	protected ?string $updated     = null;

	function __construct() {
		$this->db = new ODB();
		$this->model_name = get_class($this);
		$rc = new ReflectionClass(get_class($this));
		$attributes = $rc->getAttributes(OTable::class);

		if (!empty($attributes)) {
			$table_attribute = $attributes[0]->newInstance();
			$this->table_name = $table_attribute->name;
		}
		else {
			throw new Exception('Class must have OTable attribute class to set database table name.');
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
					$this->model[$property->name] = [
						'modified' => false,
						'initial'  => null,
						'type'     => $field_attribute->type,
						'default'  => $field_attribute->default,
						'comment'  => $field_attribute->comment,
						'incr'     => $field_attribute->incr
					];
					$this->validateField($property->name);

					$this->{$property->name} = $field_attribute->default;
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
				if (!in_array($this->model[$key]['type'], [OMODEL_DATE, OMODEL_CREATED, OMODEL_UPDATED])) {
					$this->model[$key]['initial'] = $value;
					$this->{$key} = $value;
				}
				else {
					$this->model[$key]['initial'] = date_create_from_format('Y-m-d H:i:s', $value);
					$this->{$key} = date_create_from_format('Y-m-d H:i:s', $value);
				}
			}
		}
	}

	public function find(array $opt=[]): bool {
		if (count($opt) == 0) {
			return false;
		}
		$sql = sprintf("SELECT * FROM `%s` WHERE ", $this->table_name);
		$search_fields = [];
		foreach ($opt as $key => $value) {
			if (!array_key_exists($key, $this->model)) {
				throw new Exception('Field "'.$key.'" was not found on model '.$this->model_name.'.');
			}
			if (!is_null($value)) {
				if ($this->model[$key]['type'] != OMODEL_BOOL) {
					array_push($search_fields, sprintf("`%s` = '%s' ", $key, $value));
				}
				else {
					array_push($search_fields, sprintf("`%s` = %d ", $key, ($value ? 1 : 0)));
				}
			}
			else {
				array_push($search_fields, sprintf("`%s` IS NULL ", $key));
			}
		}
		$sql .= implode("AND ", $search_fields);

		echo "SQL FIND: ".$sql."<br>\n";
		//$this->log('find - Query: '.$sql);

		$this->db->query($sql);
		$res = $this->db->next();

		if ($res) {
			$this->update($res);
			return true;
		}

		return false;
	}

	public function save() {
		$save_type = '';
		$query_params = [];

		// Set last updated date
		if (!is_null($this->updated)) {
			$this->{$this->updated} = date('Y-m-d H:i:s', time());
		}

		// UPDATE
		if (!is_null($this->{$this->created})) {
			$sql = sprintf("UPDATE `%s` SET ", $this->table_name);
			$updated_fields = [];
			foreach ($this->model as $field) {
				if ($field['type'] !== OMODEL_PK && $field['type'] !== OMODEL_PK_STR && $field['modified']) {
					array_push($updated_fields, $field->getUpdateStr());
					array_push($query_params, $this->{$field});
				}
			}
			// If there is nothing to update, just return
			if (count($updated_fields)==0){
				return false;
			}
			$sql .= implode(", ", $updated_fields);
			$sql .= " WHERE ";
			foreach ($this->pk as $i => $pk_ind) {
				if ($i != 0) {
					$sql .= "AND ";
				}
				$sql .= "`".$pk_ind."` = ?";
				array_push($query_params, $this->model->getFields()[$pk_ind]->get());
			}

			$save_type = 'u';
		}
		// INSERT
		else {
			$this->model->getFields()[$this->created]->set(date('Y-m-d H:i:s', time()));

			$sql = "INSERT INTO `".$this->table_name."` (";
			$insert_fields = [];
			foreach ($this->model->getFields() as $field) {
				array_push($insert_fields, "`".$field->getName()."`");
			}
			$sql .= implode(",", $insert_fields);
			$sql .= ") VALUES (";
			$insert_fields = [];
			foreach ($this->model->getFields() as $field) {
				$value = $field->get();
				array_push($insert_fields, $field->getInsertStr());
				if ($field->getType() === OMODEL_PK && $field->getIncr()) {
					array_push($query_params, null);
				}
				else {
					array_push($query_params, $value);
				}
			}
			$sql .= implode(",", $insert_fields);
			$sql .= ")";

			$save_type = 'i';
		}

		$this->log('save - Query: '.$sql);
		$this->log('save - Params:');
		$this->log(var_export($query_params, true));

		// Run the query
		try {
			$this->db->query($sql, $query_params);
		}
		catch(Exception $ex) {
			$this->log('ERROR: '.$ex->getMessage());
			return false;
		}

		// If table has only a PK and it is incremental, save it
		if ($save_type == 'i' && count($this->pk)==1 && $this->model->getFields()[$this->pk[0]]->getIncr()) {
			$this->model->getFields()[$this->pk[0]]->set($this->db->lastId());
		}

		// Set every field in the model as saved (original = current)
		foreach($this->model->getFields() as $field){
			$field->reset();
		}

		return true;
	}

	public function delete(): void {
		$sql = "DELETE FROM `".$this->table_name."` WHERE ";
		$delete_fields = [];
		foreach ($this->pk as $pk_field) {
			array_push($delete_fields, "`".$pk_field."` = '".$this->$pk_field."' ");
		}
		$sql .= implode("AND ", $delete_fields);

		$this->db->query($sql);

		//$this->log('delete - Query: '.$sql);
		echo "SQL DELETE: ".$sql."<br>\n";
	}

	function __set($name, $value) {
		try {
			if (array_key_exists($name, $this->model)) {
				if ($this->model[$name]['type'] !== OMODEL_CREATED && $this->model[$name]['type'] !== OMODEL_UPDATED) {
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
