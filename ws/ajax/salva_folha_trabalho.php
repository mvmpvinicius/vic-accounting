<?php 

	$m = new MongoClient();
	$_M = $m->contabilidade;

	$myFile = "../rep/folhas_trabalho_id_cliente/" . $post["nome_folha_trabalho"] . ".json";
	$fh = fopen($myFile, "w") or die("can't open file");
	$stringData = $post["mydata"];
	fwrite($fh, $stringData);
	fclose($fh);

	$arr_folha_trabalho = ["caminho" => $myFile];
	$_M->folha_trabalho_teste->insert($arr_folha_trabalho);

?>