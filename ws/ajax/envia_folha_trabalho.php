<?php 

	if (CONNECTOR_DB == "MYSQL") {
		$string = '[';
		foreach ($post['rows'] as $key => $value) {
			$mysql_obj = $_MY->query('SELECT * FROM diario WHERE id = ' . $value);
			$string .= '[';
			foreach ($mysql_obj as $key2 => $value2) {
				foreach ($value2 as $value3) {
					$string .= '"' . $value3 . '",';
				}
			}
			$string = rtrim($string, ',');
			$string .= '],';
		}
		$string = rtrim($string, ',');
		$string .= ']';
		string_contruction($string);
	} elseif (CONNECTOR_DB == "MONGODB") {
		$string = '[';
		foreach ($post['rows'] as $key => $value) {
			$mongo_obj = $_M->diario_teste3->findOne(array('_id' => new MongoId($key)));
			$string .= '[';
			foreach ($mongo_obj as $key2 => $value2) {
				$string .= '"' . $value2 . '",';
			}
			$string = rtrim($string, ',');
			$string .= '],';
		}
		$string = rtrim($string, ',');
		$string .= ']';
		string_contruction($string);
	}

	function string_contruction ($string) {
		global $post;
		$myFile = "../rep/folhas_trabalho_id_cliente/" . $post['nome_folha_trabalho'] . ".json";
		$fh = fopen($myFile, "w") or die("can't open file");
		fwrite($fh, $string);
		fclose($fh);
	}

?>