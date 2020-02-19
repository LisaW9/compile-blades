<?php

namespace Techo\CompileBlades\Console;

use Illuminate\Console\Command;
use Illuminate\Foundation\Inspiring;

class CompileBlades extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
     protected $signature = 'compile:blades {blade-name} {--location=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compile blades into 1 flat file';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $viewName = $this->argument('blade-name');

        $blade = $this->compile(view($viewName)->getPath());

        if(is_null($this->option('location'))) {

            file_put_contents(view($viewName)->getPath(), $blade);

        } else {

            $location = str_replace('.', '/', $this->option('location'));
            $newPath = resource_path('views') . "/$location.blade.php";

            $dirname = dirname($newPath);
            if (!is_dir($dirname))
            {
                mkdir($dirname, 0755, true);
            }

            file_put_contents($newPath, $blade);
        }

        $this->comment(PHP_EOL . Inspiring::quote() . PHP_EOL);
    }

    private function compile($viewPath)
    {
        $blade = file_get_contents($viewPath);
        $this->implodeLayout($blade); //A1 @inprogress
        $this->implodeIncludes($blade); //A2 @pending

        return $blade;
    }

    private function implodeLayout(&$blade)
    {
        $sections = $this->seperateSections($blade);//B1 @done
        $this->replaceLayout($blade);//B2 @done
        $this->replaceSections($blade, $sections);//B3 @inprogress


    }

    private function implodeIncludes(&$blade)
    {
        $i = 0;

        // get includes names
        preg_match_all("/@include[(]['|\"](.*?)['|\"]((,)(.*?))?[)]$/im", $blade, $pregOutput);
        
        $this->ignoreComposerViews($pregOutput);

        while (!empty($pregOutput[0])) {

            // split array from include name
            $includes = $pregOutput[1];
            $arraysSent = $pregOutput[4];

            // split array valriables
            // define variables
            $includesWithVariables = [];
            foreach ($includes as $index => $include) {
                $arrayOfVariables = empty($arraysSent[$index]) ? '[]' : $arraysSent[$index];
                $arrayOfVariablesExtraction = '<?php extract(' . $arrayOfVariables . '); ?>';
                $includesWithVariables[$include] = $arrayOfVariablesExtraction;
            }

            // Include files and append variables
            foreach ($includesWithVariables as $subViewName => $arrayOfVariables) {
                $subView = $arrayOfVariables . "\r\n" . file_get_contents(view($subViewName)->getPath());
                $blade =
                    preg_replace("/@include[(]['|\"]" . $subViewName . "['|\"]((,)(.*?))?[)]$/im", $subView, $blade);
            }

            preg_match_all("/@include[(]['|\"](.*?)['|\"]((,)(.*?))?[)]$/im", $blade, $pregOutput);
            $this->ignoreComposerViews($pregOutput);
            if (++$i > 2) {
                break;
            }
        }

        return $blade;
    }

    /**
     * Extracts the sections from the blade and cleans the blade from them
     *
     * @param $blade
     *
     * @return array
     * @done
     */
    private function seperateSections(&$blade)
    {
        preg_match_all("/@section[(]['|\"](.*?)['|\"][)](.*?)@endsection/si", $blade, $pregOutput);
        $blade = preg_replace("/@section[(]['|\"](.*?)['|\"][)](.*?)@endsection/si", "{{-- section $1 was here --}}", $blade);
        $sections = [];
        foreach ($pregOutput[2] as $index => $section) {
            $sections[$pregOutput[1][$index]] = $section;
        }

        return $sections;
    }

    private function replaceLayout(&$blade)
    {
        //find the extended file
        preg_match_all("/@extends[(]['|\"](.*?)['|\"][)]/si", $blade, $output);

        if (!empty($output[1])) {
            $layout = $output[1][0];
            //take out the extend keyword
            $blade = preg_replace("/@extends[(]['|\"](.*?)['|\"][)]/si", "{{-- Extend $1 was here --}}", $blade);
            //bring the layout
            $layout = file_get_contents(view($layout)->getPath());
            $blade = $blade . " " . $layout;
        }
    }

    private function replaceSections(&$blade, $sections)
    {
        preg_match_all("/@yield[(]['|\"](.*?)['|\"][)]/si", $blade, $output);
        $sectionsName = $output[1];
        foreach ($sectionsName as $sectionName) {
            $sectionNameWithAlt = explode('\', \'', $sectionName);
            if (isset($sections[$sectionNameWithAlt[0]])) {
                $blade = preg_replace(
                    "/@yield[(]['|\"]" . $sectionNameWithAlt[0] . "['|\"].*?[)]$/m",
                    $sections[$sectionNameWithAlt[0]],
                    $blade
                );
            } else {
                $blade = preg_replace(
                    "/@yield[(]['|\"]" . $sectionNameWithAlt[0] . "['|\"].*?[)]$/m",
                    $sectionNameWithAlt[1] ?? '{{--yield didnt have alternative--}}',
                    $blade
                );
            }
        }
    }

    private function ignoreComposerViews(&$pregOutput)
    {
        if(config('compileblades.view_composers.exclude_sections') && config('compileblades.view_composers.composerserviceprovider_location')) {

            $provider = file_get_contents(config('compileblades.view_composers.composerserviceprovider_location'));
            preg_match_all("/(View::composer|view[(][)]->composer)[(]['|\"](.*?)['|\"],(.*?)[)]/si", $provider, $output);

            foreach($output[2] as $exclude) {
                $key = array_search($exclude, $pregOutput[1], true);
                if($key !== false) {
                    unset($pregOutput[0][$key]);
                    unset($pregOutput[1][$key]);
                    unset($pregOutput[2][$key]);
                    unset($pregOutput[3][$key]);
                    unset($pregOutput[4][$key]);
                }
            }
        }
    }
}
