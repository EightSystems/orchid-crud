<?php

namespace Orchid\Crud;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Orchid\Crud\Commands\ActionCommand;
use Orchid\Crud\Commands\ResourceCommand;
use Orchid\Crud\Screens\CreateScreen;
use Orchid\Crud\Screens\EditScreen;
use Orchid\Crud\Screens\ListScreen;
use Orchid\Crud\Screens\ViewScreen;
use Orchid\Platform\Dashboard as PlatformDashboard;
use Orchid\Platform\Providers\FoundationServiceProvider;
use Orchid\Support\Facades\Dashboard;

class CrudServiceProvider extends ServiceProvider
{
    /**
     * Path to crud dir
     *
     * @var string
     */
    protected $path;

    /**
     * The available command shortname.
     *
     * @var array
     */
    protected $commands = [
        ResourceCommand::class,
        ActionCommand::class,
    ];

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(ResourceFinder $finder, Arbitrator $arbitrator): void
    {
        $this->setupDashboardFacadeHelpers();

        $resources = $finder
            ->setNamespace(app()->getNamespace() . 'Orchid\\Resources')
            ->find(app_path('Orchid/Resources'));

        $arbitrator
            ->resources($resources)
            ->boot();

        Route::domain((string)config('platform.domain'))
            ->prefix(Dashboard::prefix('/'))
            ->as('platform.')
            ->middleware(config('platform.middleware.private'))
            ->group(__DIR__ . '/../routes/crud.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->path = dirname(__DIR__, 1);

        $this->commands($this->commands);
        $this->loadJsonTranslationsFrom($this->path.'/resources/lang/');
        $this->app->register(FoundationServiceProvider::class, true);

        $this->app->singleton(Arbitrator::class, static function () {
            return new Arbitrator();
        });
    }

    protected function setupDashboardFacadeHelpers()
    {
        PlatformDashboard::macro('getCrudScreenOperation', function (): string {
            $currentScreen = Dashboard::getCurrentScreen();

            if ($currentScreen) {
                return match(true) {
                    $currentScreen instanceof CreateScreen => 'create',
                    $currentScreen instanceof EditScreen   => 'edit',
                    $currentScreen instanceof ViewScreen   => 'view',
                    $currentScreen instanceof ListScreen   => 'list',
                    default                                => ''
                };
            } else {
                return '';
            }
        });

        /**
         * Checks if this is a Crud route.
         * If you pass the $resourceName parameter it will also check if the current resource matches.
         */
        PlatformDashboard::macro('isCrudScreen', function (string $crudMethod = '*', ?string $resourceName = null): bool {
            return request()->routeIs('platform.resource.'.$crudMethod) && (
                $resourceName ? request()->route('resource') === $resourceName : true
            );
        });

        PlatformDashboard::macro('isCrudListScreen', function (?string $resourceName = null): bool {
            return PlatformDashboard::isCrudScreen('list', $resourceName);
        });

        PlatformDashboard::macro('isCrudCreateScreen', function (?string $resourceName = null): bool {
            return PlatformDashboard::isCrudScreen('create', $resourceName);
        });

        PlatformDashboard::macro('isCrudEditScreen', function (?string $resourceName = null): bool {
            return PlatformDashboard::isCrudScreen('edit', $resourceName);
        });

        PlatformDashboard::macro('isCrudViewScreen', function (?string $resourceName = null): bool {
            return PlatformDashboard::isCrudScreen('view', $resourceName);
        });

        /**
         * Returns the model being used in the crud view, edit, and create screens.
         * For the list screen, there's no single model in operation, so it will return null.
         */
        PlatformDashboard::macro('getCurrentCrudModel', function (): ?Model {
            if (PlatformDashboard::isCrudScreen() && ! PlatformDashboard::isCrudListScreen()) {
                /**
                 * @var \Orchid\Crud\Screens\ViewScreen|\Orchid\Crud\Screens\EditScreen|\Orchid\Crud\Screens\CreateScreen
                 */
                $currentScreen = PlatformDashboard::getCurrentScreen();

                return $currentScreen->model();
            } else {
                return null;
            }
        });
    }
}
