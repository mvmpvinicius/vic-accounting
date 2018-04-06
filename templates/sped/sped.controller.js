var app = angular.module('app');

app.controller('spedController', function($scope, $http, S_vars, S_http_validate, S_fx) {
	$scope.sped = {};
	$scope.sped.titulo = "OLA";

	$scope.sped.import = function(){
		// console.log(S_http_validate);
		var obj_ajax = {};
		obj_ajax._f = "importar_sped";
		$http.post(S_vars.url_ajax + "ajax.php", obj_ajax).success(function(data, status) {
			var validation = S_http_validate.validate_success(data.error, status);
			if(validation == true){
				console.log(data);
            }else{
            	alert(validation);
            }
        });
	}
});