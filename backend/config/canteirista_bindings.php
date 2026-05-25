<?php
use DI\ContainerBuilder;
use App\Models\CanteiristaModel;
use App\Repositories\CanteiristaRepository;
use App\Services\CanteiristaService;
use App\Controllers\CanteiristaController;

return function(ContainerBuilder $container){
    $container->addDefinitions([
        CanteiristaModel::class => DI\autowire(CanteiristaModel::class),
        CanteiristaRepository::class => DI\autowire(CanteiristaRepository::class),
        CanteiristaService::class => DI\autowire(CanteiristaService::class),
        CanteiristaController::class => DI\autowire(CanteiristaController::class),
    ]);
};

