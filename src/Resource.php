<?php

namespace Orchid\Crud;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Orchid\Screen\Field;
use Orchid\Screen\Layouts\Selection;
use Orchid\Screen\Sight;
use Orchid\Screen\TD;

/**
 * Resource is the basic representation of your model and custom logic for chosen screens.
 *
 * @method \Orchid\Screen\Layout[]     customCreateLayout(\Orchid\Crud\Screens\CreateScreen $screen)                                               Overrides the default crud layout at the create screen. Use it when you only need to replace one part of the crud screens.
 * @method \Orchid\Screen\Layout[]     customEditLayout(\Orchid\Crud\Screens\EditScreen $screen)                                                   Overrides the default crud layout at the edit screen. Use it when you only need to replace one part of the crud screens.
 * @method \Orchid\Screen\Layout[]     customListLayout(\Orchid\Crud\Screens\ListScreen $screen)                                                   Overrides the default crud layout at the list screen. Use it when you only need to replace one part of the crud screens.
 * @method \Orchid\Screen\Layout[]     customViewLayout(\Orchid\Crud\Screens\ViewScreen $screen)                                                   Overrides the default crud layout at the view screen. Use it when you only need to replace one part of the crud screens.
 * @method \Orchid\Screen\Action[]     customCreateCommandBar(\Orchid\Crud\Screens\CreateScreen $screen)                                           Overrides the default command bar at the create screen
 * @method \Orchid\Screen\Action[]     customEditCommandBar(\Orchid\Crud\Screens\EditScreen $screen)                                               Overrides the default command bar at the edit screen
 * @method \Orchid\Screen\Action[]     customListCommandBar(\Orchid\Crud\Screens\ListScreen $screen)                                               Overrides the default command bar at the list screen
 * @method \Orchid\Screen\Action[]     customViewCommandBar(\Orchid\Crud\Screens\ViewScreen $screen)                                               Overrides the default command bar at the view screen
 * @method array                       customViewQuery(\Orchid\Crud\Requests\ViewRequest $request, \Illuminate\Database\Eloquent\Model $model)     Use it to add extra data to the view screen. Acts like orchid Screen query method, merging the returned array to the CRUD query.
 * @method array                       customListQuery(\Orchid\Crud\Requests\IndexRequest $request)                                                Use it to add extra data to the list screen. Acts like orchid Screen query method, merging the returned array to the CRUD query.
 * @method array                       customCreateQuery(\Orchid\Crud\Requests\CreateRequest $request, \Illuminate\Database\Eloquent\Model $model) Use it to add extra data to the create screen. Acts like orchid Screen query method, merging the returned array to the CRUD query.
 * @method array                       customEditQuery(\Orchid\Crud\Requests\UpdateRequest $request, \Illuminate\Database\Eloquent\Model $model)   Use it to add extra data to the edit screen. Acts like orchid Screen query method, merging the returned array to the CRUD query.
 * @method string                      formRowTitle()                                                                                              Customize the form title at the Edit and Create screens
 * @method \Orchid\Screen\Layout[]     preFormLayout()                                                                                             Allows you to prepend extra form elements at the Edit and Create screens
 * @method \Orchid\Screen\Layout[]     postFormLayout()                                                                                            Allows you to append extra form elements at the Edit and Create screens
 * @method void                        onSave(\Orchid\Crud\ResourceRequest $request, \Illuminate\Database\Eloquent\Model $model)
 * @method void                        onDelete(\Illuminate\Database\Eloquent\Model $model)
 * @method void                        onForceDelete(\Illuminate\Database\Eloquent\Model $model)
 * @method void                        onRestore(\Illuminate\Database\Eloquent\Model $model)
 * @method \Orchid\Screen\Fields\Group tableActions(\Illuminate\Database\Eloquent\Model $model)                                                    Overrides the table actions for this resource.
 */
