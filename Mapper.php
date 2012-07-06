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
     *
     * @var array
     */
    public $routes = array();

    /**
     * Flag to enable sort routes or not, false by default
     *
     * @var boolean
     */
    public $sortRoutesEnabled = false;

    public function __construct()
    {
    }

    /**
     * Enable sort routes before matching
     *
     * @param  bool   $value boolean value to enable or not sort routes
     */
    public function enableSortRoutes(bool $value)
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
     * Match
     * @param  string $url    url string to try map with a availables routes
     * @return array  $params if the url was matched all routed params will be returned
     *                        if it doesn't match a ResourceNotFoundException will thrown
     */
    public function match($url)
    {
        if ($this->sortRoutesEnabled) {
            $this->sortRoutes();
        }
        foreach ($this->routes as $name => $route) {
            if (($params = $route->match($url)) !== false) {
                return $params;
            }
        }

        throw new Exception\ResourceNotFoundException($url);
    }

    /**
     * This method sorts all raoutes connected to the mapper
     */
    protected function sortRoutes()
    {
        //TODO store the first preordering set in cache, it need to order just one time
        foreach ($this->routes as $i => $item) {
            $this->routes[$i]['patCount'] = count(explode('/', $item['route']->getPattern()));
            $this->routes[$i]['reqCount'] = count($item['route']->getRequirements());
            $this->routes[$i]['varCount'] = count($item['route']->getVars());
            $this->routes[$i]['pop'] = trim(array_pop(explode('/', $item['route']->getPattern())));
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
}

