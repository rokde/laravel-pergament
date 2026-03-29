<?php

declare(strict_types=1);

namespace Pergament;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Pergament\Console\Commands\AnalyticsCommand;
use Pergament\Console\Commands\GenerateStaticCommand;
use Pergament\Console\Commands\MakeBlogPostCommand;
use Pergament\Console\Commands\MakeDocCommand;
use Pergament\Console\Commands\MakePageCommand;

final class PergamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/Config/pergament.php', 'pergament');
    }

    private function shareAssetVersions(string $distPath): void
    {
        $hash = fn (string $file): string => substr(md5_file(
            is_file(public_path('vendor/pergament/'.$file))
                ? public_path('vendor/pergament/'.$file)
                : $distPath.'/'.$file,
        ), 0, 8);

        View::share('pergamentCssVersion', $hash('pergament.css'));
        View::share('pergamentJsVersion', $hash('pergament.js'));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'pergament');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'pergament');

        $this->shareAssetVersions(__DIR__.'/../dist');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyticsCommand::class,
                GenerateStaticCommand::class,
                MakeDocCommand::class,
                MakeBlogPostCommand::class,
                MakePageCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/Config/pergament.php' => config_path('pergament.php'),
            ], 'pergament-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/pergament'),
            ], 'pergament-views');

            $this->publishes([
                __DIR__.'/../resources/views/components/header.blade.php' => resource_path('views/vendor/pergament/components/header.blade.php'),
            ], 'pergament-header');

            $this->publishes([
                __DIR__.'/../resources/views/components/footer.blade.php' => resource_path('views/vendor/pergament/components/footer.blade.php'),
            ], 'pergament-footer');

            $this->publishes([
                __DIR__.'/../dist' => public_path('vendor/pergament'),
                __DIR__.'/../resources/fonts' => public_path('vendor/pergament/fonts'),
            ], 'pergament-assets');
        }
    }
}
