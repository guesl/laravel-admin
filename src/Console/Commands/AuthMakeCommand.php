<?php

namespace Guesl\Admin\Console\Commands;

use Guesl\Admin\Contracts\Constant;

class AuthMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guesl:auth
                    {--template : Template name, "metronic" as default.}
                    {--force : Overwrite existing views by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic login and registration views and routes';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();

        $this->exportViews();
        $this->exportAssets();
        $this->exportRoute();

        $this->info('Successful: Authentication scaffolding generated successfully.');
    }

    /**
     * Export the authentication route.
     *
     * @return void
     */
    protected function exportRoute()
    {
        $webRoutePath = base_path('routes/web.php');
        if (!$this->isAuthConfigExists()) {
            $authRoutes = 'Auth::routes();';

            file_put_contents(
                $webRoutePath,
                $authRoutes,
                FILE_APPEND
            );

            $this->info('Updated: ' . $webRoutePath);
        }
    }

    /**
     * Check if the url config exists.
     *
     * @return bool
     */
    protected function isAuthConfigExists()
    {
        $isAuthConfigExists = false;
        $webRoutePath = base_path('routes/web.php');
        $fileArray = file($webRoutePath);

        $authRoutes = 'Auth::routes();';

        foreach ($fileArray as $line) {
            if (strpos($line, $authRoutes) !== false) {
                $isAuthConfigExists = true;
                break;
            }
        }

        return $isAuthConfigExists;
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        $this->makeDirectory(app_path('Http/Controllers/Admin'));
        $this->makeDirectory(resource_path('views/auth/layouts'));
        $this->makeDirectory(resource_path('views/auth/incs'));
        $this->makeDirectory(resource_path('views/auth/passwords'));
        $this->makeDirectory(public_path('admin/images'));
    }

    /**
     * Get The views that need to be exported.
     *
     * @return array
     */
    protected function getViews()
    {
        $template = $this->option('template') ?: Constant::TEMPLATE_DEFAULT;

        return [
            "templates/{$template}/auth/login.blade.stub" => "auth/login.blade.php",
            "templates/{$template}/auth/register.blade.stub" => "auth/register.blade.php",
            "templates/{$template}/auth/passwords/email.blade.stub" => "auth/passwords/email.blade.php",
            "templates/{$template}/auth/passwords/reset.blade.stub" => "auth/passwords/reset.blade.php",
            "templates/{$template}/auth/incs/foot.blade.stub" => "auth/incs/foot.blade.php",
            "templates/{$template}/auth/incs/head.blade.stub" => "auth/incs/head.blade.php",
            "templates/{$template}/auth/layouts/app.blade.stub" => "auth/layouts/app.blade.php",
        ];
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        $views = $this->getViews();
        foreach ($views as $key => $value) {
            if (file_exists($view = resource_path('views/' . $value)) && !$this->option('force')) {
                if (!$this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            copy(
                __DIR__ . '/stubs/make/views/' . $key,
                $view
            );

            $this->info('Generated: ' . $view);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportAssets()
    {
        if (!file_exists($logoPath = public_path('admin/images/gu.png'))) {
            copy(
                __DIR__ . '/../../../public/images/gu.png',
                $logoPath
            );
        }

        if (!file_exists($logoPath = public_path('admin/images/guesl.png'))) {
            copy(
                __DIR__ . '/../../../public/images/guesl.png',
                $logoPath
            );
        }

        if (!file_exists($logoPath = public_path('admin/images/guesl-blue.png'))) {
            copy(
                __DIR__ . '/../../../public/images/guesl-blue.png',
                $logoPath
            );
        }

        if (!file_exists($logoPath = public_path('admin/images/guesl-purple.png'))) {
            copy(
                __DIR__ . '/../../../public/images/guesl-purple.png',
                $logoPath
            );
        }

        if (!file_exists($logoPath = public_path('admin/images/guesl-white.png'))) {
            copy(
                __DIR__ . '/../../../public/images/guesl-white.png',
                $logoPath
            );
        }

        $this->info('Generated: Images(Guesl Logos).');

        if (is_dir($templatesDir = public_path('templates')) && !$this->option('force')) {
            if (!$this->confirm("The template assets already exists. Do you want to replace it?")) {
                return;
            }

            $this->rrmdir($templatesDir);
        }

        $this->recurseCopy(__DIR__ . '/../../../public/templates', $templatesDir);

        $this->info('Generated: ' . $templatesDir);
    }

    /**
     * Recurse copy entire directory.
     *
     * @param string $src
     * @param string $dst
     */
    protected function recurseCopy($src, $dst)
    {
        if (!file_exists($dst)) {
            $dir = opendir($src);
            mkdir($dst);
            while (false !== ($file = readdir($dir))) {
                if (($file != '.') && ($file != '..')) {
                    if (is_dir($src . '/' . $file)) {
                        $this->recurseCopy($src . '/' . $file, $dst . '/' . $file);
                    } else {
                        copy($src . '/' . $file, $dst . '/' . $file);
                    }
                }
            }
            closedir($dir);
        }
    }

    /**
     * Recurse remove entire directory.
     *
     * @param string $src
     */
    protected function rrmdir($src)
    {
        $dir = opendir($src);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->rrmdir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($src);
    }
}
