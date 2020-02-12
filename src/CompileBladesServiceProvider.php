<?php

namespace Lisaw\CompileBlades;

use Illuminate\Support\ServiceProvider;
use Lisaw\CompileBlades\Console\CompileBlades;

/**
 * Class CompileBladesServiceProvider
 * @package Lisaw\CompileBlades
 */
class CompileBladesServiceProvider extends ServiceProvider
{
    public function register()
    {

    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(
                [
                    CompileBlades::class,
                ]
            );
        }
    }
}
