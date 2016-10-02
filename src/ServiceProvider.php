<?php
namespace Schnubertus\Astrometry;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__ . '/../config/astrometry.php';
        $this->mergeConfigFrom($configPath, 'astrometry');
    }

    public function boot()
    {
        $configPath = __DIR__ . '/../config/astrometry.php';
        $this->publishes([$configPath => config_path('astrometry.php')], 'config');

        $this->app['astrometry'] = $this->app->share(function ($app) {
            // Initialize HTTP-Client
            $httpClient = new HttpClient();

            // Gather configuration
            $loginUrl = $app['config']->get('astrometry.urls.login', 'http://nova.astrometry.net/api/login');
            $fileUpload = $app['config']->get('astrometry.urls.file', 'http://nova.astrometry.net/api/upload');
            $urlUpload = $app['config']->get('astrometry.urls.url', 'http://nova.astrometry.net/api/url_upload');
            $jobStatus = $app['config']->get('astrometry.urls.url', 'http://nova.astrometry.net/api/jobs');
            $apiKey = $app['config']->get('astrometry.api.key', '');

            // Initialize Astrometry-Client
            $astrometryClient = new AstrometryClient($httpClient, $loginUrl, $fileUpload, $urlUpload, $jobStatus, $apiKey);

            // Return Astrometry-Client
            return $astrometryClient;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['astrometry'];
    }
}

