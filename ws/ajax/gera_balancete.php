<?php
set_time_limit(0);
require_once("../config.php");

$collection = "diario_teste4";
$tabela = "diario2";

$time_start = microtime(true);

do_trial_balance();

$time_end = microtime(true);

$execution_time = ($time_end - $time_start)/60;

echo '<b>Total Execution Time:</b> '.$execution_time.' Mins';

function do_trial_balance(){
	global $_M;
	global $_MY;
	global $collection;
	global $tabela;

	if(CONNECTOR_DB == "MYSQL"){
		$sql_insert = 'INSERT INTO trial_balance VALUES ';
		$result = $_MY->query('SELECT account, SUM(credit) as credit_sum, SUM(debt) as debt_sum FROM '.$tabela.' group by account');
		$cont_insert = 0;
		$id_diario = 1;
		while($row = $result->fetch_assoc()){
			$cont_insert++;
			if($cont_insert > 5000){
				$sql_insert .= '(null, "'.$row["account"].'", '.($row["debt_sum"] - $row["credit_sum"]).', '.$row["debt_sum"].', '.$row["credit_sum"].', '.$id_diario.' )';
				$_MY->query($sql_insert);
				$cont_insert = 0;
				$sql_insert = 'INSERT INTO trial_balance VALUES ';
			}else{
				$sql_insert .= '(null, "'.$row["account"].'", '.($row["debt_sum"] - $row["credit_sum"]).', '.$row["debt_sum"].', '.$row["credit_sum"].', '.$id_diario.' ),';
			}
		}
		if($cont_insert > 0){
			$_MY->query(rtrim($sql_insert, ","));
		}
	}elseif(CONNECTOR_DB == "MONGODB"){
		$mongo_obj = $_M->$collection->aggregate(array(
		array(
		'$project' => array(
		    "account" => 1,
		    "credit_value" => 1,
		    "debt_value" => 1
		)
		),
		array('$group' => array('_id' => '$account',
							'debt_sum' => array('$sum' => '$debt_value'),
							'credit_sum' => array('$sum' => '$credit_value'),
							'count' => array('$sum' => 1)
						    )
		)
		));
		foreach ($mongo_obj["result"] as $k_obj => $v_obj) {
			$insert_obj = new stdClass();
			$insert_obj->id_diario = 1;
			$insert_obj->account = $v_obj["_id"];
			$insert_obj->outstanding_balance = $v_obj["debt_sum"] - $v_obj["credit_sum"];
			$insert_obj->debt = $v_obj["debt_sum"];
			$insert_obj->credit = $v_obj["credit_sum"];
			$_M->trial_balance->insert($insert_obj);
			unset($mongo_obj["result"][$k_obj]);
		}
	}
}

?>