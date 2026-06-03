# Standalone Pergament CLI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a `pergament` Composer binary that can generate a static site without a host Laravel application.

**Architecture:** Add a thin `bin/pergament` entry point and a focused standalone console bootstrap that provides the minimal Illuminate runtime needed by the existing `GenerateStaticCommand`. Keep `GenerateStaticCommand` as the single source of export behavior and only adapt command dispatch/bootstrapping.

**Tech Stack:** PHP 8.4, Illuminate Console/Container/Config/View/Filesystem/Events/Routing, Blade, Pest, Composer bin scripts.

---

## File Structure

- Create `bin/pergament`: executable CLI entry point that locates Composer autoload, normalizes `generate-static` to `pergament:generate-static`, and exits with the standalone app status code.
- Create `src/Console/StandaloneApplication.php`: builds the minimal Illuminate console application, registers bindings/config/views/routes, and registers only `GenerateStaticCommand`.
- Modify `composer.json`: add the `bin` entry and required Illuminate packages for standalone execution.
- Create `tests/Feature/StandaloneCliTest.php`: verifies the binary can generate representative static output and handles unknown commands.
- Modify `README.md`: document GitHub Actions/GitHub Pages usage.

## Task 1: Add Failing Standalone CLI Tests

**Files:**
- Create: `tests/Feature/StandaloneCliTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/StandaloneCliTest.php`:

```php
<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;

beforeEach(function (): void {
    $this->outputDir = sys_get_temp_dir().'/pergament-standalone-'.uniqid();
});

afterEach(function (): void {
    if (! is_dir($this->outputDir)) {
        return;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($this->outputDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
    }

    rmdir($this->outputDir);
});

it('generates a static site through the standalone binary', function (): void {
    $process = new Process([
        PHP_BINARY,
        __DIR__.'/../../bin/pergament',
        'generate-static',
        $this->outputDir,
        '--content-path='.__DIR__.'/../fixtures/content',
        '--base-url=https://example.github.io/pergament',
        '--clean',
    ], __DIR__.'/../..');

    $process->setTimeout(60);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput().$process->getOutput());
    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/docs/getting-started/introduction.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/blog/hello-world.html'))->toBeTrue();
    expect(file_exists($this->outputDir.'/search.json'))->toBeTrue();
    expect(file_exists($this->outputDir.'/sitemap.xml'))->toBeTrue();
});

it('supports the existing artisan command name as an alias', function (): void {
    $process = new Process([
        PHP_BINARY,
        __DIR__.'/../../bin/pergament',
        'pergament:generate-static',
        $this->outputDir,
        '--content-path='.__DIR__.'/../fixtures/content',
        '--clean',
    ], __DIR__.'/../..');

    $process->setTimeout(60);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput().$process->getOutput());
    expect(file_exists($this->outputDir.'/index.html'))->toBeTrue();
});

it('returns a non-zero status for unknown standalone commands', function (): void {
    $process = new Process([
        PHP_BINARY,
        __DIR__.'/../../bin/pergament',
        'unknown-command',
    ], __DIR__.'/../..');

    $process->setTimeout(60);
    $process->run();

    expect($process->isSuccessful())->toBeFalse();
    expect($process->getErrorOutput().$process->getOutput())->toContain('Unknown command');
});
```

- [ ] **Step 2: Run the new test file to verify it fails**

Run:

```bash
./vendor/bin/pest tests/Feature/StandaloneCliTest.php
```

Expected: FAIL because `bin/pergament` does not exist.

## Task 2: Add Composer Runtime Dependencies And Binary Registration

**Files:**
- Modify: `composer.json`
- Modify: `composer.lock` via Composer

- [ ] **Step 1: Add required Illuminate packages and bin entry**

Run:

```bash
composer require illuminate/console:"^11.0|^12.0|^13.0" illuminate/config:"^11.0|^12.0|^13.0" illuminate/container:"^11.0|^12.0|^13.0" illuminate/events:"^11.0|^12.0|^13.0" illuminate/filesystem:"^11.0|^12.0|^13.0" illuminate/routing:"^11.0|^12.0|^13.0" illuminate/view:"^11.0|^12.0|^13.0"
```

Then edit `composer.json` to add the bin entry after `autoload-dev`:

```json
    "bin": [
        "bin/pergament"
    ],
```

- [ ] **Step 2: Validate Composer metadata**

Run:

```bash
composer validate --strict
```

