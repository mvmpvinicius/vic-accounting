<?php
	set_time_limit(0);
	$time_start = microtime(true);
	$return = new stdClass();


	$collection = "diario_teste4";
	$tabela = "diario";
	$filename = "../rep/diario/3.txt";

	MongoCursor::$timeout = -1;
	$sql = 'INSERT INTO '.$tabela.' VALUES';

	for($aaa = 0; $aaa<1;$aaa++){

		$original_extension = (false === $pos = strrpos($filename, '.')) ? '' : substr($filename, $pos);

		$f = fopen($filename, "r");
		$preview_lines = array();
		$count_preview_lines = 0;
		$cont_obj = 0;
		$cont_obj_master = 0;
		$date_start_position = NULL;
		$date_end_position = NULL;
		$date_regex = "(?:\d{1,2})\/(?:\d{1,2})\/\d{4}";

		$cc_regex = "";
		$cc_start_position = NULL;
		$cc_end_position = NULL;
		$cc_digits = NULL;

		$side_debt = "";
		$line_length = 0;

		$columned = false;




		if($f) {
			if($post["preview"] == true){

				switch ($original_extension) {
					case '.csv':
						while (($line = fgetcsv($f)) !== false){
							if(count($line) > 1){
								$line = implode(",", $line);
							}else{
								$line = $line[0];
							}
							$line = str_replace(";", " ", $line);
							if(!empty($line)){
								if(validate_line($line)){
									if($line_length < strlen($line)){
										$line_length = strlen($line);
									}
									// $preview_lines[] = ($line);
									if($count_preview_lines <= 40){
										$preview_lines[] = utf8_encode($line);
										// break;
									}
									$count_preview_lines++;
								}
							}
						}
						break;
					case '.xls':
					case '.xlsx':
						# code...
						break;
					case '.txt':
						while (($line = fgets($f)) !== false){
							$line_trim = trim($line);
							if(!empty($line_trim)){
								if(validate_line($line)){
									if($line_length < strlen($line)){
										$line_length = strlen($line);
									}
									// $line = $line." - ".strlen($line);
									// $preview_lines[] = ($line);
									if($count_preview_lines <= 40){
										// echo $line;
										$preview_lines[] = utf8_encode($line);
										// break;
									}else{
										break;
									}
									$count_preview_lines++;
								}
							}
						}
					break;
				}
				$return->success = "ok";
				$return->error = "no_error";
				$return->range = $line_length;
				$return->value = $preview_lines;
			}else{
				do_process();
				switch ($original_extension) {
					case '.csv':
						while (($line = fgetcsv($f)) !== false){
							if(count($line) > 1){
								$line = implode(",", $line);
							}else{
								$line = $line[0];
							}
							$line = str_replace(";", " ", $line);
							if(!empty($line)){
								if(validate_line($line)){
									if($columned){
										main_columned($line);
									}else{
										main($line);
									}
								}
							}
						}
						if($columned){
							if($cont_obj > 0){
								$_MY->query(rtrim($sql, ","));
							}
						}
						break;
					case '.xls':
					case '.xlsx':
						# code...
						break;
					case '.txt':
						while (($line = fgets($f)) !== false){
							if(!empty($line)){
								if($columned){
									main_columned($line);
								}else{
									$line = trim($line);
									main($line);
								}
							}
						}
						if($columned){
							if($cont_obj > 0){
								$_MY->query(rtrim($sql, ","));
							}
						}
						break;
				}
				$return->success = "ok";
				$return->error = "no_error";
				$return->linhas = $cont_obj_master;
			}
			fclose($f);

		}else{
			$return->success = "error";
			$return->error = "file_open";
			$return->value = array();
		}
	}
	// $time_end = microtime(true);

	// //dividing with 60 will give the execution time in minutes other wise seconds
	// $execution_time = ($time_end - $time_start)/60;

	// //execution time of the script
	// echo '<b>Total Execution Time:</b> '.$execution_time.' Mins';
	echo json_encode($return);

	// Valida linhas até encontrar a primeira linha que tenha valor valido para iniciar o processo de importação
	function validate_line($line){
		$values = get_debito_credito_linha($line);

		// $count_matchs = preg_match("/(?:\d{1,2})\/(?:\d{1,2})\/\d{4}/", $line, $match, PREG_OFFSET_CAPTURE);
  //   	if($count_matchs == 1){
  //   		$date = true;
  //   	}else{
  //   		$date = false;
  //   	}

		// if($values > 0 || $date == true){
		// 	return true;
		// }else{
		// 	return false;
		// }
		return true;
	}

	function main_preview($line){
		global $count_preview_lines;
		global $preview_lines;
		$preview_lines[] = utf8_encode($line);
		$count_preview_lines++;
	}

	function main($line){
		global $collection;
		global $tabela;
		global $side_debt;
		global $cont_obj;
		global $cc_regex;
		global $_MY;
		$final_obj = new stdClass();

		$date_match = get_date_line($line);
		$final_obj->date = $date_match[0];
		$account_match = get_numero_conta($line, $cc_regex);
		$final_obj->account = $account_match[0];
		$values = get_debito_credito_linha($line);
		if($side_debt == "left"){
			$final_obj->debt = convert_money($values[0][0][0]);
			$final_obj->credit = convert_money($values[0][1][0]);
		}else{
			$final_obj->credit = convert_money($values[0][0][0]);
			$final_obj->debt = convert_money($values[0][1][0]);
		}

		$count_remove = 0;
		$arr_position_matchs = array();
		$arr_position_matchs[$date_match[1]] = strlen($date_match[0]);
		$arr_position_matchs[$values[0][0][1]] = strlen($values[0][0][0]);
		$arr_position_matchs[$values[0][1][1]] = strlen($values[0][1][0]);

		ksort($arr_position_matchs);
		$concept = $line;
		foreach($arr_position_matchs as $k_item => $v_item){
			$concept = substr_replace($concept, '-', $k_item - $count_remove, $v_item + $k_item);
			$count_remove += $v_item;
		}
		$concept = trim(str_replace($account_match[0], '', $concept));
		$final_obj->concept = utf8_encode($concept);
		$cont_obj++;
		// $aaa->b = "aaa";$_M->diario_teste->insert($aaa);exit();
		// $_M->diario_teste->insert($final_obj);
		// echo 'INSERT INTO diario VALUES(null, "'.$final_obj->date.'", "'.$final_obj->account.'", "'.$final_obj->debt.'", "'.$final_obj->credit.'", "'.addslashes($final_obj->concept).'")';exit;
		// grava('INSERT INTO diario VALUES(null, "'.$final_obj->date.'", "'.$final_obj->account.'", "'.$final_obj->debt.'", "'.$final_obj->credit.'", "'.addslashes($final_obj->concept).'")');
		// exit();
		// print_r($final_obj);
		// if($cont_obj == 20){
		// 	exit();
		// }
		if(CONNECTOR_DB == "MONGODB"){
			$_M->$collection->insert($final_obj);
		}

		if(CONNECTOR_DB == "MYSQL"){
			if($cont_obj == 7000){
				$sql .= '(null, "'.$final_obj->date.'", "'.$final_obj->account.'", "'.$final_obj->debt_value.'", "'.$final_obj->credit_value.'", "'.addslashes($final_obj->concept).'", "'.addslashes($final_obj->title).'", "'.addslashes($final_obj->doc1).'", "'.addslashes($final_obj->doc2).'")';
				$_MY->query($sql);

				$cont_obj = 0;
				$sql = 'INSERT INTO '.$tabela.' VALUES';
			}else{
				$sql .= '(null, "'.$final_obj->date.'", "'.$final_obj->account.'", "'.$final_obj->debt_value.'", "'.$final_obj->credit_value.'", "'.addslashes($final_obj->concept).'", "'.addslashes($final_obj->title).'", "'.addslashes($final_obj->doc1).'", "'.addslashes($final_obj->doc2).'"),';
			}
		}
	}

	function main_columned($line){
		global $collection;
		global $tabela;
		global $post;
		global $cont_obj;
		global $cont_obj_master;
		global $_MY;
		global $sql;
		$final_obj = new stdClass();
		$answers = $post["answers"];

		$lines_replace = new stdClass();
		$lines_replace->line_date = substr($line, (int) $answers["question0.1.1"]["date"]["min"], (int) ($answers["question0.1.1"]["date"]["max"] - $answers["question0.1.1"]["date"]["min"]));
		$date_match = get_date_line($lines_replace->line_date);
		$final_obj->date =  date("Y-m-d", strtotime(str_replace('"', "",$date_match[0])));


		// BEGIN ACCOUNTS

		if($answers["question0.1.1"]["account"]["min"] == $answers["question0.1.1"]["account"]["max"]){
			$final_obj->account	= 0;
		}else{
			$lines_replace->line_account = substr($line, (int) $answers["question0.1.1"]["account"]["min"], (int) ($answers["question0.1.1"]["account"]["max"] - $answers["question0.1.1"]["account"]["min"]));
			$account_match = get_numero_conta($lines_replace->line_account);
			$final_obj->account =  str_replace('"', "",$account_match);
		}

		if($answers["question0.1.1"]["debt_account"]["min"] == $answers["question0.1.1"]["debt_account"]["max"]){
			$final_obj->debt_account = 0;
		}else{
			$lines_replace->line_debt_account = substr($line, (int) $answers["question0.1.1"]["debt_account"]["min"], (int) ($answers["question0.1.1"]["debt_account"]["max"] - $answers["question0.1.1"]["debt_account"]["min"]));
			$account_match = get_numero_conta($lines_replace->line_debt_account);
			$final_obj->debt_account =  str_replace('"', "",$account_match);
		}

		if($answers["question0.1.1"]["credit_account"]["min"] == $answers["question0.1.1"]["credit_account"]["max"]){
			$final_obj->credit_account = 0;
		}else{
			$lines_replace->line_credit_account = substr($line, (int) $answers["question0.1.1"]["credit_account"]["min"], (int) ($answers["question0.1.1"]["credit_account"]["max"] - $answers["question0.1.1"]["credit_account"]["min"]));
			$account_match = get_numero_conta($lines_replace->line_credit_account);
			$final_obj->credit_account =  str_replace('"', "",$account_match);
		}

		// END ACCOUNTS

		// BEGIN VALUES

		if($answers["question0.1.1"]["value"]["min"] == $answers["question0.1.1"]["value"]["max"]){
			$final_obj->value = 0;
		}else{
			$lines_replace->line_value = substr($line, (int) $answers["question0.1.1"]["value"]["min"], (int) ($answers["question0.1.1"]["value"]["max"] - $answers["question0.1.1"]["value"]["min"]));
			$value_match = get_debito_credito_linha($lines_replace->line_value);
			if(!empty($value_match[0][0][0])){
				$final_obj->value = convert_money(str_replace('"', "",$value_match[0][0][0]));
			}else{
				$final_obj->value = 0;
			}
		}

		if($answers["question0.1.1"]["debt_value"]["min"] == $answers["question0.1.1"]["debt_value"]["max"]){
			$final_obj->debt_value = 0;
		}else{
			$lines_replace->line_debt_value = substr($line, (int) $answers["question0.1.1"]["debt_value"]["min"], (int) ($answers["question0.1.1"]["debt_value"]["max"] - $answers["question0.1.1"]["debt_value"]["min"]));
			$value_match = get_debito_credito_linha($lines_replace->line_debt_value);
			if(!empty($value_match[0][0][0])){
				$final_obj->debt_value = convert_money(str_replace('"', "",$value_match[0][0][0]));
			}else{
				$final_obj->debt_value = 0;
			}
		}

		if($answers["question0.1.1"]["credit_value"]["min"] == $answers["question0.1.1"]["credit_value"]["max"]){
			$final_obj->credit_value = 0;
		}else{
			$lines_replace->line_credit_value = substr($line, (int) $answers["question0.1.1"]["credit_value"]["min"], (int) ($answers["question0.1.1"]["credit_value"]["max"] - $answers["question0.1.1"]["credit_value"]["min"]));
			$value_match = get_debito_credito_linha($lines_replace->line_credit_value);
			// echo $line." - ".$line_credit_value.PHP_EOL;
			if(!empty($value_match[0][0][0])){
				$final_obj->credit_value = convert_money(str_replace('"', "",$value_match[0][0][0]));
			}else{
				$final_obj->credit_value = 0;
			}
		}

		if($final_obj->value == 0 &&  $final_obj->debt_value == 0 &&  $final_obj->credit_value == 0 || (!is_numeric(str_replace(",",".", str_replace(".","", $final_obj->value))) || !is_numeric(str_replace(",",".", str_replace(".","", $final_obj->debt_value))) || !is_numeric(str_replace(",",".", str_replace(".","", $final_obj->credit_value))))){
			return false;
		}
		$final_obj->entry = $lines_replace->entry = str_replace('"', "",trim(substr($line, (int) $answers["question0.1.1"]["entry"]["min"], (int) ($answers["question0.1.1"]["entry"]["max"] - $answers["question0.1.1"]["entry"]["min"]))));
		$final_obj->doc1 = $lines_replace->doc1 = str_replace('"', "",trim(substr($line, (int) $answers["question0.1.1"]["doc1"]["min"], (int) ($answers["question0.1.1"]["doc1"]["max"] - $answers["question0.1.1"]["doc1"]["min"]))));
		$final_obj->doc2 = $lines_replace->doc2 = str_replace('"', "",trim(substr($line, (int) $answers["question0.1.1"]["doc2"]["min"], (int) ($answers["question0.1.1"]["doc2"]["max"] - $answers["question0.1.1"]["doc2"]["min"]))));
		$final_obj->title = utf8_encode($lines_replace->title = str_replace('"', "",trim(substr($line, (int) $answers["question0.1.1"]["title"]["min"], (int) ($answers["question0.1.1"]["title"]["max"] - $answers["question0.1.1"]["title"]["min"])))));
		$final_obj->concept =  utf8_encode($lines_replace->concept = str_replace('"', "",trim(substr($line, (int) $answers["question0.1.1"]["concept"]["min"], (int) ($answers["question0.1.1"]["concept"]["max"] - $answers["question0.1.1"]["concept"]["min"])))));


		if(CONNECTOR_DB == "MONGODB"){
			// print_r($final_obj);
			$_M->$collection->insert($final_obj);
		}

		if(CONNECTOR_DB == "MYSQL"){
			if($cont_obj == 7000){
				$sql .= '(null, "'.$final_obj->date.'", "'.$final_obj->account.'", "'.$final_obj->debt_value.'", "'.$final_obj->credit_value.'", "'.addslashes($final_obj->concept).'", "'.addslashes($final_obj->title).'", "'.addslashes($final_obj->doc1).'", "'.addslashes($final_obj->doc2).'")';
				$_MY->query($sql);

				$cont_obj = 0;
				$sql = 'INSERT INTO '.$tabela.' VALUES';
			}else{
				$sql .= '(null, "'.$final_obj->date.'", "'.$final_obj->account.'", "'.$final_obj->debt_value.'", "'.$final_obj->credit_value.'", "'.addslashes($final_obj->concept).'", "'.addslashes($final_obj->title).'", "'.addslashes($final_obj->doc1).'", "'.addslashes($final_obj->doc2).'"),';
			}
		}

		$cont_obj++;
		$cont_obj_master++;
	}



	// Applies decisions by user's answers
	function do_process(){
		global $post;
		global $date_start_position;
		global $date_end_position;
		global $cc_start_position;
		global $cc_end_position;
		global $cc_digits;
		global $side_debt;
		global $cc_regex;
		global $columned;
		global $date_regex;

		$answers = $post["answers"];

		if($answers["question1.1"] == "yes"){
			$years_number = substr_count($answers["question1"], "y");
			$positions->days = stripos($answers["question1"], "dd");
			$positions->mounths = stripos($answers["question1"], "mm");
			$positions->years = stripos($answers["question1"],  str_pad("yy", $years_number, "y", STR_PAD_LEFT));
			$positions->char =  preg_replace('{(.)\1+}','$1', str_replace(str_pad("yy", $years_number, "y", STR_PAD_LEFT), "", str_replace("mm", "", str_replace("dd", "",$answers["question1"]))));

			if($positions->years == 0){
				$date_regex = "\d{".$years_number."}\\".$positions->char."(?:\d{1,2})\\".$positions->char."(?:\d{1,2})";
			}else{
				$date_regex = "(?:\d{1,2})\\".$positions->char."(?:\d{1,2})\\".$positions->char."\d{".$years_number."}";
			}
		}


		if($answers["question0.1"] == "yes"){
			$columned = true;
			foreach($post["answers"]["question0.1.1"] as $k => $v){
				if($v["min"] == -1){
					$post["answers"]["question0.1.1"][$k]["min"] = 0;
				}
			}
		}


		if($answers["question1.1"] == "yes"){
			if($answers["question1.1.1"]["positions"][0]["start"] == $answers["question1.1.1"]["positions"][1]["start"] &&
				$answers["question1.1.1"]["positions"][0]["start"] == $answers["question1.1.1"]["positions"][2]["start"] &&
				$answers["question1.1.1"]["positions"][1]["start"] == $answers["question1.1.1"]["positions"][2]["start"]){
				$date_start_position = $answers["question1.1.1"]["positions"][0]["start"];
				$date_end_position = ($answers["question1.1.1"]["positions"][0]["end"] - $answers["question1.1.1"]["positions"][0]["start"]) + 10;
			}
		}else{
			if($answers["question1.2"] == "above"){
				$date_start_position = 0;
				$date_end_position = -1;
			}

		}

		if($answers["question2.1"] == "no"){
			$array_cases = array();
			$account_cases = explode("##", $answers["question2.2"]);
			if(count($account_cases) > 0){
				foreach($account_cases as $case){

					$chars = str_replace("9", "", $case);
					$chars_count = count_chars($chars, 3);
					$chars_cases = str_replace("9", "\d", $case);
					for($i=0; $i < count($chars_count); $i++){
						$chars_cases = str_replace($chars_count[$i], "\\".$chars_count[$i] , $chars_cases);
					}

					$array_cases[] = $chars_cases;
				}

				usort($array_cases, 'sort_regex');
				$cc_regex = implode("|", $array_cases);
			}
		}else{
			if($answers["question2.1.1"] == "no"){
				$array_cases = array();
				$account_cases = explode("##", $answers["question2.1.2"]);
					if(count($account_cases) > 0){
						arsort($account_cases);
						foreach($account_cases as $case){
							$array_cases[] = "\d{".$case."}";
						}
						$cc_regex = implode("|", $array_cases);
					}
			}else{
				if($answers["question2.1"]["positions"][0]["start"] == $answers["question2.1"]["positions"][1]["start"] &&
					$answers["question2.1"]["positions"][0]["start"] == $answers["question2.1"]["positions"][2]["start"] &&
					$answers["question2.1"]["positions"][1]["start"] == $answers["question2.1"]["positions"][2]["start"]){
					$cc_start_position = $answers["question2.1"]["positions"][0]["start"];
					$cc_end_position = ($answers["question2.1"]["positions"][0]["end"] - $answers["question2.1"]["positions"][0]["start"]);
					$cc_digits = strlen(trim($answers["question2.1"]["selections"][0]));
				}
			}
		}

		if($answers["question3.1"]["positions"][0]["start"] < $answers["question4.1"]["positions"][1]["start"]){
			$side_debt = "left";
		}else{
			$side_debt = "right";
		}
	}

	function get_date_line($line){
		global $date_start_position;
		global $date_end_position;
		global $date_regex;
		global $columned;
		if($columned == false){
			if($date_end_position = -1){
				$string_line = substr($line, $date_start_position);
			}else{
				$string_line = substr($line, $date_start_position, $date_end_position);
			}
		}else{
			$string_line = trim($line);
		}
		$count_matchs = preg_match("/".$date_regex."/", $string_line, $match, PREG_OFFSET_CAPTURE);
    	if($count_matchs == 1){
    		return $match[0];
    	}else{
    		return null;
    	}
	}

	function get_numero_conta($line, $regex = ""){
		global $cc_start_position;
		global $cc_end_position;
		global $cc_digits;
		global $columned;

		if($columned == false){
			if(empty($regex)){
				$string_line = substr($line, ($cc_start_position - $cc_start_position / 2), $cc_end_position + 20);
				$count_matchs = preg_match_all("/(?<!\B)[0-9]{".$cc_digits."}(?!\B)/", $string_line, $match, PREG_OFFSET_CAPTURE);
			}else{
				$count_matchs = preg_match_all("/".$regex."/", trim($line), $match, PREG_OFFSET_CAPTURE);
			}
	    	if($count_matchs = 2){
	    		return $match[0][0];
	    	}else{
				return null;
			}
		}else{
			return trim($line);
		}
	}

	function get_debito_credito_linha($line){
		$count_matchs = preg_match_all("/[0-9.]*\,[0-9][0-9]/",  $line, $match, PREG_OFFSET_CAPTURE);
    	if($count_matchs > 0){
    		return $match;
    	}else{
    		return null;
    	}
	}

	function sort_regex($a,$b){
    	return strlen($b)-strlen($a);
	}

	function convert_money($value){
		$v_value = str_replace(",", ".", str_replace(".", "", $value));
		return (double) $v_value;
	}

?>