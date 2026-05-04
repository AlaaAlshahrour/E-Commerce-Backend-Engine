<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateFullProjectCompact extends Command
{
    protected $signature = 'generate:exportProject';

    protected $description = 'Generate FullProject.php to AI';

    public function handle(): void
    {
        $outputPath = base_path('FullProject.php');
        $content = "// === FULL PROJECT COMPACT EXPORT ===\n";

        $sections = [
            'Controllers' => app_path('Http/Controllers'),
            'ApiControllers' => app_path('Http/Controllers/Api'),
            'Services' => app_path('Services'),
            'Models' => app_path('Models'),
            'Enums' => app_path('Enums'),
            'Migrations' => database_path('migrations'),
            'Seeders' => database_path('seeders'),
            'Factories' => database_path('factories'),
            'Resources' => app_path('Http/Resources'),
            'Requests' => app_path('Http/Requests'),
            'Config' => base_path('config'),
            'Routes' => base_path('routes'),
            'Providers' => app_path('Providers'),
            'Bootstrap' => base_path('Bootstrap'),
            //    'Resources_view' => base_path('resources/views'),
            //    'Resources_view_layouts' => base_path('resources/views/layouts'),
            //    'Resources_view_layouts_main' => base_path('resources/views/layouts_main'),
            //    'Resources_view_main' => base_path('resources/views/main'),
            //    'Resources_view_requests' => base_path('resources/views/requests'),
            //    'Resources_view_services' => base_path('resources/views/services'),
            //    'Resources_view_users' => base_path('resources/views/users'),
            //    'Resources_view_settings' => base_path('resources/views/setting'),
            //    'Lang_ar' => base_path('resources/lang/ar'),
            //    'Lang_en' => base_path('resources/lang/en'),
        ];

        foreach ($sections as $sectionName => $path) {
            if (! File::exists($path)) {
                $this->warn("$sectionName directory not found, skipping...");

                continue;
            }

            $files = File::allFiles($path);
            $files = array_filter($files, function ($file) use ($sectionName) {
                if ($sectionName === 'Migrations') {
                    return $file->getExtension() === 'php';
                }

                return in_array($file->getExtension(), ['php']);
            });

            usort($files, function ($a, $b) {
                return strcmp($a->getFilename(), $b->getFilename());
            });

            $content .= "\n// === [$sectionName] ===\n";

            foreach ($files as $file) {
                $filename = str_replace(base_path().'/', '', $file->getRealPath());
                $fileContent = File::get($file->getRealPath());

                $fileContent = str_replace(['<?php', '?>'], '', $fileContent);
                $fileContent = preg_replace('/^use .*;/m', '', $fileContent);
                $fileContent = preg_replace('/^declare\(.*\);/m', '', $fileContent);

                // إزالة التعليقات //
                // إزالة التعليقات #
                $fileContent = preg_replace('/\/\/.*$/m', '', $fileContent);
                $fileContent = preg_replace('/#.*$/m', '', $fileContent);
                // إزالة التعليقات /* */
                $fileContent = preg_replace('#/\*.*?\*/#s', '', $fileContent);

                // إزالة الأسطر الفارغة
                $fileContent = preg_replace('/^\s*$(?:\r\n?|\n)/m', '', $fileContent);

                // ضغط الأسطر لتقليل الحجم:
                $fileContent = preg_replace('/\s*{\s*/', '{', $fileContent);
                $fileContent = preg_replace('/\s*}\s*/', '}', $fileContent);
                $fileContent = preg_replace('/\s*;\s*/', ';', $fileContent);
                $fileContent = preg_replace('/\s*\(\s*/', '(', $fileContent);
                $fileContent = preg_replace('/\s*\)\s*/', ')', $fileContent);
                $fileContent = preg_replace('/\s*,\s*/', ',', $fileContent);

                // إزالة الفراغات المتكررة
                $fileContent = preg_replace('/[ ]{2,}/', ' ', $fileContent);

                // إزالة الفراغات بداية كل سطر
                $fileContent = preg_replace('/^\s+/m', '', $fileContent);

                $content .= "// ===== $filename =====\n";
                $content .= $fileContent."\n";
            }
        }

        File::put($outputPath, $content);

        $this->info('FullProject.php generated successfully with minified, cleaned, organized content.');
    }
}
