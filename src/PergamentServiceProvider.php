<?php

declare(strict_types=1);

namespace Pergament;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Pergament\Console\Commands\GenerateStaticCommand;
use Pergament\Console\Commands\MakeBlogPostCommand;
use Pergament\Console\Commands\MakeDocPageCommand;

final class PergamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/pergament.php', 'pergament');
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pergament');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'pergament');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateStaticCommand::class,
                MakeDocPageCommand::class,
                MakeBlogPostCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/Config/pergament.php' => config_path('pergament.php'),
            ], 'pergament-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pergament'),
            ], 'pergament-views');
        }
    }
}
