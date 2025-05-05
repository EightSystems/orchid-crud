<?php

namespace Orchid\Crud\Layouts;

use Orchid\Crud\CrudScreen;
use Orchid\Screen\Layouts\Modal;
use Orchid\Screen\Repository;
use Orchid\Support\Facades\Dashboard;

class ResourceModal extends Modal
{
    public function build(Repository $repository)
    {
        $screen = Dashboard::getCurrentScreen();

        if (! $screen) {
            return null;
        }

        if ($screen instanceof CrudScreen) {
            $defferedRoute = route(
                'platform.async.crud',
                array_filter(
                    [
                        'resource' => request()->route('resource'),
                        'id'       => ($resourceId = request()->route('id')) ? $resourceId : null,
                    ]
                )
            );
        } else {
            $defferedRoute = route('platform.async');
        }

        $this->variables = array_merge($this->variables, [
            'deferredRoute'  => $defferedRoute,
            'deferrerParams' => $this->getDeferrerDataLoadingParameters(),
        ]);

        return $this->buildAsDeep($repository);
    }
}
