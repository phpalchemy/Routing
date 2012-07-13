README
=========================
[![Build Status](https://secure.travis-ci.org/eriknyk/Routing.png?branch=master)](http://travis-ci.org/eriknyk/Routing)

Faster & simple Http Routing
------------

This is a Simple but faster URL Http routing library

Sample Use:

    use Alchemy\Component\Routing\Mapper;
    use Alchemy\Component\Routing\Route;

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

    $mapper->connect(
        'complete_route',
        new Route(
            '/leap_year/{year}',
            array(
                '_controller' => 'Validator',
                '_action' => 'leapYear'
            ),
            array(
                'year' => '\d+',
                '_method' => array('GET', 'POST')
            )
        )
    );

    print_r($mapper->match('/home/sample'));
    Array
    (
        [_controller] => home,
        [_action] => sample
    )

    print_r($mapper->match('/leap_year/2012'));
    Array
    (
        [_controller] => Validator,
        [_action] => leapYear,
        [year] => 2012,
    )