abstract class Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = '';

    /**
     * If true it will redirect to the Crud View Resource page after saving the resource (create/update/restoring)
     */
    public static bool $redirectToViewAfterSaving = false;

    /**
     * Get the displayable label of the resource.
     *
     * @return string
     */
    public static function label(): string
    {
        return Str::of(static::nameWithoutResource())
            ->snake(' ')
            ->title()
            ->plural()
            ->toString();
    }

    /**
     * Get the resource should be displayed in the navigation
     *
     * @return bool
     */
    public static function displayInNavigation(): bool
    {
        return true;
    }

    /**
     * Get the number of models to return per page
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     *
     * @return int
     */
    public static function perPage(): int
    {
        return resolve(static::$model)->getPerPage();
    }

    /**
     * Get the displayable icon of the resource.
     *
     * @return string
     */
    public static function icon(): string
    {
        return 'folder';
    }

    /**
     * Get the displayable sort of the resource.
     *
     * @return string
     */
    public static function sort(): string
    {
        return 2000;
    }

    /**
     * Get the columns displayed by the resource.
     *
     * @return TD[]
     */
    abstract public function columns(): array;

    /**
     * Get the fields displayed by the resource.
     *
     * @return Field[]
     */
    abstract public function fields(): array;

    /**
     * Get the sights displayed by the resource.
     *
     * @return Sight[]
     */
    abstract public function legend(): array;

    /**
     * Get the URI key for the resource.
     *
     * @return string
     */
    public static function uriKey(): string
    {
        return Str::of(static::class)->classBasename()->kebab()->plural();
    }

    /**
     * Get the permission key for the resource.
     *
     * @return string|null
     */
    public static function permission(): ?string
    {
        return null;
    }

    /**
     * The underlying model resource instance.
     *
     * @return Model
     */
    public function getModel(): Model
    {
        return app(static::$model);
    }

    /**
     * Get the displayable singular label of the resource.
     *
     * @return string
     */
    public static function singularLabel(): string
    {
        return Str::of(static::nameWithoutResource())->snake(' ')->title()->singular();
    }

    /**
     * @return string
     */
    public static function nameWithoutResource(): string
    {
        return Str::of(static::class)
            ->classBasename()
            ->replace('Resource', '')
            ->whenEmpty(function () {
                return 'Resource';
            });
    }

    /**
     * Get the text for the create resource button.
     *
     * @return string|null
     */
    public static function createButtonLabel(): string
    {
        return __('Create :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the create resource toast.
     *
     * @return string
     */
    public static function createToastMessage(): string
    {
        return __('The :resource was created!', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the update resource button.
     *
     * @return string
     */
    public static function updateButtonLabel(): string
    {
        return __('Update :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the update resource toast.
     *
     * @return string
     */
    public static function updateToastMessage(): string
    {
        return __('The :resource was updated!', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the delete resource button.
     *
     * @return string
     */
    public static function deleteButtonLabel(): string
    {
        return __('Delete :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the delete resource toast.
     *
     * @return string
     */
    public static function deleteToastMessage(): string
    {
        return __('The :resource was deleted!', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the save resource button.
     *
     * @return string
     */
    public static function saveButtonLabel(): string
    {
        return __('Save :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the restore resource button.
     *
     * @return string
     */
    public static function restoreButtonLabel(): string
    {
        return __('Restore :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the restore resource toast.
     *
     * @return string
     */
    public static function restoreToastMessage(): string
    {
        return __('The :resource was restored!', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for Traffic Cop error.
     *
     * @return string
     */
    public static function trafficCopMessage(): string
    {
        return __('Since the :resource was edited, its values have changed. Refresh the page to see them or click ":button" again to replace them.', [
            'resource' => static::singularLabel(),
            'button'   => self::updateButtonLabel(),
        ]);
    }

    /**
     * Get the text for the list breadcrumbs.
     *
     * @return string
     */
    public static function listBreadcrumbsMessage(): string
    {
        return static::label();
    }

    /**
     * Get the text for the create breadcrumbs.
     *
     * @return string
     */
    public static function createBreadcrumbsMessage(): string
    {
        return __('New :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the text for the edit breadcrumbs.
     *
     * @return string
     */
    public static function editBreadcrumbsMessage(): string
    {
        return __('Edit :resource', ['resource' => static::singularLabel()]);
    }

    /**
     * Get the descriptions for the screen.
     *
     * @return null|string
     */
    public static function description(): ?string
    {
        return null;
    }

    /**
     * Get the text when there are no resources for the action.
     *
     * @return string
     */
    public static function emptyResourceForAction(): string
    {
        return __('No ":resources" over which you can perform an action', ['resources' => static::label()]);
    }

    /**
     * Get the validation rules that apply to save/update.
     *
     * @param Model $model
     *
     * @return array
     */
    public function rules(Model $model): array
    {
        return [];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @return array
     */
    public function filters(): array
    {
        return [];
    }

    /**
     * Overrides the filters `Selection` template, by default it will be a DROP DOWN template.
     *
     * @return string
     */
    public function filtersContainerTemplate(): string
    {
        return Selection::TEMPLATE_DROP_DOWN;
    }

    /**
     * Get relationships that should be eager loaded when performing an index query.
     *
     * @return array
     */
    public function with(): array
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @return array
     */
    public function actions(): array
    {
        return [];
    }

    /**
     * Allows you to treat and/or return a dynamic action if no action matches.
     * If you return a null value it will just proceed with the default behaviour of HTTP 405
     *
     * @param Request $request
     *
     * @return null|Action
     */
    public function noActionMatched(Request $request): ?Action
    {
        return null;
    }

    /**
     * Indicates whether should check for modifications between viewing and updating a resource.
     *
     * @return bool
     */
    public static function trafficCop(): bool
    {
        return false;
    }

    /**
     * Action to query one model
     *
     * @param Model $model
     *
     * @return Model $model
     */
    public function modelQuery(ResourceRequest $request, Model $model): Builder
    {
        return $model->query();
    }

    /**
     * Action to query models list
     *
     * @param \Orchid\Crud\ResourceRequest $request
     * @param Model                        $model
     *
     * @return \Illuminate\Database\Eloquent\Builder $model
     */
    public function paginationQuery(ResourceRequest $request, Model $model): Builder
    {
        return $model->query();
    }

    /**
     * Determine if this resource uses soft deletes.
     *
     * @return bool
     */
    public static function softDeletes(): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive(static::$model), true);
    }

    /**
     * Action to create and update the model
     *
     * @param ResourceRequest $request
     * @param Model           $model
     */
    public function save(ResourceRequest $request, Model $model): void
    {
        if (method_exists(static::class, 'onSave')) {
            static::onSave($request, $model);

            return;
        }

        $model->forceFill($request->all())->save();
    }

    /**
     * Action to delete a model
     *
     * @param Model $model
     *
     * @throws Exception
     */
    public function delete(Model $model): void
    {
        if (method_exists(static::class, 'onDelete')) {
            static::onDelete($model);

            return;
        }

        $model->delete();
    }

    /**
     * Action to restore a model
     *
     * @param Model $model
     */
    public function restore(Model $model): void
    {
        if (method_exists(static::class, 'onRestore')) {
            static::onRestore($model);

            return;
        }

        $model->restore();
    }

    /**
     * Action to Force delete a model
     *
     * @param Model $model
     *
     * @throws Exception
     */
    public function forceDelete(Model $model): void
    {
        if (method_exists(self::class, 'onForceDelete')) {
            static::onForceDelete($model);

            return;
        }

        $model->forceDelete();
    }

    /**
     * Determines whether the actions block should be shown in the table.
     *
     * @return bool
     */
    public function canShowTableActions(): bool
    {
        return true;
    }

    /**
     * Determines whether the actions at the header should be shown inside a DropDown or all in the same level.
     *
     * @return bool
     */
    public function actionsInsideDropdown(): bool
    {
        return true;
    }
}
