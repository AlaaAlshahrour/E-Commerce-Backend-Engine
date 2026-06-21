<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;

class GenerateFullProjectCompact extends Command
{
    protected $signature = 'generate:exportProject';

    protected $description = 'Generate FullProject.php to AI';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $outputPath = base_path('FullProject.php');
        $content = "// === FULL PROJECT COMPACT EXPORT ===\n";

        $sections = [
            'Console' => app_path('Console'),
            'Enums' => app_path('Enums'),
            'Helpers' => app_path('Helpers'),
            'Controllers' => app_path('Http/Controllers'),
            'Middleware' => app_path('Http/Middleware'),
            'Requests' => app_path('Http/Requests'),
            'Jobs' => app_path('Jobs'),
            'Models' => app_path('Models'),
            'Processors' => app_path('Processors'),
            'Providers' => app_path('Providers'),
            'Repositories' => app_path('Repositories'),
            'Services' => app_path('Services'),
            'Bootstrap' => base_path('bootstrap'),
            'Config' => base_path('config'),
            'Migrations' => database_path('migrations'),
            'Seeders' => database_path('seeders'),
            'Factories' => database_path('factories'),
            'Routes' => base_path('routes'),
            'Resources_views_pdf' => base_path('resources/views/pdf'),
            'ApiCollections' => base_path('api-collections'),
            'Tests' => base_path('tests'),
        ];

        foreach ($sections as $sectionName => $path) {
            if (! File::exists($path)) {
                $this->warn("$sectionName directory not found, skipping...");
                continue;
            }

            $isYamlSection = $sectionName === 'ApiCollections';
            $isTestSection = $sectionName === 'Tests';

            $files = File::allFiles($path);
            $files = array_filter($files, function ($file) use ($isYamlSection, $isTestSection) {
                $ext = $file->getExtension();

                if ($isYamlSection) {
                    return in_array($ext, ['yaml', 'yml']);
                }

                if ($isTestSection) {
                    return in_array($ext, ['php', 'js']);
                }

                return $ext === 'php';
            });

            usort($files, function ($a, $b) {
                return strcmp($a->getFilename(), $b->getFilename());
            });

            $content .= "\n// === [$sectionName] ===\n";

            foreach ($files as $file) {
                $filename = str_replace(base_path().'/', '', $file->getRealPath());
                $fileContent = File::get($file->getRealPath());
                $ext = $file->getExtension();

                if ($isYamlSection || $ext === 'js') {
                    $fileContent = preg_replace('/#.*$/m', '', $fileContent);
                    $fileContent = preg_replace('/\/\/.*$/m', '', $fileContent);
                    $fileContent = preg_replace('/^\s*$(?:\r\n?|\n)/m', '', $fileContent);
                } else {
                    $fileContent = str_replace(['<?php', '?>'], '', $fileContent);
                    $fileContent = preg_replace('/^use .*;/m', '', $fileContent);
                    $fileContent = preg_replace('/^declare\(.*\);/m', '', $fileContent);
                    $fileContent = preg_replace('/\/\/.*$/m', '', $fileContent);
                    $fileContent = preg_replace('/#.*$/m', '', $fileContent);
                    $fileContent = preg_replace('#/\*.*?\*/#s', '', $fileContent);
                    $fileContent = preg_replace('/^\s*$(?:\r\n?|\n)/m', '', $fileContent);
                    $fileContent = preg_replace('/\s*{\s*/', '{', $fileContent);
                    $fileContent = preg_replace('/\s*}\s*/', '}', $fileContent);
                    $fileContent = preg_replace('/\s*;\s*/', ';', $fileContent);
                    $fileContent = preg_replace('/\s*\(\s*/', '(', $fileContent);
                    $fileContent = preg_replace('/\s*\)\s*/', ')', $fileContent);
                    $fileContent = preg_replace('/\s*,\s*/', ',', $fileContent);
                    $fileContent = preg_replace('/[ ]{2,}/', ' ', $fileContent);
                    $fileContent = preg_replace('/^\s+/m', '', $fileContent);
                }

                $content .= "// ===== $filename =====\n";
                $content .= $fileContent."\n";
            }
        }

        $rootFiles = [
            '.env',
            'Dockerfile',
            'docker-compose.yml',
            'docker-compose_redis.yml',
            'vite.config.js',
            'package.json'
        ];

        $content .= "\n// === [Root Environment & Docker Files] ===\n";
        foreach ($rootFiles as $rootFile) {
            $filePath = base_path($rootFile);
            if (File::exists($filePath)) {
                $content .= "// ===== $rootFile =====\n";
                $content .= File::get($filePath)."\n";
            }
        }

        File::put($outputPath, $content);

        $this->info('FullProject.php generated successfully with minified, cleaned, organized content.');
    }
}
