<?php


require_once("db.php");
/* ДЛЯ РАБОТЫ СКРИПТА НЕОБХОДИМО 
 * ЗАПОЛНИТЬ ДАННЫЕ ДЛЯ СОЕДИНЕНИЯ С БД
 * db_conf.php :
 $DB_HOST = ...
 $DB_USER =
 $DB_PASS =
 $DB_NAME =
 * Настроенный вариант работы скрипта по адресу:
 * http://tkt-online.ru/majesti/
*/
require_once("db_conf.php");
DEFINE ('TABLE_NAME', 'data' );
DEFINE ('FILE_NAME' , TABLE_NAME . ".csv");

$DB = DB::getInstance($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

$csv = array_map('str_getcsv', file(FILE_NAME));
foreach($csv as &$c)
{
	foreach($c as $k => $v){
		$c[$k] = iconv("CP1251", "UTF-8", $v);	
	}

}

if ( $DB->table_exists(TABLE_NAME) )
{
	$rec = $DB->get_random_record(TABLE_NAME);
	$new_status = ($rec['Статус'] == 0 ? 1 : 0);
	$DB->update( TABLE_NAME, array('Статус' => $new_status), array('id' => $rec['id']) );
	$rec = $DB->select_row( TABLE_NAME, array('id' => $rec['id']) );
	echo $rec['Имя'] . ' ' . $rec['Статус'];
}
else
{
	$table_fields = array_shift($csv);
	$table_fields = explode(';',$table_fields[0]);
	$table_values = $csv;
	
	foreach($table_values as &$t)
	foreach($t as $k => $v)
	{
			$temp = explode(';', $v);
			$temp = $DB->escape($temp);
			$t[$k] = $temp;
	}
	$DB->create_table(TABLE_NAME, $table_fields);
	$DB->insert_multi(TABLE_NAME, $table_fields, $table_values);
}





