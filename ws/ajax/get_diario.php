<?php
	require_once("../config.php");

	set_time_limit(0);


	$collection = "diario_teste3";

	$return = new StdClass();

	$return->recordsTotal = $_M->$collection->count();

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
		$data = $_M->$collection->aggregate(
			array(
				array(
					'$project' => array(
					    "date" => 1,
					    "entry" => 1,
					    "account" => 1,
					    "debt_account" => 1,
					    "credit_account" => 1,
					    "value" => 1,
					    "debt_value" => 1,
					    "credit_value" => 1,
					    "concept" => 1,
					    "title" => 1,
					    "doc1" => 1,
					    "doc2" => 1
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
			array('account' =>  $regex),
			array('date' =>  $regex),
			array('entry' =>  $regex),
			array('title' =>  $regex),
			array('doc1' =>  $regex),
			array('doc2' =>  $regex),
			array('concept' =>  $regex)
		);
		$mongo_obj = $_M->$collection->find(array('$or' => $fields_filter))->skip($_POST["start"])->limit($_POST["start"] + $_POST["length"])->sort($sort);
		$return->data = iterator_to_array($mongo_obj, false);
		$return->recordsFiltered = $_M->$collection->count(array('$or' => $fields_filter));
	}


	echo json_encode($return);
?>