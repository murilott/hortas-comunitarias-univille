<?php
 
use App\Controllers\CanteiristaController;
use Slim\Routing\RouteCollectorProxy;

return function(RouteCollectorProxy $app){
    $app->group('/Canteiristas', function(RouteCollectorProxy $group){
        $group->get('', CanteiristaController::class.':list');
        // Rotas estáticas DEVEM vir antes de /{uuid} para evitar conflito de roteamento
        $group->get('/filtro', CanteiristaController::class.':filter');
        $group->get('/estatisticas', CanteiristaController::class.':statistics');
        $group->get('/{uuid}', CanteiristaController::class.':get');
        $group->post('', CanteiristaController::class.':create');
        $group->put('/{uuid}', CanteiristaController::class.':update');
        $group->patch('/{uuid}/ativar', CanteiristaController::class.':activate');
        $group->patch('/{uuid}/desativar', CanteiristaController::class.':deactivate');
        $group->delete('/{uuid}', CanteiristaController::class.':delete');
    });
};