Expected: PASS. Composer may warn if package metadata is intentionally minimal; fix only validation errors.

- [ ] **Step 3: Commit this dependency slice if committing is requested**

Run only if the user explicitly requested commits:

```bash
git add composer.json composer.lock
git commit -m "build: add standalone cli dependencies"
```

## Task 3: Implement Standalone Console Bootstrap

**Files:**
- Create: `src/Console/StandaloneApplication.php`

- [ ] **Step 1: Create the bootstrap implementation**

Create `src/Console/StandaloneApplication.php`:

```php
<?php

declare(strict_types=1);

namespace Pergament\Console;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Console\Application as ConsoleApplication;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\RoutingServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewServiceProvider;
use Pergament\Console\Commands\GenerateStaticCommand;
use Pergament\PergamentServiceProvider;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Throwable;

final class StandaloneApplication extends Container
{
    private string $basePath;

    /** @var array<int, ServiceProvider> */
    private array $serviceProviders = [];

    public function __construct(string $basePath)
    {
        parent::__construct();

        $this->basePath = mb_rtrim($basePath, DIRECTORY_SEPARATOR);

        Container::setInstance($this);
        Facade::setFacadeApplication($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->instance('path.base', $this->basePath);
        $this->instance('path.config', $this->basePath.'/config');
        $this->instance('path.public', $this->basePath.'/public');
        $this->instance('path.resources', $this->basePath.'/resources');
        $this->instance('path.storage', sys_get_temp_dir().'/pergament-standalone');

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
        $this->bootstrap();

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

    public function bootstrap(): void
    {
        $this->registerCoreBindings();
        $this->register(new RoutingServiceProvider($this));
        $this->register(new ViewServiceProvider($this));
        $this->register(new PergamentServiceProvider($this));
        $this->bootProviders();
        $this->registerStandaloneRoutes();
    }

    public function basePath(string $path = ''): string
    {
        return $this->basePath.($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    public function configPath(string $path = ''): string
    {
        return $this->basePath('config'.($path !== '' ? DIRECTORY_SEPARATOR.$path : ''));
    }

    public function publicPath(string $path = ''): string
    {
        return $this->basePath('public'.($path !== '' ? DIRECTORY_SEPARATOR.$path : ''));
    }

    public function resourcePath(string $path = ''): string
    {
        return $this->basePath('resources'.($path !== '' ? DIRECTORY_SEPARATOR.$path : ''));
    }

    public function storagePath(string $path = ''): string
    {
        $base = sys_get_temp_dir().'/pergament-standalone';

        return $base.($path !== '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    public function runningInConsole(): bool
    {
        return true;
    }

    public function environment(...$environments): string|bool
    {
        if ($environments === []) {
            return 'production';
        }

        return in_array('production', $environments, true);
    }

    public function register(ServiceProvider $provider): ServiceProvider
    {
        $provider->register();
        $this->serviceProviders[] = $provider;

        return $provider;
    }

    private function bootProviders(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $this->call([$provider, 'boot']);
            }
        }
    }

    private function registerCoreBindings(): void
    {
        $this->singleton('files', fn (): Filesystem => new Filesystem);
        $this->singleton('events', fn (): Dispatcher => new Dispatcher($this));
        $this->singleton('config', function (): ConfigRepository {
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
                ],
                'view' => [
                    'paths' => [$this->resourcePath('views')],
                    'compiled' => $this->storagePath('framework/views'),
                ],
            ]);

            $config->set('pergament', require __DIR__.'/../Config/pergament.php');

            return $config;
        });
        $this->singleton('translator', fn (): Translator => new Translator(new ArrayLoader, 'en'));
    }

    private function registerStandaloneRoutes(): void
    {
        require __DIR__.'/../../routes/web.php';
    }
}

final class StandaloneConsoleKernel implements ConsoleKernelContract
{
    public function bootstrap(): void {}

    public function handle($input, $output = null): int
    {
        return 0;
    }

    public function terminate($input, int $status): void {}

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
```

- [ ] **Step 2: Run the standalone tests**

Run:

```bash
./vendor/bin/pest tests/Feature/StandaloneCliTest.php
```

Expected: FAIL because `bin/pergament` still does not exist, or with missing bootstrap bindings. If it fails due to unused imports in this new class, remove unused imports before continuing.

## Task 4: Add The `bin/pergament` Entry Point

**Files:**
- Create: `bin/pergament`

- [ ] **Step 1: Add the executable script**

