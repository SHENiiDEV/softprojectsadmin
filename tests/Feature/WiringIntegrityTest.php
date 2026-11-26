<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Catches the mistakes that only blow up when somebody opens the page: a <livewire:...> tag with the wrong name,
 * a wire:click calling a method that does not exist, a route that uses an unregistered middleware alias or a missing
 * route / permission / feature flag.
 */
class WiringIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /** Unused legacy component (no route, no view): known to reference things that do not exist. */
    private const LEGACY_FILES = ['app/Livewire/Companies/Create.php'];

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * @return list<string> relative paths
     */
    private function files(string $dir, string $suffix): array
    {
        $found = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root().'/'.$dir, \FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), $suffix)) {
                $found[] = substr($file->getPathname(), strlen($this->root()) + 1);
            }
        }

        sort($found);

        return $found;
    }

    private function read(string $relative): string
    {
        return (string) file_get_contents($this->root().'/'.$relative);
    }

    public function test_every_livewire_tag_points_to_a_component(): void
    {
        $missing = [];

        foreach ($this->files('resources/views', '.blade.php') as $view) {
            preg_match_all('/<livewire:([a-zA-Z0-9._-]+)/', $this->read($view), $matches);

            foreach ($matches[1] as $name) {
                if (in_array($name, ['is', 'dynamic-component'], true)) {
                    continue;
                }

                $segments = explode('.', $name);
                $class = implode('/', array_map(fn (string $segment) => str_replace(' ', '', ucwords(str_replace('-', ' ', $segment))), $segments)).'.php';

                $exists = file_exists($this->root().'/app/Livewire/'.$class)
                    || file_exists($this->root().'/resources/views/livewire/'.implode('/', $segments).'.blade.php');

                if (! $exists) {
                    $missing[] = "{$view}: <livewire:{$name}>";
                }
            }
        }

        $this->assertSame([], $missing, 'Livewire tags without a component');
    }

    public function test_every_wire_action_in_a_component_view_exists_on_the_component(): void
    {
        $missing = [];

        foreach ($this->files('app/Livewire', '.php') as $file) {
            if (in_array($file, self::LEGACY_FILES, true)) {
                continue;
            }

            if (! preg_match("/view\(\s*'([a-z0-9._-]+)'/", $this->read($file), $view)) {
                continue;
            }

            $viewFile = 'resources/views/'.str_replace('.', '/', $view[1]).'.blade.php';
            $this->assertFileExists($this->root().'/'.$viewFile, "{$file} renders a view that does not exist");

            $class = 'App\\'.str_replace('/', '\\', substr(substr($file, 4), 0, -4));
            if (! class_exists($class)) {
                continue;
            }

            preg_match_all('/wire:(?:click|change|submit|keydown|keyup|blur)(?:\.[a-z.]+)?="([A-Za-z_][A-Za-z0-9_]*)/', $this->read($viewFile), $actions);

            foreach (array_unique($actions[1]) as $action) {
                if (! method_exists($class, $action)) {
                    $missing[] = "{$viewFile}: {$action}() is not a method of {$class}";
                }
            }
        }

        $this->assertSame([], $missing, 'wire actions without a method');
    }

    public function test_every_route_middleware_alias_is_registered(): void
    {
        $router = app('router');
        $known = array_merge(array_keys($router->getMiddleware()), array_keys($router->getMiddlewareGroups()));
        $unknown = [];

        foreach (Route::getRoutes() as $route) {
            foreach ($route->gatherMiddleware() as $middleware) {
                if (! is_string($middleware)) {
                    continue;
                }

                $name = Str::before($middleware, ':');

                if (! in_array($name, $known, true) && ! class_exists($name)) {
                    $unknown[] = $route->uri().' uses "'.$name.'"';
                }
            }
        }

        $this->assertSame([], array_values(array_unique($unknown)), 'Unregistered middleware aliases (e.g. "role" without an alias in bootstrap/app.php)');
    }

    public function test_named_routes_used_in_code_exist(): void
    {
        $missing = [];
        $files = array_merge($this->files('app', '.php'), $this->files('resources/views', '.php'));

        foreach ($files as $file) {
            if (in_array($file, self::LEGACY_FILES, true)) {
                continue;
            }

            preg_match_all("/(?<!::)(?<![A-Za-z])route\(\s*'([a-z0-9._-]+)'/", $this->read($file), $matches);

            foreach (array_unique($matches[1]) as $name) {
                if (! Route::has($name)) {
                    $missing[] = "{$file}: route('{$name}')";
                }
            }
        }

        $this->assertSame([], $missing, 'Undefined route names');
    }

    public function test_permissions_and_feature_flags_used_in_code_exist(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $permissions = Permission::pluck('name')->all();
        $flags = array_keys(config('features'));
        $missing = [];

        $files = array_merge($this->files('app', '.php'), $this->files('resources/views', '.php'), $this->files('routes', '.php'), $this->files('config', '.php'));

        foreach ($files as $file) {
            $source = $this->read($file);

            preg_match_all("/(?:can\(|@can\(|@cannot\(|hasPermissionTo\(|requirePermission\(|permission:)\s*'((?:view|manage)_[a-z_]+)'?/", $source, $perms);
            preg_match_all('/permission:((?:view|manage)_[a-z_]+)/', $source, $routePerms);

            foreach (array_unique(array_merge($perms[1], $routePerms[1])) as $permission) {
                if (! in_array($permission, $permissions, true)) {
                    $missing[] = "{$file}: permission {$permission}";
                }
            }

            if (str_ends_with($file, 'config/features.php')) {
                continue;
            }

            preg_match_all("/features\.([a-z_]+)/", $source, $used);
            preg_match_all('/feature:([a-z_]+)/', $source, $routeFlags);

            foreach (array_unique(array_merge($used[1], $routeFlags[1])) as $flag) {
                // "features.php" in a comment is the config file, not a flag
                if ($flag !== 'php' && ! in_array($flag, $flags, true)) {
                    $missing[] = "{$file}: feature flag {$flag}";
                }
            }
        }

        $this->assertSame([], $missing, 'Permissions / feature flags that do not exist');
    }

    public function test_every_page_renders_for_an_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['features' => array_fill_keys(array_keys(config('features')), true)]);

        $admin = User::factory()->create()->assignRole('admin');
        $this->actingAs($admin);

        $failed = [];

        foreach (Route::getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();

            if (! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{') || ! in_array('auth', $middleware, true)) {
                continue;
            }

            $status = $this->get('/'.ltrim($route->uri(), '/'))->getStatusCode();

            if (! in_array($status, [200, 302], true)) {
                $failed[] = "/{$route->uri()} -> {$status}";
            }
        }

        $this->assertSame([], $failed, 'Pages that do not render');
    }

    public function test_pages_with_parameters_render_for_an_admin(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        config(['features' => array_fill_keys(array_keys(config('features')), true)]);

        $admin = User::factory()->create()->assignRole('admin');
        $client = Client::factory()->create();
        $project = Project::factory()->create(['client_id' => $client->id]);
        $task = Task::create(['title' => 'Smoke', 'project_id' => $project->id, 'status' => 'todo', 'priority' => 'low', 'type' => 'kyb']);

        $urls = [
            route('projects.show', $project),
            route('projects.show', $project).'?tab=onboarding',
            route('projects.show', $project).'?tab=boarding',
            route('projects.show', $project).'?tab=operations',
            route('projects.edit', $project),
            route('tasks.kanban', ['task_id' => $task->id]),
            route('tasks.kanban', ['new' => 'kyb', 'project_id' => $project->id]),
            route('onboarding.index', ['open' => 'ONB-'.str_pad((string) $project->id, 3, '0', STR_PAD_LEFT)]),
            route('client.portal', $client->hash),
        ];

        $failed = [];
        foreach ($urls as $url) {
            $status = $this->actingAs($admin)->get($url)->getStatusCode();

            if ($status !== 200) {
                $failed[] = "{$url} -> {$status}";
            }
        }

        $this->assertSame([], $failed, 'Pages with parameters that do not render');
    }
}
