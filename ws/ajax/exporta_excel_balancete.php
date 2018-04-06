<?php

	error_reporting(E_ALL);

	require_once 'lib/ext/PHPExcel.php';
	require_once 'lib/ext/PHPExcel/Writer/Excel2007.php';
	require_once 'lib/ext/PHPExcel/IOFactory.php';

	$trial_balance = $_MY->query('SELECT account FROM trial_balance');
	$periodo = 'mensal';

	$objPHPExcel = new PHPExcel();
	$objPHPExcel->setActiveSheetIndex(0);

	$legendas = array('Data' => 'A',
					  'Conta' => 'B',
					  'Título' => 'C',
					  'Total Débito' => 'D',
					  'Total Crédito' => 'E',
					  'Saldo' => 'F');

	$letras = array('date' => 'A', 
					'account' => 'B', 
					'title' => 'C',
					'soma_debito' => 'D',
					'soma_credito' => 'E',
					'saldo' => 'F');
	$linha = '1';

	foreach ($legendas as $legendas_key => $legendas_value) {
		$valor = $legendas[$legendas_key] . $linha;
		$objPHPExcel->getActiveSheet()->SetCellValue($valor, $legendas_key);
	}
	$linha++;

	switch ($periodo) {
		case 'mensal':
			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito, SUM(debt) - SUM(credit) as saldo FROM diario GROUP BY account, month(date)');
			break;
		case 'trimestral':
			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito, SUM(debt) - SUM(credit) as saldo FROM diario GROUP BY account, quarter(date)');
			break;
		case 'semestral':
			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito, SUM(debt) - SUM(credit) as saldo FROM diario GROUP BY account, IF(month(date) < 7, 1, 2)');
			break;
		case 'anual':
			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito, SUM(debt) - SUM(credit) as saldo FROM diario GROUP BY account');
			break;
	}

	foreach ($diario as $diario_key => $diario_value) {
		foreach ($diario_value as $diario_value_key => $diario_value_value) {
			$valor = $letras[$diario_value_key] . $linha;
			$objPHPExcel->getActiveSheet()->SetCellValue($valor, $diario_value_value);
		}
		$linha++;
	}

	// foreach ($trial_balance as $trial_balance_value) {
	// 	switch ($periodo) {
	// 		case 'mensal':
	// 			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito 
	// 								   FROM diario WHERE account = "' . $trial_balance_value['account'] . '" GROUP BY month(date)');
	// 			break;
	// 		case 'trimestral':
	// 			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito 
	// 								   FROM diario WHERE account = "' . $trial_balance_value['account'] . '" GROUP BY quarter(date)');
	// 			break;
	// 		case 'semestral':
	// 			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito 
	// 								   FROM diario WHERE account = "' . $trial_balance_value['account'] . '" GROUP BY IF(month(date) < 7, 1, 2)');
	// 			break;
	// 		case 'anual':
	// 			$diario = $_MY->query('SELECT date, account, title, SUM(debt) as soma_debito, SUM(credit) as soma_credito 
	// 								   FROM diario WHERE account = "' . $trial_balance_value['account'] . '"');
	// 			break;
	// 	}

	// 	foreach ($legendas as $legendas_key => $legendas_value) {
	// 		$valor = $legendas[$legendas_key] . $linha;
	// 		$objPHPExcel->getActiveSheet()->SetCellValue($valor, $legendas_key);
	// 	}
	// 	$linha++;

	// 	foreach ($diario as $diario_key => $diario_value) {
	// 		foreach ($diario_value as $diario_value_key => $diario_value_value) {
	// 			$valor = $letras[$diario_value_key] . $linha;
	// 			$objPHPExcel->getActiveSheet()->SetCellValue($valor, $diario_value_value);
	// 		}
	// 		$linha++;
	// 	}
	// 	$linha++;
	// }

	$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
	$objWriter->save('novoarquivo.xls');

?>