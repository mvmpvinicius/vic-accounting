var app = angular.module('app', ['ngRoute', 'ngResource', 'datatables', 'textAngular', 'colorbox', 'ui-rangeSlider']);

app.config(['$routeProvider',
    function($routeProvider) {
        $routeProvider.
        when('/diario', {
            templateUrl: 'templates/diario/diario.html',
            controller: 'diarioController',
            controllerAs: 'diarioCtrl'
        }).
        when('/sped', {
            templateUrl: 'templates/sped/sped.html',
            controller: 'spedController',
            controllerAs: 'spedCtrl'
        }).
        when('/balancete', {
            templateUrl: 'templates/balancete/balancete.html',
            controller: 'balanceteController',
            controllerAs: 'balanceteCtrl'
        }).
        when('/folha_trabalho', {
            templateUrl: 'templates/folha_trabalho/folha_trabalho.html',
            controller: 'folhaTrabalhoController',
            controllerAs: 'folhaTrabalhoCtrl'
        }).
        otherwise({
            redirectTo: '/diario'
        });
    }
]);

app.controller('appController', function($scope, S_vars){
    var $app = this;
    $app.titulo = "App";

    $app.vars = S_vars;
    $app.selected_menu = "contabilidade";
    $app.select_menu = function(menu) {
        $app.selected_menu = menu;
    };
});
