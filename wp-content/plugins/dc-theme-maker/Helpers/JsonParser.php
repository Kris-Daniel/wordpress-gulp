<?php

namespace Helpers;
class JsonParser
{
	public function getData($file)
	{
		try{
			// Read JSON file
			if(file_exists(__DIR__ . '/../config/' . $file . '.json')){
				$json = file_get_contents(__DIR__ . '/../config/' . $file . '.json');
			}
			//Decode JSON
			$json_data = json_decode($json,true);

			//Print data
			return ($json_data);
		} catch (Exception $e) { die($e); }
	}
}