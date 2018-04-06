	<?php
	require_once("../config.php");

	set_time_limit(0);
	$return = new StdClass();

	$return->recordsTotal = $_M->trial_balance->count();

	$return->draw = (int)$_POST["draw"] + 1;

	if($_POST["order"][0]["dir"] == "asc"){
		$sort = array($_POST["columns"][$_POST["order"][0]["column"]]["data"] => 1);
	}else{
		$sort = array($_POST["columns"][$_POST["order"][0]["column"]]["data"] => -1);
	}

	if(empty($_POST["order"][0]["column"])){
		$sort = array('_id' =>1);
	}

	if(empty($_POST["search"]["value"])){
		$data = $_M->trial_balance->aggregate(
			array(
				array(
					'$project' => array(
					    "id_diario" => 1,
					    "account" => 1,
					    "debt" => 1,
					    "credit" => 1,
					    "outstanding_balance" => 1
					)
				),
				array('$sort' => $sort),
				array('$limit' => ( (int) $_POST["start"]) + ( (int) $_POST["length"]) ),
				array('$skip' => ( (int) $_POST["start"]) )
			)
		);
		$return->data = $data["result"];
		unset($data);
		$return->recordsFiltered = $return->recordsTotal;
	}else{
		$regex = new MongoRegex("/".$_POST["search"]["value"]."/i");
		$fields_filter = array(
			array('account' =>  $regex)
			// array('debt' =>  $regex),
			// array('credit' =>  $regex),
			// array('outstanding_balance' =>  $regex)
		);

		$mongo_obj = $_M->trial_balance->find(array('$or' => $fields_filter))->skip($_POST["start"])->limit($_POST["start"] + $_POST["length"])->sort($sort);
		$return->data = iterator_to_array($mongo_obj, false);
		$return->recordsFiltered = $_M->trial_balance->count(array('$or' => $fields_filter));
	}


	echo json_encode($return);
?>