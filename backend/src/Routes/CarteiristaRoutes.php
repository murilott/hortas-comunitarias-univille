<?php
 
use App\Controllers\CarteiristaController;
use Slim\Routing\RouteCollectorProxy;

return function(RouteCollectorProxy $app){
    $app->group('/carteiristas', function(RouteCollectorProxy $group){
        $group->get('', CarteiristaController::class.':list');
        // Rotas estáticas DEVEM vir antes de /{uuid} para evitar conflito de roteamento
        $group->get('/filtro', CarteiristaController::class.':filter');
        $group->get('/estatisticas', CarteiristaController::class.':statistics');
        $group->get('/{uuid}', CarteiristaController::class.':get');
        $group->post('', CarteiristaController::class.':create');
        $group->put('/{uuid}', CarteiristaController::class.':update');
        $group->patch('/{uuid}/ativar', CarteiristaController::class.':activate');
        $group->patch('/{uuid}/desativar', CarteiristaController::class.':deactivate');
        $group->delete('/{uuid}', CarteiristaController::class.':delete');
    });
};
