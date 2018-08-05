<?php

namespace Rapd;

/**
 * Basic entity, without any persistance features.
 */
class BaseEntity {
	use Prototype;

	private $values = [];

	/**
	 * Override this array, which describes the fields of the extending entity class.
	 * Format is:
	 *     "field_name" => (integer|text|float|...)::class
	 * @var array
	 */
	protected static $fields = [
		"id" => integer::class
	];

	private static $typeDefaultValues = [
		integer::class => 0,
		string::class => "",
		float::class => 0.0,
	];

	public function __construct(array $values = []){
		$this->values = array_map(function($field){
			return self::$typeDefaultValues[$field];
		}, $this->getFields());
		$this->patch($values);
	}

	public function patch(array $values){
		foreach($values as $key => $value){
			if(array_key_exists($key, $this->getFields())){
				if($this->validateFieldValue($key, $value)){
					$this->__set($key, $value);
				}
			} else {
				error_log("Rejecting {$key} because it is not in the field list of ".get_called_class());
			}
		}
	}

	public function validateFieldValue($field, $value, $throwOnInvalidValue = true){
		$validationMethod = "VALIDATE_{$field}";

		$isValid = self::validateBuiltinType($this->getFields()[$field], $value);

		# If the basic type validators above approves the value,
		# also ask the field-specific validator if it exists.
		if($isValid && false !== array_search($validationMethod, get_class_methods(get_called_class()))){
			$isValid = call_user_func([get_called_class(), $validationMethod], $value);
		} else {
			echo "Didn't find {$validationMethod} for ".get_called_class()."\n";
			echo print_r(get_class_methods(get_called_class()), true);
		}

		if($throwOnInvalidValue && !$isValid){
			throw new \Exception("Field validation failed for {$field}");
		}

		return $isValid;
	}

	public static function validateBuiltinType(string $type, $value) : bool {
		switch($type){
			case integer::class:
				return is_numeric($value) && intval($value) == floatval($value);
			case string::class:
				return is_string($value);
			case float::class:
				return is_numeric($value);
		}
		return true;
	}

	public function __set($key, $value){
		if($this->validateFieldValue($key, $value)){
			$this->values[$key] = $value;
		}
	}

	public function __get($key){
		return $this->values[$key];
	}

	/**
	 * If not overridden this method will imply a table name.
	 * Examples (entity class => table name):
	 *     TodoTask => todo_task
	 *     App\Namespace\City => city
	 * @see  tests/entity_tests.php
	 */
	public static function getTable() : string {
		$namespacedClassParts = explode("\\", get_called_class());
		$table = array_pop($namespacedClassParts);
		$table = preg_replace("/([a-z0-9])([A-Z0-9])/", '$1_$2', $table);
		$table = strtolower($table);
		return $table;
	}

	public static function getFields() : array {
		return self::$fields;
	}
}
