<?php

declare(strict_types=1);

namespace Pergament\Console;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Filesystem\Filesystem as FilesystemContract;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Contracts\View\Factory as ViewFactoryContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\View\ViewServiceProvider;
use Pergament\Console\Commands\GenerateStaticCommand;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

final class StandaloneApplication extends Container
{
    private string $basePath;

    /** @var array<int, ServiceProvider> */
    private array $serviceProviders = [];

    /** @var array<int, callable> */
    private array $terminatingCallbacks = [];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);

        Container::setInstance($this);
        Facade::setFacadeApplication($this);

        if (! class_exists('Str', false)) {
            class_alias(Str::class, 'Str');
        }

        $this->instance('app', $this);
        $this->instance(ApplicationContract::class, $this);
        $this->instance(self::class, $this);
        $this->instance(Container::class, $this);
        $this->instance('path.base', $this->basePath);
        $this->instance('path.config', $this->configPath());
        $this->instance('path.public', $this->publicPath());
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.storage', $this->storagePath());

        $this->singleton(ExceptionHandler::class, StandaloneExceptionHandler::class);
        $this->singleton(ConsoleKernelContract::class, StandaloneConsoleKernel::class);
    }

    public static function runFromBasePath(string $basePath): int
    {
        $app = new self($basePath);

        return $app->runConsole();
    }

    public function runConsole(): int
    {
        $this->bootstrapStandaloneRuntime();

        $input = new ArgvInput;
        $output = new ConsoleOutput;

        if ($input->getFirstArgument() === 'generate-static') {
            $_SERVER['argv'][1] = 'pergament:generate-static';
            $input = new ArgvInput;
        }

        if ($input->getFirstArgument() !== 'pergament:generate-static') {
            $output->getErrorOutput()->writeln('Unknown command. Usage: pergament generate-static <output-dir> [--content-path=...] [--prefix=...] [--base-url=...] [--clean]');

            return 1;
        }

        $console = new ConsoleApplication($this, $this->make('events'), '1.0.0');
        $console->setAutoExit(false);
        $console->add($this->make(GenerateStaticCommand::class));

        return $console->run($input, $output);
    }

    public function bootstrapStandaloneRuntime(): void
    {
        $this->registerCoreBindings();
        $this->register(new RoutingServiceProvider($this));
        $this->register(new ViewServiceProvider($this));
        $this->registerFrameworkAliases();
        $this->registerPergamentViews();
        $this->registerStandaloneRoutes();
    }

    public function register(ServiceProvider|string $provider, bool $force = false): ServiceProvider
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        $provider->register();
        $this->serviceProviders[] = $provider;

        return $provider;
    }

    public function basePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath, $path);
    }

    public function path(string $path = ''): string
    {
        return $this->joinPath($this->basePath('app'), $path);
    }

    public function configPath(string $path = ''): string
    {
        return $this->joinPath($this->basePath('config'), $path);
    }

    public function publicPath(string $path = ''): string
    {
        return $this->joinPath($this->basePath('public'), $path);
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->joinPath($this->basePath('resources'), $path);
    }

    public function storagePath(string $path = ''): string
    {
        return $this->joinPath(sys_get_temp_dir().'/pergament-standalone', $path);
    }

    public function environment(...$environments): string|bool
    {
        if ($environments === []) {
            return 'production';
        }

        return in_array('production', $environments, true);
    }

    public function runningInConsole(): bool
    {
        return true;
    }

    public function runningUnitTests(): bool
    {
        return false;
    }

    public function hasDebugModeEnabled(): bool
    {
        return false;
    }

    public function getNamespace(): string
    {
        return 'App\\';
    }

    public function routesAreCached(): bool
    {
        return false;
    }

    public function configurationIsCached(): bool
    {
        return false;
    }

    public function terminating(callable $callback): void
    {
        $this->terminatingCallbacks[] = $callback;
    }

    public function terminate(): void
    {
        foreach ($this->terminatingCallbacks as $callback) {
            $callback();
        }
    }

    private function registerCoreBindings(): void
    {
        $compiledViewsPath = $this->storagePath('framework/views');

        if (! is_dir($compiledViewsPath)) {
            mkdir($compiledViewsPath, 0755, true);
        }

        $this->singleton('files', fn (): Filesystem => new Filesystem);
        $this->singleton(Filesystem::class, fn (): Filesystem => $this->make('files'));
        $this->singleton('events', fn (): Dispatcher => new Dispatcher($this));
        $this->singleton(Dispatcher::class, fn (): Dispatcher => $this->make('events'));
        $this->singleton('log', fn (): NullLogger => new NullLogger);
        $this->singleton('config', function () use ($compiledViewsPath): ConfigRepository {
            $config = new ConfigRepository([
                'app' => [
                    'name' => 'Pergament',
                    'env' => 'production',
                    'debug' => false,
                    'url' => 'http://localhost',
                    'asset_url' => null,
                    'locale' => 'en',
                    'fallback_locale' => 'en',
                    'key' => 'base64:'.base64_encode(random_bytes(32)),
                    'previous_keys' => [],
                ],
                'view' => [
                    'paths' => [$this->resourcePath('views')],
                    'compiled' => $compiledViewsPath,
                    'cache' => true,
                ],
            ]);

            $config->set('pergament', require __DIR__.'/../Config/pergament.php');

            return $config;
        });
        $this->singleton(ConfigRepository::class, fn (): ConfigRepository => $this->make('config'));
        $this->singleton('translator', fn (): Translator => new Translator(new ArrayLoader, 'en'));

        $this->instance('request', Request::create((string) config('app.url', 'http://localhost')));
    }

    private function registerFrameworkAliases(): void
    {
        $this->alias('config', ConfigRepository::class);
        $this->alias('events', DispatcherContract::class);
        $this->alias('files', FilesystemContract::class);
        $this->alias('log', LoggerInterface::class);
        $this->alias('url', UrlGeneratorContract::class);
        $this->alias('view', ViewFactoryContract::class);
    }

    private function registerPergamentViews(): void
    {
        View::addNamespace('pergament', __DIR__.'/../../resources/views');
        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/components', 'pergament');
        View::share('pergamentCssVersion', $this->assetVersion('pergament.css'));
        View::share('pergamentJsVersion', $this->assetVersion('pergament.js'));
    }

    private function registerStandaloneRoutes(): void
    {
        require __DIR__.'/../../routes/web.php';

        $this->make('router')->getRoutes()->refreshNameLookups();
        $this->make('router')->getRoutes()->refreshActionLookups();
    }

    private function assetVersion(string $file): string
    {
        $publishedPath = $this->publicPath('vendor/pergament/'.$file);
        $distPath = __DIR__.'/../../dist/'.$file;
        $path = is_file($publishedPath) ? $publishedPath : $distPath;

        return substr(md5_file($path), 0, 8);
    }

    private function joinPath(string $base, string $path): string
    {
        if ($path === '') {
            return $base;
        }

        return rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }
}

final class StandaloneConsoleKernel implements ConsoleKernelContract
{
    public function bootstrap(): void {}

    public function handle($input, $output = null): int
    {
        return 0;
    }

    public function call($command, array $parameters = [], $outputBuffer = null): int
    {
        return 0;
    }

    public function queue($command, array $parameters = []): void {}

    public function all(): array
    {
        return [];
    }

    public function output(): string
    {
        return '';
    }

    public function terminate($input, $status): void {}
}

final class StandaloneExceptionHandler implements ExceptionHandler
{
    public function report(Throwable $e): void {}

    public function shouldReport(Throwable $e): bool
    {
        return true;
    }

    public function render($request, Throwable $e)
    {
        throw $e;
    }

    public function renderForConsole($output, Throwable $e): void
    {
        $output->writeln($e->getMessage());
    }
}
