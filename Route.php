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
 * Class Route
 *
 * This class match http url strings for a pattern given
 *
 * @version   1.0
 * @author    Erik Amaru Ortiz <aortiz.erik@gmail.com>
 * @link      https://github.com/eriknyk/phpalchemy
 * @copyright Copyright 2012 Erik Amaru Ortiz
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @package   Alchemy/Component/Routing
 */
class Route
{
    protected $pattern = '';
    protected $realPattern = '';
    protected $vars = array();
    protected $defaults = array();
    protected $requirements = array();
    protected $urlString = '';

    protected $type = '';
    protected $resourcePath = '';

    public $parameters = array();

    /**
     * Request object to try match withh the request information
     * @var Alchemy\Component\Http\Request
     */
    protected $request;

    public function __construct(
        $pattern = '',
        array $defaults = array(),
        array $requirements = array(),
        $type = '',
        $resourcePath = ''
    ) {
        defined('DS') || define('DS', DIRECTORY_SEPARATOR);

        $this->setPattern($pattern);
        $this->setDefaults($defaults);
        $this->setRequirements($requirements);

        $this->type = $type;
        $this->parameters  = array();
        $this->resourcePath = $resourcePath;

        $this->prepare();
    }

    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
    }

    public function getPattern()
    {
        return $this->pattern;
    }

    public function setDefaults($defaults)
    {
        $this->defaults = $defaults;
    }

    public function getDefaults()
    {
        return $this->defaults;
    }

    public function setRequirements($requirements)
    {
        if (array_key_exists('_method', $requirements)) {
            if (! is_array($requirements['_method']) && ! is_string($requirements['_method'])) {
                throw new \InvalidArgumentException(
                    "Invalid Argument Error: Param '_method' only accepts string or array definition."
                );
            }

            $methods = is_array($requirements['_method']) ? $requirements['_method'] : array($requirements['_method']);

            // convert all methods names to lowercase
            foreach ($methods as $i => $method) {
                $methods[$i] = strtolower($method);
            }

            $requirements['_method'] = $methods;
        }

        $this->requirements = $requirements;
    }

    public function getRequirements()
    {
        return $this->requirements;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getVars()
    {
        return $this->vars[0];
    }

    public function prepare()
    {
        $this->pattern = addcslashes($this->pattern, '.\/');
        preg_match_all('/\{([\w]+)\}/', $this->pattern, $this->vars);
        $patterns = $replacements = array();

        foreach ($this->vars[1] as $var) {
            $patterns[] = "/\{$var\}/";

            if (isset($this->requirements[$var])) {
                array_push($replacements, "({$this->requirements[$var]})");
            } else {
                array_push($replacements, '([\w\-]+)');
            }
        }

        $this->realPattern = preg_replace($patterns, $replacements, $this->pattern);
    }

    public function match($mixed)
    {
        $urlString = $mixed;
        $requestMethod = '';

        if ($urlString instanceof Request) {
            $urlString = $mixed->getPathInfo();
            $requestMethod = strtolower($mixed->getMethod());

            // HEAD and GET are equivalent as per RFC
            if ('head' === ($method = strtolower($this->context->getMethod()))) {
                $requestMethod = 'get';
            }
        }

        $this->urlString = urldecode($urlString);

        if (!preg_match('/^'.$this->realPattern.'$/', $this->urlString, $compiledMatches)) {
            return false;
        }

        // to verify _method requirement was defined $requestMethod ahould defined from Request object
        if (array_key_exists('_method', $this->requirements) && ! empty($requestMethod)) {
            // filter method that by requirement
            if (! in_array($requestMethod, $this->requirements['_method'])) {
                return false;
            }
        }

        if (!(isset($this->vars[1]) && count($compiledMatches) >= count($this->vars[1]))) {
            throw new \Exception("Error while matching parameters, url string given: '$urlString'");
        }

        $varValues = array_slice($compiledMatches, 1);

        foreach ($this->vars[1] as $i => $varName) {
            $this->parameters[$varName] = $varValues[$i];
            unset($varValues[$i]);
        }

        foreach ($varValues as $varValue) {
            if (substr($varValue, 0, 1) != '?') {
                $this->parameters[] = $varValue;
            }
        }

        $this->parameters = array_merge($this->defaults, $this->parameters);

        return $this->parameters;
    }
}

