<?php
use Alchemy\Component\Routing\Mapper;
use Alchemy\Component\Routing\Route;
use Alchemy\Component\Routing\Exception\ResourceNotFoundException;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-05-30 at 13:05:03.
 */
class MapperTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Mapper
     */
    protected $mapper;
    public static $rootDir = "";
    public static $cachemtime = "";
    public static $counter = 0;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass()
    {
        self::$rootDir = realpath(__DIR__ . "/../");

        if (is_dir(self::$rootDir . "/vendor")) {
            require_once self::$rootDir . "/vendor/autoload.php";
        }
    }

    /**
     * @covers Mapper::connect
     */
    public function testConnect()
    {
        $mapper = new Mapper();
        $mapper->connect(
            'home_route',
            new Route(
                '/',
                array(
                    '_controller' => 'sample',
                    '_action' => 'index'
                )
            )
        );

        $mapper->connect(
            'to_index_route',
            new Route(
                '/{_controller}',
                array('_action' => 'index')
            )
        );

        $mapper->connect(
            'complete_route',
            new Route(
                '/{_controller}/{_action}'
            )
        );

        $this->assertCount(3, $mapper->getRoutes());

        return $mapper;
    }

    /**
     * @covers Mapper::match
     * @depends testConnect
     * @dataProvider provider1
     */
    public function testMatch($uri, $expected, Mapper $mapper)
    {
        $result = $mapper->match($uri);
        $this->assertEquals($expected, $result);
    }

    /**
     * @expectedException Alchemy\Component\Routing\Exception\ResourceNotFoundException
     * @depends testConnect
     */
    public function testException(Mapper $mapper)
    {
        $url = '/my_controller/my_action/var1/val1';
        $result = $mapper->match($url);
    }

    /**
     * Test loading routes from yaml file
     * @dataProvider provider1
     */
    public function testCachedSoure($uri, $expected)
    {
        $mapper = new Mapper(new \Alchemy\Component\Yaml\Yaml());
        $mapper->loadFrom(self::$rootDir . "/Tests/fixtures/routes.yaml");

        $result = $mapper->match($uri);
        $this->assertEquals($expected, $result);
    }

    public function testPrepareCachedEnv()
    {
        // this routine is only executed once
        self::$counter++;

        $cacheDir = sys_get_temp_dir();
        if (file_exists($cacheDir . "/routes.yaml.cache")) {
            unlink($cacheDir . "/routes.yaml.cache");
        }

        $mapper = new Mapper(new \Alchemy\Component\Yaml\Yaml());
        $mapper->setCacheDir($cacheDir);
        $mapper->loadFrom(self::$rootDir . "/Tests/fixtures/routes.yaml");

        self::$cachemtime = filemtime($cacheDir . "/routes.yaml.cache");
        $this->assertTrue(file_exists($cacheDir . "/routes.yaml.cache"));

        return null;
    }

    /**
     * Test routes cache for yaml file
     * @depends testPrepareCachedEnv
     * @dataProvider provider1
     */
    public function testCachedFile($uri, $expected)
    {
        // ensure that testPrepareCachedEnv() was called once
        $this->assertEquals(1, self::$counter);

        $cacheDir = sys_get_temp_dir();

        // second loading
        $mapper = new Mapper(new \Alchemy\Component\Yaml\Yaml());

        $this->assertTrue(file_exists($cacheDir . "/routes.yaml.cache"));

        $mapper->setCacheDir($cacheDir);
        $mapper->loadFrom(self::$rootDir . "/Tests/fixtures/routes.yaml");

        $result = $mapper->match($uri);
        $this->assertEquals($expected, $result);

        // this file should be the same at was created in testPrepareCachedEnv()
        // and it shouldn't be created each time
        $this->assertEquals(self::$cachemtime, filemtime($cacheDir . "/routes.yaml.cache"));
    }

    /**
     * Test routes
     */
    public function testMapping()
    {
        $mapper = new Mapper(new \Alchemy\Component\Yaml\Yaml());
        $mapper->loadFrom(self::$rootDir . "/Tests/fixtures/routes2.yaml");
        $result = $mapper->match('/login/auth');
        $expected = array(
            "params" => array(
                '_controller'=>'login',
                '_action'=>'auth'
            ),
            "mapped" => array(
                "_controller" => 'Sandbox\Controller\loginController',
                "_action" => 'authAction'
            )
        );

        $this->assertEquals($expected, $result);
    }

    /**
     * Test routes
     */
    public function testMapping2()
    {
        $mapper = new Mapper(new \Alchemy\Component\Yaml\Yaml());
        $mapper->loadFrom(self::$rootDir . "/Tests/fixtures/routes3.yaml");
        $result = $mapper->match('/user_role/assign');
        $expected = array(
            "params" => array(
                '_controller'=>'user_role',
                '_action'=>'assign'
            ),
            "mapped" => array(
                "_controller" => 'Sandbox\Controller\UserRoleController',
                "_action" => 'assignAction'
            )
        );
        $this->assertEquals($expected, $result);

        $result = $mapper->match('/tool/leap_year/1998');

        $expected = array(
            "_controller" => 'SomeOther\Module\UserTools',
            "_action" => 'leapYear',
            "year" => 1998
        );
        $this->assertEquals($expected, $result);

//        $result = $mapper->match('/admin/setup/group/profile');
//        $expected = array(
//            "_controller" => 'Sandbox\Utils\groupTest',
//            "_action" => 'do_update_profile',
//            "year" => 1998
//        );
//        $this->assertEquals($expected, $result);

    }

    function provider1()
    {
        return array(
            array(
                "/",
                array(
                    '_controller' => 'sample',
                    '_action' => 'index'
                )
            ),
            array(
                "/my_controller",
                array(
                    '_controller' => 'my_controller',
                    '_action' => 'index'
                )
            ),
            array(
                "/my_controller/my_action",
                array(
                    '_controller' => 'my_controller',
                    '_action' => 'my_action'
                )
            )
        );
    }
}

