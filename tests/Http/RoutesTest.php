<?php

namespace Davidhsianturi\Compass\Tests\Http;

use Davidhsianturi\Compass\Compass;
use Davidhsianturi\Compass\Tests\TestCase;
use Davidhsianturi\Compass\Storage\RouteModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Davidhsianturi\Compass\Storage\DatabaseRoutesRepository;

class RoutesTest extends TestCase
{
    use RefreshDatabase;

    protected $repository;

    public function setUp(): void
    {
        parent::setUp();

        $this->loadFactoriesUsing($this->app, __DIR__.'/../../src/Storage/factories');

        $this->repository = new DatabaseRoutesRepository('testbench');
    }

    public function test_index_routes()
    {
        $this->registerAppRoutes();

        $routes = $this->repository->get();
        $totalList = $routes->count();
        $totalGroup = Compass::groupingRoutes($routes)->count();

        $this->getJson(route('compass.routes'))
            ->assertSuccessful()
            ->assertJsonStructure([
                'routes' => [
                    'list' => [],
                    'group' => [],
                ],
            ])
            ->assertJsonCount($totalList, 'routes.list')
            ->assertJsonCount($totalGroup, 'routes.group');
    }

    public function test_show_route_by_id()
    {
        $this->registerAppRoutes();

        $route = $this->repository->get()->random()->jsonSerialize();

        $this->getJson(route('compass.routes.show', $route['id']))
            ->assertSuccessful()
            ->assertJsonStructure(['route' => []])
            ->assertJson([
                'route' => [
                    'id' => $route['id'],
                    'storageId' => $route['storageId'],
                    'title' => $route['title'],
                ],
            ]);
    }

    public function test_store_the_app_route_to_storage()
    {
        $this->registerAppRoutes();

        $appRoute = $this->repository->get()->random()->jsonSerialize();

        $response = $this->postJson(route('compass.routes.store'), $appRoute);
        $response
            ->assertSuccessful()
            ->assertJsonStructure(['route' => []])
            ->assertJson([
                'route' => [
                    'id' => $appRoute['id'],
                    'title' => $appRoute['title'],
                    'network' => $appRoute['network'],
                ],
            ]);

        $this->assertNotNull($response->json(['route'])['storageId']);
    }

    public function test_update_existing_route_from_storage()
    {
        $route = $this->repository->find($this->routeFactory()->route_id)->jsonSerialize();

        $updateAttribute = array_merge($route, ['title' => 'List All Invoices']);

        $response = $this->postJson(route('compass.routes.store'), $updateAttribute);
        $response
            ->assertSuccessful()
            ->assertJsonStructure(['route' => []])
            ->assertJson([
                'route' => [
                    'id' => $updateAttribute['id'],
                    'storageId' => $updateAttribute['storageId'],
                    'title' => 'List All Invoices',
                    'network' => $updateAttribute['network'],
                ],
            ]);
    }

    protected function routeFactory()
    {
        $this->registerAppRoutes();

        $route = $this->repository->get()->random()->jsonSerialize();

        return factory(RouteModel::class)->create([
            'route_id' => md5($route['info']['uri'].':'.$route['info']['method']),
            'title' => $route['title'],
            'network' => [],
        ]);
    }
}
