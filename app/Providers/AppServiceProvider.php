<?php

namespace App\Providers;

use App\Jobs\RegenerateSitemap;
use App\Models\Core\Content\FaqItem;
use App\Models\Core\Content\Page;
use App\Models\Core\Product\Category;
use App\Models\Core\Product\Product;
use App\Models\Core\Service\Service;
use App\Services\Core\Cart\CartService;
use App\Services\Core\Product\CatalogMenu;
use App\Settings\GeneralSettings;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->addActiveFields();
        $this->configureCommands();
        $this->configureModels();
        $this->configurePasswordValidation();
        $this->configureDates();
        $this->configureViewComposers();
        $this->configureSitemapRegeneration();

        // $this->configureLoadMigrationsFrom();
    }

    private function addActiveFields(): void
    {
        Blueprint::macro('activeFields', function () {
            $this->boolean('is_active')->default(true);
            $this->boolean('is_valid')->default(true);
        });
    }

    /**
     * Configure the application's commands.
     */
    private function configureCommands(): void
    {
        DB::prohibitDestructiveCommands(
            $this->app->isProduction()
        );
    }

    /**
     * Configure the dates.
     */
    private function configureDates(): void
    {
        Date::use(CarbonImmutable::class);
    }

    /**
     * The sitemap is rebuilt (queued, de-duplicated) whenever public content is created, removed or
     * published/unpublished/renamed.
     */
    private function configureSitemapRegeneration(): void
    {
        $dispatch = fn () => RegenerateSitemap::dispatch();

        foreach ([Product::class, Category::class, Service::class, Page::class, FaqItem::class] as $model) {
            $model::created($dispatch);
            $model::updated(fn (Model $updated) => $updated->wasChanged(['is_active', 'is_published', 'slug']) ? $dispatch() : null);
            $model::deleted($dispatch);
        }
    }

    /** The public site's header and footer read the general settings. */
    private function configureViewComposers(): void
    {
        View::composer('components.layouts.app', function ($view): void {
            $view->with('site', app(GeneralSettings::class));
            $view->with('menu', app(CatalogMenu::class)->tree());
            $view->with('cartCount', app(CartService::class)->count());
        });
    }

    /**
     * Configure the models.
     */
    private function configureModels(): void
    {
        Model::shouldBeStrict(! $this->app?->isProduction());

        Model::unguard();
    }

    /**
     * Configure the password validation rules.
     */
    private function configurePasswordValidation(): void
    {
        Password::defaults(fn () => $this->app?->isProduction() ? Password::min(8)->uncompromised() : null);
    }

    private function configureLoadMigrationsFrom(): void
    {
        $this->loadMigrationsFrom([
            database_path().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR.'folder_name',
        ]);

        /* $migrationsPath = database_path('migrations');
        $directories = glob($migrationsPath . '/*', GLOB_ONLYDIR);
        $paths = array_merge([$migrationsPath], $directories);

        $this->loadMigrationsFrom($paths);*/
    }
}