Before creating the file, verify the parent directory exists or create it with `mkdir -p bin`.

Create `bin/pergament`:

```php
#!/usr/bin/env php
<?php

declare(strict_types=1);

$autoloadCandidates = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../autoload.php',
];

$autoload = null;

foreach ($autoloadCandidates as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}

if ($autoload === null) {
    fwrite(STDERR, "Unable to locate Composer autoload. Run composer install before using pergament.\n");
    exit(1);
}

require $autoload;

use Pergament\Console\StandaloneApplication;

try {
    exit(StandaloneApplication::runFromBasePath(getcwd()));
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage().PHP_EOL);
    exit(1);
}
```

- [ ] **Step 2: Make the binary executable**

Run:

```bash
chmod +x bin/pergament
```

- [ ] **Step 3: Run the standalone tests**

Run:

```bash
./vendor/bin/pest tests/Feature/StandaloneCliTest.php
```

Expected: PASS or fail with a specific missing Illuminate binding/helper. If a binding is missing, add the smallest binding to `StandaloneApplication` and rerun this command until it passes.

## Task 5: Document GitHub Pages Usage

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Add a README section**

Add this section near existing static generation or usage documentation in `README.md`:

```markdown
## Standalone Static Export

Pergament ships a standalone CLI for generating static HTML without a host Laravel application:

```bash
vendor/bin/pergament generate-static public --content-path=content --base-url="https://example.com"
```

The command accepts the same options as the Laravel Artisan command:

- `--content-path=` overrides the content directory.
- `--prefix=` overrides the generated Pergament route prefix.
- `--base-url=` sets the absolute site URL used by canonical URLs, feeds, and sitemap output.
- `--clean` removes the output directory before generating.

### GitHub Pages

Use the standalone CLI in GitHub Actions to publish generated files to GitHub Pages:

```yaml
name: Deploy Pergament site

on:
  push:
    branches: [main]

permissions:
  contents: read
  pages: write
  id-token: write

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
      - run: composer install --no-dev --prefer-dist --no-interaction
      - run: vendor/bin/pergament generate-static public --content-path=content --base-url="https://OWNER.github.io/REPOSITORY"
      - uses: actions/upload-pages-artifact@v3
        with:
          path: public

  deploy:
    needs: build
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@v4
```
```

- [ ] **Step 2: Check README formatting**

Run:

```bash
composer test:lint
```

Expected: PASS for PHP formatting. Manually inspect the README section to ensure fenced code blocks are balanced.

## Task 6: Full Verification

**Files:**
- Verify all modified files

- [ ] **Step 1: Run focused tests**

Run:

```bash
./vendor/bin/pest tests/Feature/StandaloneCliTest.php tests/Feature/GenerateStaticCommandTest.php
```

Expected: PASS.

- [ ] **Step 2: Run project test suite**

Run:

```bash
composer test
```

Expected: PASS for Pint and Pest with coverage threshold.

- [ ] **Step 3: Manually smoke-test the binary**

Run:

```bash
rm -rf /tmp/pergament-cli-smoke && php bin/pergament generate-static /tmp/pergament-cli-smoke --content-path=tests/fixtures/content --base-url=https://example.github.io/pergament --clean
```

Expected: exit code 0 and output includes `Static site generated successfully.`.

- [ ] **Step 4: Inspect representative output**

Run:

```bash
test -f /tmp/pergament-cli-smoke/index.html && test -f /tmp/pergament-cli-smoke/sitemap.xml && test -f /tmp/pergament-cli-smoke/search.json
```

Expected: exit code 0.

- [ ] **Step 5: Commit all implementation changes if committing is requested**

Run only if the user explicitly requested commits:

```bash
git status --short
git add bin/pergament composer.json composer.lock src/Console/StandaloneApplication.php tests/Feature/StandaloneCliTest.php README.md docs/superpowers/specs/2026-06-03-standalone-pergament-cli-design.md docs/superpowers/plans/2026-06-03-standalone-pergament-cli.md
git commit -m "feat: add standalone pergament cli"
```

## Self-Review

- Spec coverage: covered binary registration, standalone bootstrap, reuse of `GenerateStaticCommand`, static-export-only scope, GitHub Pages usage, error handling, and tests.
- Placeholder scan: no incomplete implementation instructions remain.
- Type consistency: plan consistently uses `Pergament\Console\StandaloneApplication`, `bin/pergament`, and `GenerateStaticCommand`.
