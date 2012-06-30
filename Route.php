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
    protected $pattern;
    protected $realPattern;
    protected $vars;
    protected $defaults;
    protected $requirements;
    protected $urlString;

    protected $type;
    protected $resourcePath;

    public $parameters = array();

    public function __construct($pattern = null, $defaults = null, $requirements = null, $type = null, $resourcePath = null)
    {
        if (!defined('DS')) {
            define('DS', DIRECTORY_SEPARATOR);
        }

        $this->setPattern($pattern ? $pattern : '');
        $this->setDefaults($defaults ? $defaults : Array());
        $this->setRequirements($requirements ? $requirements : Array());

        $this->type    = $type;
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

    public function match($urlString)
    {
        $this->urlString = urldecode($urlString);

        if (!preg_match('/^'.$this->realPattern.'$/', $this->urlString, $compiledMatches)) {
            return false;
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


