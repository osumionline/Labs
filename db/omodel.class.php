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

	private function log(string $str): void {
		echo $str."<br>\n";
	}

	private function getField(array $data): OModelFieldNum | OModelFieldText | OModelFieldDate | OModelFieldBool | OModelFieldFloat | null {
		switch ($data['type']) {
			case OMODEL_PK: {
				return new OModelFieldNum($data);
			}
			break;
			case OMODEL_PK_STR: {
				return new OModelFieldText($data);
			}
			break;
			case OMODEL_CREATED: {
				return new OModelFieldDate($data);
			}
			break;
			case OMODEL_UPDATED: {
				return new OModelFieldDate($data);
			}
			break;
			case OMODEL_NUM: {
				return new OModelFieldNum($data);
			}
			break;
			case OMODEL_TEXT: {
				return new OModelFieldText($data);
			}
			break;
			case OMODEL_DATE: {
				return new OModelFieldDate($data);
			}
			break;
			case OMODEL_BOOL: {
				return new OModelFieldBool($data);
			}
			break;
			case OMODEL_LONGTEXT: {
				return new OModelFieldText($data);
			}
			break;
			case OMODEL_FLOAT: {
				return new OModelFieldFloat($data);
			}
			break;
		}
		return null;
	}

	private function initialize(): void {
		if (!$this->initialized) {
			$rc = new ReflectionClass($this);
			$properties = $rc->getProperties();

			foreach ($properties as $property) {
				$attributes = $property->getAttributes(OTableField::class);
				if (!empty($attributes)) {
					$field_attribute = $attributes[0]->newInstance();
					$new_field = $this->getField([
						'modified' => false,
						'initial'  => null,
						'name'     => is_null($field_attribute->name) ? $property->name : $field_attribute->name,
						'type'     => $field_attribute->type,
						'default'  => $field_attribute->default,
						'comment'  => $field_attribute->comment,
						'incr'     => $field_attribute->incr
					]);

					if ($this->validateField($new_field)) {
						$this->model[$property->name] = $new_field;
						$this->{$property->name} = $field_attribute->default;
					}
				}
			}

			$this->initialized = true;
		}
	}

	private function validateField($field): bool {
		if (is_null($field)) {
			throw new Exception('Field had errors and could not be checked.');
		}

		// OMODEL_PK
		if ($field->getType() == OMODEL_PK) {
			if ($field->getIncr() && !is_null($field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'" is an autoincremental primary key, it can\'t have a default value.');
			}
			if (gettype($field->getDefault()) !== 'integer' && gettype($field->getDefault()) !== 'NULL') {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, integer or null was expected.');
			}
			array_push($this->pk, $field->getName());
			return true;
		}
		// OMODEL_PK_STR
		if ($field->getType() == OMODEL_PK_STR) {
			if (gettype($field->getDefault()) !== 'string' && gettype($field->getDefault()) !== 'NULL') {
				throw new Exception('Field "'.$property_name.'"\'s default value is wrong, string or null was expected.');
			}
			array_push($this->pk, $field->getName());
			return true;
		}
		// OMODEL_CREATED
		if ($field->getType() == OMODEL_CREATED) {
			$this->created = $field->getName();
			return true;
		}
		// OMODEL_UPDATED
		if ($field->getType() == OMODEL_UPDATED) {
			$this->updated = $field->getName();
			return true;
		}
		// OMODEL_NUM
		if ($field->getType() == OMODEL_NUM) {
			if (!is_numeric($field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, numeric value or null was expected.');
			}
			$field->setDefault(intval($field->getDefault()));
			return true;
		}
		// OMODEL_TEXT
		if ($field->getType() == OMODEL_TEXT) {
			if (!is_string($field->getDefault()) && !is_null($field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, string value or null was expected.');
			}
			$field->setDefault(strval($field->getDefault()));
			return true;
		}
		// OMODEL_DATE
		if ($field->getType() == OMODEL_DATE) {
			if (!is_string($field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, DateTime value or null was expected.');
			}
			$date_pattern = "/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/";
			if (!is_null($field->getDefault()) && !preg_match($date_pattern, $field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, pattern "yyyy-mm-dd hh:ii:ss" expected.');
			}
			return true;
		}
		// OMODEL_BOOL
		if ($field->getType() == OMODEL_BOOL) {
			if (!is_bool($field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, boolean value or null was expected.');
			}
			return true;
		}
		// OMODEL_LONGTEXT
		if ($field->getType() == OMODEL_LONGTEXT) {
			if (!is_string($field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, string value or null was expected.');
			}
			$field->setDefault(strval($field->getDefault()));
			return true;
		}
		// OMODEL_FLOAT
		if ($field->getType() == OMODEL_FLOAT) {
			if (!is_numeric($field->getDefault())) {
				throw new Exception('Field "'.$field->getName().'"\'s default value is wrong, numeric value or null was expected.');
			}
			$field->setDefault(floatval($field->getDefault()));
			return true;
		}
		return false;
	}

	public function update(array $object): void {
		foreach ($this->model as $item) {
			$item->setModified(false);
			$item->setInitial($item->getDefault());
		}
		foreach ($object as $key => $value) {
			if (array_key_exists($key, $this->model)) {
				if (!in_array($this->model[$key]->getType(), [OMODEL_DATE, OMODEL_CREATED, OMODEL_UPDATED])) {
					$this->model[$key]->setInitial($value);
					$this->{$key} = $value;
				}
				else {
					$this->model[$key]->setInitial(date_create_from_format('Y-m-d H:i:s', $value));
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
				if ($this->model[$key]->getType() != OMODEL_BOOL) {
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

		$this->log('find - Query: '.$sql);

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
			$this->{$this->updated} = new \DateTime();
		}

		// UPDATE
		if (!is_null($this->{$this->created})) {
			$sql = sprintf("UPDATE `%s` SET ", $this->table_name);
			$updated_fields = [];
			foreach ($this->model as $field) {
				if (!in_array($field->getType(), [OMODEL_PK, OMODEL_PK_STR]) && $field->getModified()) {
					array_push($updated_fields, $field->getUpdateStr());
					if (!in_array($field->getType(), [OMODEL_DATE, OMODEL_CREATED, OMODEL_UPDATED])) {
						array_push($query_params, $this->{$field->getName()});
					}
					else {
						array_push($query_params, date_format($this->{$field->getName()}, 'Y-m-d H:i:s'));
					}
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
				array_push($query_params, $this->{$this->model[$pk_ind]->getName()});
			}

			$save_type = 'u';
		}
		// INSERT
		else {
			$this->{$this->created} = new \DateTime();

			$sql = "INSERT INTO `".$this->table_name."` (";
			$insert_fields = [];
			foreach ($this->model as $field) {
				array_push($insert_fields, "`".$field->getName()."`");
			}
			$sql .= implode(",", $insert_fields);
			$sql .= ") VALUES (";
			$insert_fields = [];
			foreach ($this->model as $field) {
				array_push($insert_fields, $field->getInsertStr());
				if ($field->getType() === OMODEL_PK && $field->getIncr()) {
					array_push($query_params, null);
				}
				else if (in_array($field->getType(), [OMODEL_DATE, OMODEL_CREATED, OMODEL_UPDATED])) {
					array_push($query_params, date_format($this->{$field->getName()}, 'Y-m-d H:i:s'));
				}
				else {
					array_push($query_params, $this->{$field->getName()});
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
		if ($save_type == 'i' && count($this->pk)==1 && $this->model[$this->pk[0]]->getIncr()) {
			$this->{$this->model[$this->pk[0]]->getName()} = $this->db->lastId();
		}

		// Set every field in the model as saved (original = current)
		foreach($this->model as $field){
			$field->reset();
		}

		return true;
	}

	public function delete(): void {
		$sql = "DELETE FROM `".$this->table_name."` WHERE ";
		$delete_fields = [];
		foreach ($this->pk as $pk_field) {
			array_push($delete_fields, "`".$pk_field."` = '".$this->{$pk_field}."' ");
		}
		$sql .= implode("AND ", $delete_fields);

		$this->db->query($sql);

		$this->log('delete - Query: '.$sql);
	}

	function __set($name, $value) {
		try {
			if (array_key_exists($name, $this->model)) {
				if (!in_array($this->model[$name]->getType(), [OMODEL_CREATED, OMODEL_UPDATED])) {
					$this->model[$name]->setModified(true);
					$this->{$name} = $value;
				}
			}
			else {
				$this->{$name} = $value;
			}
		}
		catch (Exception $ex) {}
	}
}
