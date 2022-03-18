<?php

namespace WalkerChiu\MorphNav;

use Illuminate\Support\ServiceProvider;

class MorphNavServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfig();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish config files
        $this->publishes([
           __DIR__ .'/config/morph-nav.php' => config_path('wk-morph-nav.php'),
        ], 'config');

        // Publish migration files
        $from = __DIR__ .'/database/migrations/';
        $to   = database_path('migrations') .'/';
        $this->publishes([
            $from .'create_wk_morph_nav_table.php'
                => $to .date('Y_m_d_His', time()) .'_create_wk_morph_nav_table.php',
        ], 'migrations');

        $this->loadTranslationsFrom(__DIR__.'/translations', 'php-morph-nav-int');
        $this->publishes([
            __DIR__.'/translations' => resource_path('lang/vendor/php-morph-nav-int'),
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                config('wk-morph-nav.command.cleaner')
            ]);
        }

        config('wk-core.class.morph-nav.nav')::observe(config('wk-core.class.morph-nav.navObserver'));
        config('wk-core.class.morph-nav.navLang')::observe(config('wk-core.class.morph-nav.navLangObserver'));
    }

    /**
     * Merges user's and package's configs.
     *
     * @return void
     */
    private function mergeConfig()
    {
        if (!config()->has('wk-morph-nav')) {
            $this->mergeConfigFrom(
                __DIR__ .'/config/morph-nav.php', 'wk-morph-nav'
            );
        }

        $this->mergeConfigFrom(
            __DIR__ .'/config/morph-nav.php', 'morph-nav'
        );
    }

    /**
     * Merge the given configuration with the existing configuration.
     *
     * @param String  $path
     * @param String  $key
     * @return void
     */
    protected function mergeConfigFrom($path, $key)
    {
        if (
            !(
                $this->app instanceof CachesConfiguration
                && $this->app->configurationIsCached()
            )
        ) {
            $config = $this->app->make('config');
            $content = $config->get($key, []);

            $config->set($key, array_merge(
                require $path, $content
            ));
        }
    }
}
