<?php

	function SQL_query($q, $func) {
		global $sql;
		$result = -1;
		
		$start = microtime(true);
		$sql -> query('SET NAMES utf8mb4;');
		$result = $sql -> query($q);
		$end = microtime(true);
		$work = $end - $start;
		
		if (SQL_LOG) {
			$time = date('Y-m-d H:i:s', time());
			$log = "[$time] ($func) :: $q ($work)".PHP_EOL;
			file_put_contents(DOCROOT . 'log/sqllog.txt', $log, FILE_APPEND | LOCK_EX);
		}
		
		if (DEBUG) {
			if ($sql -> errno) {
				die (load_tpl('dberr', array(
					'ERR' => $sql -> error,
					'ERRNO' => $sql -> errno
				)));
			}
		}
		return $result;
	}
	
	function clean_table($table) {
		return SQL_query('TRUNCATE `' . $table . '`', 'clean_table');
	}
	
	function getRow($table, $fields, $by_fields='*', $logic = 'AND') {
		global $sql;
		$table = $sql -> real_escape_string($table);
		$logic = $sql -> real_escape_string($logic);
		$logic = (in_array($logic, ['AND', 'OR']) ? $logic : 'AND');
		$where_conditions = '';
		$by_fields_collection = array();
		$fields_collection = array();
		$result = array();

		$fields = ($fields != '*') ? explode(',', $fields) : '*';
		if (is_array($fields)) {
			if (!empty($fields)) {
				for ($i = 0; $i < count($fields); $i++) {
					$field = $sql -> real_escape_string($fields[$i]);
					$fields_collection[] = "`$table`.`$field`";
				}
				$fields_collection = implode(', ', $fields_collection);
			}
		}
		if (is_string($fields) && $fields == '*') $fields_collection = "`$table`.*";

		if (is_array($by_fields)) {
			if (!empty($by_fields)) {
				for ($i = 0; $i < count($by_fields); $i++) {
					$by_field = $sql -> real_escape_string($by_fields[$i][0]);
					$by_field_value = $sql -> real_escape_string($by_fields[$i][2]);
					$compare_logic = $sql -> real_escape_string($by_fields[$i][1]);
					$compare_logic = (in_array($compare_logic, ['=', '!=', '>', '<', '>=', '<=']) ? $compare_logic : '=');
					
					$by_fields_collection[] = "`$table`.`$by_field` $compare_logic '$by_field_value'";
				}
				$by_fields_collection = implode(" $logic ", $by_fields_collection);
			}
		}
		if (is_string($by_fields) && $by_fields == '*') $by_fields_collection = '1';
		$where_conditions = "WHERE ($by_fields_collection)";
		
		$q = "SELECT $fields_collection FROM `$table` $where_conditions;";
		$ch = SQL_query($q, 'getRow');
		
		if (!$sql -> errno) {
			$result = $ch -> fetch_assoc();
			$ch -> close();			
		}
		
		return $result;
	}	
	
	function getRows($table, $fields='*', $by_fields='*', $logic='AND', $order_by='', $order_type='ASC', $limit_start='', $limit_count='', $group_by='') {
		global $sql;
		$table = $sql -> real_escape_string($table);
		$order_by = $sql -> real_escape_string($order_by);
		$order_type = $sql -> real_escape_string($order_type);
		$logic = (in_array($logic, ['AND', 'OR']) ? $logic : 'AND');
		$order_type = (in_array($order_type, ['ASC', 'DESC']) ? $order_type : 'ASC');
		$group_by = $sql -> real_escape_string($group_by);
		$limit_start = $sql -> real_escape_string($limit_start);
		$limit_count = $sql -> real_escape_string($limit_count);
		
		$fields_collection = array();
		$by_fields_collection = array();
		$where_conditions = '';
		$order_conditions = '';
		$limit_conditions = '';
		$group_conditions = '';
		
		$result = array();
		
		$fields = ($fields != '*') ? explode(',', $fields) : '*';
		if (is_array($fields)) {
			if (!empty($fields)) {
				for ($i = 0; $i < count($fields); $i++) {
					$field = $sql -> real_escape_string($fields[$i]);
					$fields_collection[] = "`$table`.`$field`";
				}
				$fields_collection = implode(', ', $fields_collection);
			}
		}
		if (is_string($fields) && $fields == '*') $fields_collection = "`$table`.*";
		
		if (is_array($by_fields)) {
			if (!empty($by_fields)) {
				for ($i = 0; $i < count($by_fields); $i++) {
					$by_field = $sql -> real_escape_string($by_fields[$i][0]);
					$by_field_value = $sql -> real_escape_string($by_fields[$i][2]);
					$compare_logic = $sql -> real_escape_string($by_fields[$i][1]);
					$compare_logic = (in_array($compare_logic, ['=', '!=', '>', '<', '>=', '<=', 'IN', 'LIKE', 'REGEXP']) ? $compare_logic : '=');
					
					if ($compare_logic != 'IN') {

						$by_fields_collection[] = "`$table`.`$by_field` $compare_logic '$by_field_value'";
					} else if ($compare_logic == 'IN') {
						$by_field_value_collection = array();
						$by_field_value = explode(',', $by_field_value);
						
						if (!empty($by_field_value)) {
							for ($j = 0; $j < count($by_field_value); $j++) {
								$by_field_value_current = $sql -> real_escape_string($by_field_value[$j]);
								if (is_string($by_field_value_current)) {
									$by_field_value_collection[] = "'$by_field_value_current'";
								} else if (is_numeric($by_field_value_current)) {
									$by_field_value_collection[] = $by_field_value;
								}
							}
							$by_field_value = implode(',', $by_field_value_collection);
						}
						$by_fields_collection[] = "`$table`.`$by_field` $compare_logic ($by_field_value)";
					}
				}
				$by_fields_collection = implode(" $logic ", $by_fields_collection);
			}
		}
		if (is_string($by_fields) && $by_fields == '*') $by_fields_collection = '1';
		$where_conditions = "WHERE ($by_fields_collection)";
	
		if (!empty($order_by)) {
			if ($order_by == 'rand()') {
				$order_conditions = "ORDER BY rand() $order_type";
			} else if (preg_match('/length\//', $order_by)) {
				preg_match('/length\/(.*)/isu', $order_by, $found);
				$order_by = $found[1];
				$order_conditions = "ORDER BY LENGTH(`$table`.`$order_by`), $order_by";
			} else {
				$order_conditions = "ORDER BY `$table`.`$order_by` $order_type";
			}
		}
		
		if (!empty($group_by)) {
			$group_conditions = "GROUP BY `$table`.`$group_by`";
		}
		
		if (!empty($limit_count)) {
			$limit_conditions = "LIMIT $limit_start, $limit_count";
		}
		
		$q = "SELECT SQL_CALC_FOUND_ROWS $fields_collection FROM `$table` $where_conditions $group_conditions $order_conditions $limit_conditions;";

		$ch = SQL_query($q, 'getFields');
		if (!$sql -> errno) {
			while ($sql_result = $ch -> fetch_assoc()) {
				$result[] = $sql_result;
			}
		}
		
		return $result;
	}
	
	function getField($table, $field, $by_fields='*', $logic = 'AND') {
		global $sql;
		$table = $sql -> real_escape_string($table);
		$field = $sql -> real_escape_string($field);
		$logic = $sql -> real_escape_string($logic);
		$logic = (in_array($logic, ['AND', 'OR']) ? $logic : 'AND');
		$by_fields_collection = array();
		$where_conditions = '';
		$result = '';

		if (is_array($by_fields)) {
			if (!empty($by_fields)) {
				for ($i = 0; $i < count($by_fields); $i++) {
					$by_field = $sql -> real_escape_string($by_fields[$i][0]);
					$by_field_value = $sql -> real_escape_string($by_fields[$i][2]);
					$compare_logic = $sql -> real_escape_string($by_fields[$i][1]);
					$compare_logic = (in_array($compare_logic, ['=', '!=', '>', '<', '>=', '<=']) ? $compare_logic : '=');
					
					$by_fields_collection[] = "`$table`.`$by_field` $compare_logic '$by_field_value'";
				}
				$by_fields_collection = implode(" $logic ", $by_fields_collection);
			}
		}
		if (is_string($by_fields) && $by_fields == '*') $by_fields_collection = '1';
		$where_conditions = "WHERE ($by_fields_collection)";
		
		$q = "SELECT `$table`.`$field` FROM `$table` $where_conditions;";
		$ch = SQL_query($q, 'getField');
		
		if (!$sql -> errno) {
			$sql_result = $ch -> fetch_assoc();
			$ch -> close();			
			$result = (isset($sql_result[$field]) ? $sql_result[$field] : null);
		}
		
		return $result;
	}
	
	function getCount($table, $field, $by_fields='*', $logic = 'AND') {
		global $sql;
		$table = $sql -> real_escape_string($table);
		$field = $sql -> real_escape_string($field);
		$logic = $sql -> real_escape_string($logic);
		$logic = (in_array($logic, ['AND', 'OR']) ? $logic : 'AND');
		$by_fields_collection = array();
		$where_conditions = '';
		$result = -1;

		if (is_array($by_fields)) {
			if (!empty($by_fields)) {
				for ($i = 0; $i < count($by_fields); $i++) {
					$by_field = $sql -> real_escape_string($by_fields[$i][0]);
					$by_field_value = $sql -> real_escape_string($by_fields[$i][2]);
					$compare_logic = $sql -> real_escape_string($by_fields[$i][1]);
					$compare_logic = (in_array($compare_logic, ['=', '!=', '>', '<', '>=', '<=']) ? $compare_logic : '=');
					
					$by_fields_collection[] = "`$table`.`$by_field` $compare_logic '$by_field_value'";
				}
				$by_fields_collection = implode(" $logic ", $by_fields_collection);
			}
		}
		if (is_string($by_fields) && $by_fields == '*') $by_fields_collection = '1';
		$where_conditions = "WHERE ($by_fields_collection)";
		
		$q = "SELECT COUNT(`$table`.`$field`) AS `count` FROM `$table` $where_conditions;";
		$ch = SQL_query($q, 'getCount');
		
		if (!$sql -> errno) {
			$sql_result = $ch -> fetch_assoc();
			$ch -> close();			
			$result = (int)(isset($sql_result['count']) ? $sql_result['count'] : -1);
		}
		
		return $result;
	}
	
	function getSum($table, $file, $field, $by_fields='*', $logic = 'AND') {
		global $sql;
		$table = $sql -> real_escape_string($table);
		$field = $sql -> real_escape_string($field);
		$logic = $sql -> real_escape_string($logic);
		$logic = (in_array($logic, ['AND', 'OR']) ? $logic : 'AND');
		$by_fields_collection = array();
		$where_conditions = '';
		$result = -1;

		if (is_array($by_fields)) {
			if (!empty($by_fields)) {
				for ($i = 0; $i < count($by_fields); $i++) {
					$by_field = $sql -> real_escape_string($by_fields[$i][0]);
					$by_field_value = $sql -> real_escape_string($by_fields[$i][2]);
					$compare_logic = $sql -> real_escape_string($by_fields[$i][1]);
					$compare_logic = (in_array($compare_logic, ['=', '!=', '>', '<', '>=', '<=']) ? $compare_logic : '=');
					
					$by_fields_collection[] = "`$table`.`$by_field` $compare_logic '$by_field_value'";
				}
				$by_fields_collection = implode(" $logic ", $by_fields_collection);
			}
		}
		if (is_string($by_fields) && $by_fields == '*') $by_fields_collection = '1';
		$where_conditions = "WHERE ($by_fields_collection)";
		
		$q = "SELECT SUM(`$table`.`$field`) AS `sum` FROM `$table` $where_conditions;";
		echo $q;
		$ch = SQL_query($q, $file, 'getField');
		
		if (!$sql -> errno) {
			$sql_result = $ch -> fetch_assoc();
			$ch -> close();			
			$result = (float)(isset($sql_result['sum']) ? $sql_result['sum'] : -1);
		}
		
		return $result;
	}
	
	function insertFields($table, $fields) {
		global $sql;
		$insert_rowsNames = array();
		$insert_rowsValues = array();
		$table = $sql -> real_escape_string($table);
		
		if (!empty($fields)) {
			foreach ($fields as $field => $value) {
				
				if (!empty($value) && !is_null($value)) $value_field = "'{$sql -> real_escape_string($value)}'";
				else $value_field = 'NULL';				
				
				$insert_rowsNames[] = '`' . $sql -> real_escape_string($field) . '`';
				$insert_rowsValues[] = $value_field;
			}
			$insert_rowsNames = implode(',', $insert_rowsNames);
			$insert_rowsValues = implode(',', $insert_rowsValues);
			
			$q = "INSERT INTO `$table` ($insert_rowsNames) VALUES ($insert_rowsValues);";
			SQL_query($q, 'insertFields');
		}
		
		return $sql -> insert_id;
	}
	
	function insertManyFields($table, $fields) {
		global $sql;
		$insert_values_per_field_collection = array();
		$insert_keys_per_field_collection = array();
		$insert_values_collection = '';
		$insert_keys_collection = '';
		$table = $sql -> real_escape_string($table);
		$insert_collection = array();
		
		if (!empty($fields)) {
			for ($i = 0; $i < count($fields); $i++) {
				$fields_collection_arr = $fields[$i];
				
				if (!empty($fields_collection_arr)) {
					foreach ($fields_collection_arr as $key => $value) {
						$value = $sql -> real_escape_string($value);
						$key = $sql -> real_escape_string($key);
						
						if (is_numeric($value)) {
							$insert_values_per_field_collection[] = "$value";
						} else {
							$insert_values_per_field_collection[] = "'$value'";
						}
						$insert_keys_per_field_collection[] = "`$table`.`$key`";
					}
					$insert_values_collection = implode(', ', $insert_values_per_field_collection);
					$insert_keys_collection = implode(', ', $insert_keys_per_field_collection);
					
					$insert_collection[] = '(' . $insert_values_collection . ')';
					
					$insert_values_per_field_collection = [];
					$insert_keys_per_field_collection = [];
				}
			}
			$q = "INSERT INTO `$table` ($insert_keys_collection) VALUES " . implode(',', $insert_collection);
			SQL_query($q, 'insertManyFields');
		}
	}
	
	function updateFields($table, $fields, $by_fields='*', $logic='AND') {
		global $sql;
		$table = $sql -> real_escape_string($table);
		$logic = $sql -> real_escape_string($logic);
		$logic = (in_array($logic, ['AND', 'OR']) ? $logic : 'AND');
		$update_collection = array();
		$by_fields_collection = array();
		$where_conditions = '';
		$update_conditions = '';
		
		if (is_array($fields) && !empty($fields)) {
			foreach ($fields as $field => $value) {
				$field = $sql -> real_escape_string($field);
				$value = $sql -> real_escape_string($value);
				$update_collection[] = "`$table`.`$field` = '$value'";
			}
			$update_collection = implode(', ', $update_collection);
			$update_conditions = "SET $update_collection";
		}
		
		if (is_array($by_fields)) {
			if (!empty($by_fields)) {
				for ($i = 0; $i < count($by_fields); $i++) {
					$by_field = $sql -> real_escape_string($by_fields[$i][0]);
					$by_field_value = $sql -> real_escape_string($by_fields[$i][2]);
					$compare_logic = $sql -> real_escape_string($by_fields[$i][1]);
					$compare_logic = (in_array($compare_logic, ['=', '!=', '>', '<', '>=', '<=']) ? $compare_logic : '=');
					
					$by_fields_collection[] = "`$table`.`$by_field` $compare_logic '$by_field_value'";
				}
				$by_fields_collection = implode(" $logic ", $by_fields_collection);
			}
		}
		if (is_string($by_fields) && $by_fields == '*') $by_fields_collection = '1';
		$where_conditions = "WHERE ($by_fields_collection)";
		
		$q = "UPDATE `$table` $update_conditions $where_conditions;";
		SQL_query($q, 'updateFields');
	}
	
	function deleteFields($table, $by_fields, $logic='AND') {
		global $sql;
		$table = $sql -> real_escape_string($table);
		$logic = $sql -> real_escape_string($logic);
		$logic = (in_array($logic, ['AND', 'OR']) ? $logic : 'AND');
		$update_collection = array();
		$by_fields_collection = array();
		$where_conditions = '';
		
		if (is_array($by_fields)) {
			if (!empty($by_fields)) {
				for ($i = 0; $i < count($by_fields); $i++) {
					$by_field = $sql -> real_escape_string($by_fields[$i][0]);
					$by_field_value = $sql -> real_escape_string($by_fields[$i][2]);
					$compare_logic = $sql -> real_escape_string($by_fields[$i][1]);
					$compare_logic = (in_array($compare_logic, ['=', '!=', '>', '<', '>=', '<=']) ? $compare_logic : '=');
					
					$by_fields_collection[] = "`$table`.`$by_field` $compare_logic '$by_field_value'";
				}
				$by_fields_collection = implode(" $logic ", $by_fields_collection);
			}
		}
		if (is_string($by_fields) && $by_fields == '*') $by_fields_collection = '1';
		$where_conditions = "WHERE ($by_fields_collection)";
		
		$q = "DELETE FROM `$table` $where_conditions;";
		SQL_query($q, 'deleteFields');
	}
	
	function getFoundRows() {
		$ch = SQL_query('SELECT FOUND_ROWS() AS `found`;', 'get_found_rows');

		$result = array();
		$result = $ch -> fetch_assoc();
		$ch -> close();
		
		return (int)$result['found'];		
	}
	
	function rowExists($table, $field, $value) {
		return getField($table, $field, array([$field, '=', $value])) == $value;
	}
	
	function getDelimitedList($table, $file, $field, $by_fields='*', $delimiter=',') {
		$res = getRows($table, $file, $field, $by_fields);
		$result = array();
		
		if (!empty($res)) {
			for ($i = 0; $i < count($res); $i++) {
				$result[] = $res[$i][$field];
			}
			$result = implode($delimiter, $result);
		}
		
		return $result;
	}
	
	function assocArrayByField($table, $field, $value, $by_fields='*', $logic='AND', $order_by='', $order_type='ASC', $limit_start='', $limit_count='', $group_by='') {
		$fields = "$field,$value";
		$res = getRows($table, $fields, $by_fields, $logic, $order_by, $order_type, $limit_start, $limit_count, $group_by);
		$result = array();
		
		if (!empty($res)) {
			for ($i = 0; $i < count($res); $i++) {
				$result[$res[$i][$field]] = $res[$i][$value];
			}
		}
		
		return $result;
	}

	function getSepFieldsByField($table, $field, $by_fields='*', $sep=',', $logic='AND', $order_by='', $order_type='ASC', $limit_start='', $limit_count='', $group_by='') {
		$fields_collection = getRows($table, $field, $by_fields, $logic, $order_by, $order_type, $limit_start, $limit_count, $group_by);
		$result = array();
		
		if (!empty($fields_collection)) {
			for ($i = 0; $i < count($fields_collection); $i++) {
				$result[] = $fields_collection[$i][$field];
			}
		}
		
		return implode($sep, $result);
	}
?>