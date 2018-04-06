<?php
    require_once("phpGrid/conf.php"); // relative path to conf.php without leading slash

    $dg = new C_DataGrid("SELECT account, debt, credit, outstanding_balance, id, id_diario FROM trial_balance", "id", "Balancete");

    $dg->set_multiselect(true);
    $dg->set_col_hidden('id');
    $dg->set_col_hidden('id_diario');
    $dg->set_col_title("account", "Conta");
    $dg->set_col_title("outstanding_balance", "Saldo");
    $dg->set_col_title("debt", "Débito");
    $dg->set_col_title("credit", "Crédito");

    $dg->display();
?>
