<?php
/*
 * This file is part of the phpalchemy package.
 *
 * (c) Erik Amaru Ortiz <aortiz.erik@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Component\Routing;

use Alchemy\Component\Http\Request;

/**
 * Class Mapper
 *
 * This class enroutes your http requests and handle them
 *
 * @version   1.0
 * @author    Erik Amaru Ortiz <aortiz.erik@gmail.com>
 * @link      https://github.com/eriknyk/phpalchemy
 * @copyright Copyright 2012 Erik Amaru Ortiz
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @package   Alchemy/Component/Routing
 */
class Mapper
{
    /**
     * Contains routes collection
     * @var Route[]
     */
    public $routes = array();

    /**
     * Flag to enable sort routes or not, false by default
     * @var boolean
     */
    public $sortRoutesEnabled = false;

    /**
     * @var array
     */
    protected $mapping = array();

    /**
     * @var \Alchemy\Component\Yaml\Yaml|null
     */
    protected $yaml = null;

    /**
     * @var string
     */
    protected $cacheDir = "";
    /**
     * @var string
     */
    protected $cacheContent = "";

    /**
     * @param \Alchemy\Component\Yaml\Yaml|null $yaml
     */
    public function __construct(\Alchemy\Component\Yaml\Yaml $yaml = null)
    {
        if (! is_null($yaml)) {
            $this->yaml = $yaml;
        }
    }

    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = rtrim($cacheDir, DIRECTORY_SEPARATOR);
    }

    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    public function loadFrom($file)
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        switch ($extension) {
            case "yaml":
            case "yml";
                $filename = basename($file);

                if (! empty($this->cacheDir) && $this->isCached($filename)) { // try load from cache
                    $data = $this->getCached();
                } else {
                    if (! is_object($this->yaml)) {
                        throw new \Exception("Yaml Parser library is not loaded");
                    }

                    $data = $this->yaml->load($file);

                    if (! empty($this->cacheDir)) {
                        $this->saveInCache($file, $data);
                    }
                }

                $routesList = $data["routes"];

                if (isset($data["mapping"])) {
                    $this->mapping = $data["mapping"];
                }

                foreach ($routesList as $routeData) {
                    // validate route data
                    if (! isset($routeData["pattern"])) {
                        throw new \Exception("Invalid route definition, param: \"pattern\" is required.");
                    }
                    if (! is_string($routeData["pattern"])) {
                        throw new \Exception("Invalid route definition, param: \"pattern\" must be a string.");
                    }
                    if (isset($routeData["defaults"]) && ! is_array($routeData["defaults"])) {
                        throw new \Exception("Invalid route definition, param: \"defaults\" must be an array.");
                    }
                    if (isset($routeData["requirements"]) && ! is_array($routeData["requirements"])) {
                        throw new \Exception("Invalid route definition, param: \"requirements\" must be an array.");
                    }

                    $route = new Route();
                    $route->setPattern($routeData["pattern"]);

                    if (array_key_exists("defaults", $routeData)) {
                        $route->setDefaults($routeData["defaults"]);
                    }
                    if (array_key_exists("requirements", $routeData)) {
                        $route->setRequirements($routeData["requirements"]);
                    }
                    if (array_key_exists("mapping", $routeData)) {
                        $route->setMapping($routeData["mapping"]);
                    } elseif (! empty($this->mapping)) {
                        $route->setMapping($this->mapping);
                    }

                    $this->connect($routeData["pattern"], $route);
                }
                break;
        }
    }

    /**
     * Enable sort routes before matching
     * @param  bool   $value boolean value to enable or not sort routes
     */
    public function enableSortRoutes($value)
    {
        $this->sortRoutesEnabled = $value;
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Connect a route to mapper
     *
     * @param  string $name  name for connecting route
     * @param  Route  $route route object to connect to mapper
     */
    public function connect($name, Route $route)
    {
        $this->routes[$name] = $route;
    }

    /**
     * Match a url string given as parameter or with Request info.
     *
     * @param $mixed
     * @throws \InvalidArgumentException
     * @throws Exception\ResourceNotFoundException
     * @internal param mixed $url it can be a url string or a Request object, it is used
     *                       to try map it with all available routes
     * @return array $params if the url was matched all routed params will be returned
     *                        if it does not match a ResourceNotFoundException will be thrown
     */
    public function match($mixed)
    {
        if (! ($mixed instanceof Request) && ! is_string($mixed)) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid Argument Error: Invalid type param, it can be a url string or a Request object, '%s' given.",
                gettype($mixed)
            ));
        }

        if ($this->sortRoutesEnabled) {
            $this->sortRoutes();
        }

        foreach ($this->routes as $name => $route) {
            if (($params = $route->match($mixed)) !== false) {
                return $params;
            }
        }

        $url = is_string($mixed) ? $mixed : $mixed->getPathInfo();
        throw new Exception\ResourceNotFoundException($url);
    }

    /**
     * This method sorts all routes connected to the mapper
     */
    protected function sortRoutes()
    {
        //TODO store the first preordering set in cache, it need to order just one time
        $routes = array();
        foreach ($this->routes as $i => $item) {
            $this->routes[$i]['patCount'] = count(explode('/', $item->getPattern()));
            $this->routes[$i]['reqCount'] = count($item->getRequirements());
            $this->routes[$i]['varCount'] = count($item->getVars());
            $this->routes[$i]['pop'] = trim(array_pop(explode('/', $item->getPattern())));
        }

        $list = $this->routes;
        // first, order by separator number '/'
        usort($list, function ($a, $b)
        {
            if ($b['patCount'] == $a['patCount']) {
                return 0;
            }

            return $b['patCount'] < $a['patCount'] ? 1 : -1;
        });

        $n = count($list);
        for ($i = 1; $i < $n; $i++) {
            $j= $i - 1;
            while (
                $j>=0 && $list[$j]['patCount'] == $list[$i]['patCount'] &&
                $list[$j]['varCount'] > $list[$i]['varCount']
            ) {
                $this->swapValues($list[$j+1],$list[$j]);
                $j--;
            }
        }

        for ($i = 1; $i < $n; $i++) {
            $j= $i - 1;
            while (
                $j>=0 && $list[$j]['patCount'] == $list[$i]['patCount'] &&
                $list[$j]['varCount'] >= $list[$i]['varCount'] && $list[$j]['reqCount'] < $list[$i]['reqCount']
            ) {
                $this->swapValues($list[$j+1],$list[$j]);
                $j--;
            }
        }

        $this->routes = $list;
    }

    /**
     * This method swap values from two params passed by reference
     * @param  string &$a value to swap
     * @param  string &$b value to swap
     */
    protected function swapValues(&$a, &$b)
    {
        $x = $a;
        $a = $b;
        $b = $x;
    }

    /**
     * Verify is a file is cached
     * @param $filename
     * @return bool
     */
    protected function isCached($filename)
    {
        if (! empty($this->cacheDir)) {
            return false;
        }

        $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR . $filename . ".cache";

        if (file_exists($cacheFile)
            && is_array($this->cacheContent = include($cacheFile))
            && $this->cacheContent["_chk"] == filemtime($cacheFile)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Get cached content
     * @return string
     */
    protected function getCached()
    {
        return $this->cacheContent;
    }

    /**
     * Save in cache
     * @param $filename
     * @param $content
     * @return bool
     */
    protected function saveInCache($filename, $content)
    {
        if (empty($this->cacheDir) || ! is_dir($this->cacheDir) || ! is_writable($this->cacheDir)) {
            return false;
        }

        $content["_chk"] = filemtime($filename);

        file_put_contents(
            $this->cacheDir . DIRECTORY_SEPARATOR . basename($filename) . ".cache",
            "<?php return " . var_export($content, true) . ";"
        );

        return true;
    }
}

