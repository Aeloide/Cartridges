<?php

	class sql_mysql{
		var $dbl;
		var $debug;
		var $num_queries;
		var $queries;
		var $logfile;
		var $logenabled;
		var $errfunction;
		
		function __construct(){
			$this->logenabled = false;			
			$this->logfile = __DIR__."/sql_errors.txt";
			$this->errfunction = false;
		}

		function connect($host, $user, $password, $database, $debug = 0){
			$this->dbl = mysqli_connect($host, $user, $password);
			if(!$this->dbl) die('Нет связи с сервером базы данных. Вероятная причина - технические работы.');
			$db = mysqli_select_db($this->dbl, $database);
			$this->num_queries = 0;
			$this->debug = $debug ? 1 : 0;
			$this->queries = array();
			return $db;
		}

		function result($handle, $num = 0, $file = '', $line = 0){
			if(!is_object($handle)){
				$handle = $this->query($handle, $file, $line);
			}
	        mysqli_data_seek($handle, $num);
	        if($row = mysqli_fetch_array($handle)){
				return $row[$num];
			}
		}

		function num_rows($handle, $file = '', $line = 0){
			if(!is_object($handle)){
				$handle = $this->query($handle, $file, $line);
			}
			if($result = mysqli_num_rows($handle)){
				return $result;
			}
		}

		function fetch_assoc($handle, $file = '', $line = 0){
			if(!is_object($handle)){
				$handle = $this->query($handle, $file, $line);
			}
			if($result = mysqli_fetch_assoc($handle)){
				return $result;
			}
		}

		function escape($value){
			return mysqli_real_escape_string($this->dbl, $value);
		}

		function fetch_array($handle){
			$result = array();
			if(!is_object($handle)){
				$handle = $this->query($handle);
			}
			while($row = mysqli_fetch_array($handle)){
				$result[] = $row[0];
			}
			return $result;
		}

		function query($query, $file = '', $line = 0){
	    	global $queries;
	    	if($this->debug){ array_push($this->queries, $query); }
	    	if($result = mysqli_query($this->dbl, $query)){
				$this->num_queries++;
				return $result;
	    	}else{
				$this->show_error($query, $file, $line);
	    	}
		}
		
		function show_error($query, $file = '', $line = 0){
			$sapi = php_sapi_name();
    		$log['errno'] = mysqli_errno($this->dbl);
    		$log['error'] = mysqli_error($this->dbl);
			$log['file'] = $file;
			$log['line'] = $line;			
    		$log['query'] = $query;
			if($sapi == 'cli'){
				print "Произошла ошибка MySQL\nОшибка: ".$log['errno'].', '.$log['error']."\nЗапрос: ".$query."\n";
			}else{			
				$message = '
<table>
<tr><td>Произошла ошибка MySQL</td></tr>
<tr><td>Время: '.date("Y-m-d H:i:s").'</td></tr>
<tr><td>Тип запуска: '.$sapi.'</td></tr>
<tr><td>Ошибка: '.$log['errno'].', '.$log['error'].'</td></tr>
<tr><td>Запрос: '.$query.'</td></tr>
<tr><td>Файл: '.(($file == '') ? '<i>не указано</i>' : basename($file)).'</td></tr>
<tr><td>Строка: '.(($line == 0) ? '<i>не указано</i>' : $line).'</td></tr>			
</table>


';
				if($this->logenabled == true){
					if(!file_exists($this->logfile)){
						file_put_contents($this->logfile, strip_tags($message));						
					}else{
						file_put_contents($this->logfile, strip_tags($message), FILE_APPEND);						
					}
				}
				
				if($this->errfunction !== false){
					if(function_exists($this->errfunction)){
						call_user_func($this->errfunction, $log);
					}
				}
				
				print $message;			  
			}
			exit;			
		}
	}

?>