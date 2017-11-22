<?php

namespace Naph\Searchlight;

use Naph\Searchlight\Drivers\Elasticsearch\ElasticsearchDriver;
use Naph\Searchlight\Drivers\Eloquent\EloquentDriver;

/**
 * Searchlight DriverManager
 * Heavily modeled after Laravel service provider managers
 */
class DriverManager
{
    /**
     * The application
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The array of created drivers
     *
     * @var \Naph\Searchlight\Driver[]
     */
    protected $drivers = [];

    /**
     * The array of provided custom drivers
     *
     * @var \Closure[]
     */
    protected $customDrivers = [];

    /**
     * DriverManager constructor.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Retrieve the search driver
     *
     * @param string|null $name
     *
     * @return \Naph\Searchlight\Driver
     */
    public function driver(?string $name = null): Driver
    {
        $name = $name ?: $this->getDefaultDriver();

        if ($name === 'eloquent') {
            //dd($this->resolve($name));
        }

        return isset($this->drivers[$name])
            ? $this->drivers[$name]
            : $this->drivers[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given search driver
     * May optionally provide a config array to override
     *
     * @param string $name
     *
     * @return \Naph\Searchlight\Driver
     */
    protected function resolve(string $name): Driver
    {
        $config = $this->getDriverConfig($name);

        if (is_null($config)) {
            throw new \InvalidArgumentException("Searchlight driver [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($name, $config);
        }

        $driverMethod = 'create' . ucfirst($config['driver']) . 'Driver';

        if (method_exists($this, $driverMethod)) {
            return $this->{$driverMethod}($name, $config);
        }

        throw new \InvalidArgumentException("Searchlight driver [{$name}] is not defined.");
    }

    /**
     * Create the Elasticsearch driver
     *
     * @param string $name
     * @param array $config
     *
     * @return \Naph\Searchlight\Driver
     */
    protected function createElasticsearchDriver(string $name, array $config): Driver
    {
        return $this->drivers[$name] = new ElasticsearchDriver($config['repositories'], $config);
    }

    /**
     * Create the Eloquent driver
     *
     * @param string $name
     * @param array $config
     *
     * @return \Naph\Searchlight\Driver
     */
    protected function createEloquentDriver(string $name, array $config): Driver
    {
        return $this->drivers[$name] = new EloquentDriver($config['repositories'], $config);
    }

    /**
     * The default driver from config
     *
     * @return string
     */
    protected function getDefaultDriver(): string
    {
        return $this->app['config']['searchlight.driver'];
    }

    /**
     * Instantiate a custom driver
     *
     * @param $name
     * @param $config
     *
     * @return \Naph\Searchlight\Driver
     */
    protected function callCustomCreator($name, $config): Driver
    {
        return $this->customDrivers[$config['driver']]($this->app, $name, $config);
    }

    /**
     * Get the config for given driver
     *
     * @param string $name
     *
     * @return array
     */
    private function getDriverConfig(string $name): array
    {
        $config = $this->app['config']["searchlight.drivers.{$name}"];

        return array_merge($this->getRootConfig(), $config, ['driver' => $name]);
    }

    /**
     * The configuration every driver has access to
     *
     * @return array
     */
    private function getRootConfig(): array
    {
        return array_only($this->app['config']['searchlight'], ['size', 'repositories']);
    }

    /**
     * Register a custom driver
     *
     * @param $name
     * @param \Closure $callback
     */
    public function provide($name, \Closure $callback)
    {
        $this->customDrivers[$name] = $callback;
    }
}
