<?php

namespace Orchid\Crud\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Orchid\Crud\CrudScreen;
use Orchid\Platform\Http\Controllers\AsyncController as OrchidAsyncController;
use Orchid\Screen\Screen;

class AsyncController extends OrchidAsyncController
{
    protected function bootScreen(string $screenName, Request $request): Screen
    {
        /** @var \Orchid\Crud\CrudScreen $screen */
        $screen = app($screenName);

        if (! $screen instanceof CrudScreen) {
            abort(500, 'Screen is not a CrudScreen');
        }

        // Boot middleware so it can fill the request and resource
        foreach ($screen->getMiddleware() as $middlewareConfig) {
            $screenMiddleware = $middlewareConfig['middleware'];

            $screenMiddleware($request, function ($newRequest) use (&$request) {
                $request = $newRequest;
            });
        }

        return $screen;
    }

    public function crudLoad(Request $request, string $resource, ?string $id = null)
    {
        $request->validate([
            '_call'     => 'required|string',
            '_screen'   => 'required|string',
            '_template' => 'required|string',
        ]);

        // Fill the request resource and ID
        $request->route()->setParameter('resource', $resource);
        $request->route()->setParameter('id', $id);

        $screen = Crypt::decryptString(
            $request->input('_screen')
        );

        $screen = $this->bootScreen($screen, $request);

        return $screen->asyncBuild(
            $request->input('_call'),
            $request->input('_template')
        );
    }

    public function crudListener(Request $request, string $screen, string $layout, string $resource, ?string $id = null)
    {
        $screen = Crypt::decryptString($screen);
        $layout = Crypt::decryptString($layout);
        $resource = Crypt::decryptString($resource);
        $id = $id ? Crypt::decryptString($id) : null;

        // Fill the request resource and ID
        $request->route()->setParameter('resource', $resource);
        $request->route()->setParameter('id', $id);

        // This allows us to use the same listener view without needing to copy it to this package
        $request->route()->action['as'] = 'platform.async.listener';

        $screen = $this->bootScreen($screen, $request);

        /** @var \Orchid\Screen\Layouts\Listener $layout */
        $layout = app($layout);

        return $screen->asyncParticalLayout($layout, $request);
    }
}
