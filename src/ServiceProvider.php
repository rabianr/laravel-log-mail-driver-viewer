<?php

namespace Rabianr\LogMailViewer;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->configPath(), 'logmailviewer');
    }

    /**
     * Register the config for publishing
     *
     * @param  Router  $router
     */
    public function boot(Router $router)
    {
        $this->publishes([$this->configPath() => config_path('logmailviewer.php')], 'logmailviewer');

        if (config('app.debug') == false) {
            return;
        }

        $this->app->make('config')->set('logging.channels.logmailviewer', [
            'driver' => 'daily',
            'tap' => [ LogLineFormatter::class ],
            'path' => storage_path('logs/logmailviewer.log'),
            'level' => 'debug',
            'days' => 28,
        ]);

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'logmailviewer');
        $this->loadJSONTranslationsFrom(__DIR__ . '/../lang', 'logmailviewer');
    }

    /**
     * Get the config path
     *
     * @return string
     */
    protected function configPath()
    {
        return __DIR__ . '/../config/logmailviewer.php';
    }
}
