<?php
/*
 * This file is part of the phpalchemy package.
 *
 * (c) Erik Amaru Ortiz <aortiz.erik@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Component\Routing\Exception;

/**
 * Class ResourceNotFoundException
 *
 * This exception is thrown when a requested resource not found
 *
 * @version   1.0
 * @author    Erik Amaru Ortiz <aortiz.erik@gmail.com>
 * @link      https://github.com/eriknyk/phpalchemy
 * @copyright Copyright 2012 Erik Amaru Ortiz
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 * @package   Alchemy/Component/Routing
 */
class ResourceNotFoundException extends \Exception
{
    public $url;

    /**
     * Constructor.
     * @param string $url url of request
     */
    public function __construct($url)
    {
        $this->url = $url;
        parent::__construct(sprintf('Resource "%s" Not Found!', $url));
    }
}