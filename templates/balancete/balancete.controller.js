var app = angular.module('app');

app.controller('balanceteController', function($scope, $http, S_vars, $sce) {
    var $balancete = this;
    $balancete.titulo = "Balancete";

    $balancete.iniciar_master_detail = function() {
        var obj_ajax = {};
        obj_ajax._f = 'inicia_balancete';
        obj_ajax._p = {};
        $http.post(S_vars.url_ajax + 'ajax.php', obj_ajax).success(function(data, status) {
            // console.log(data);
            $balancete.grid = $sce.trustAsHtml(data);
        });
    }

    $balancete.exportExcel = function() {
        waitingDialog.show('Preparando dados para realizar exportação...');
        var obj_ajax = {};
        obj_ajax._f = 'exporta_excel_balancete';
        obj_ajax._p = {};
        $http.post(S_vars.url_ajax + 'ajax.php', obj_ajax).success(function(data, status) {
            waitingDialog.hide();
        });
    }

    $balancete.sendData = function() {
        var nome_folha_trabalho = prompt('Insira o nome da Folha de Trabalho');

        if (nome_folha_trabalho === null || nome_folha_trabalho == '') return false;

        var rows = getSelRows();

        var obj_ajax = {};
        obj_ajax._f = 'envia_folha_trabalho';
        obj_ajax._p = {
            rows: rows,
            nome_folha_trabalho: nome_folha_trabalho
        };
        $http.post(S_vars.url_ajax + 'ajax.php', obj_ajax).success(function(data, status) {
            alert('Enviado para folha de trabalho com sucesso');
        });
    }

    if (S_vars.soft == true) {
        $balancete.iniciar_master_detail();
    } else {
        $balancete.datatables = function($filter, $compile, $scope, $resource, DTOptionsBuilder, DTColumnBuilder) {
            var vm = this;
            vm.selectAll = false;
            $balancete.selected = {};
            $balancete.toggleAll = toggleAll;
            $balancete.toggleOne = toggleOne;

            var titleHtml = '<input type="checkbox" ng-model="balanceteCtrl.selectAll" ng-click="balanceteCtrl.toggleAll(balanceteCtrl.selectAll, balanceteCtrl.selected)">';

            vm.dtOptions = DTOptionsBuilder.newOptions()
                .withOption('ajax', {
                    url: S_vars.url_ajax + "ajax/get_balancete.php",
                    type: 'POST'
                })
                .withLanguage({
                    "sEmptyTable": "Nenhum registro encontrado",
                    "sInfo": "Mostrando de _START_ até _END_ de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando 0 até 0 de 0 registros",
                    "sInfoFiltered": "(Filtrados de _MAX_ registros)",
                    "sInfoPostFix": "",
                    "sInfoThousands": ".",
                    "sLengthMenu": "_MENU_ resultados por página",
                    "sLoadingRecords": "Carregando...",
                    "sProcessing": "Processando...",
                    "sZeroRecords": "Nenhum registro encontrado",
                    "sSearch": "Pesquisar (Conta)",
                    "oPaginate": {
                        "sNext": "Próximo",
                        "sPrevious": "Anterior",
                        "sFirst": "Primeiro",
                        "sLast": "Último"
                    },
                    "oAria": {
                        "sSortAscending": ": Ordenar colunas de forma ascendente",
                        "sSortDescending": ": Ordenar colunas de forma descendente"
                    }
                })
                .withDataProp('data')
                .withOption('processing', true)
                .withOption('serverSide', true)
                .withOption('createdRow', function(row, data, dataIndex) {
                    // Recompiling so we can bind Angular directive to the DT
                    $compile(angular.element(row).contents())($scope);
                })
                .withOption('headerCallback', function(header) {
                    if (!vm.headerCompiled) {
                        // Use this headerCompiled field to only compile header once
                        vm.headerCompiled = true;
                        $compile(angular.element(header).contents())($scope);
                    }
                })
                .withPaginationType('full_numbers');

            vm.dtColumns = [
                DTColumnBuilder.newColumn(null).withTitle(titleHtml).notSortable()
                .renderWith(function(data, type, full, meta) {
                    // $balancete.selected[full.id] = false;
                    // console.log(full.id);
                    return '<input type="checkbox" ng-model="balanceteCtrl.selected[\'' + data._id.$id + '\']" ng-click="balanceteCtrl.toggleOne(balanceteCtrl.selected)">';
                }),
                DTColumnBuilder.newColumn('_id').notVisible(),
                DTColumnBuilder.newColumn('id_diario').notVisible(),
                DTColumnBuilder.newColumn('account').withTitle('Conta').withOption('width', '20%'),
                DTColumnBuilder.newColumn('debt').withTitle('Débito').withOption('width', '20%').renderWith(function(data, type, full) {
                    return $filter('currency')(data, 'R$', 2);
                }),
                DTColumnBuilder.newColumn('credit').withTitle('Crédito').withOption('width', '20%').renderWith(function(data, type, full) {
                    return $filter('currency')(data, 'R$', 2);
                }),
                DTColumnBuilder.newColumn('outstanding_balance').withTitle('Saldo').withOption('width', '20%').renderWith(function(data, type, full) {
                    return $filter('currency')(data, 'R$', 2);
                })
            ];

            function toggleAll(selectAll, selectedItems) {
                for (var id in selectedItems) {
                    if (selectedItems.hasOwnProperty(id)) {
                        selectedItems[id] = selectAll;
                    }
                }
            }

            function toggleOne(selectedItems) {
                for (var id in selectedItems) {
                    if (selectedItems.hasOwnProperty(id)) {
                        if (!selectedItems[id]) {
                            vm.selectAll = false;
                            return;
                        }
                    }
                }
                vm.selectAll = true;
            }
        }
    }

});