<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/', fn() => response()->json([
    'service' => 'Course Service',
    'port'    => 3002,
    'status'  => 'running',
]));

$router->group(['prefix' => 'courses'], function () use ($router) {

    $router->get('/',  'CourseController@index');

    $router->post('/', 'CourseController@store');

    $router->get('/{id}/lessons', 'CourseController@lessons');

    $router->post('/{id}/enroll', 'CourseController@enroll');
});

$router->get('/progress/{userId}', 'CourseController@progress');

$router->post('/lessons/{id}/complete', 'CourseController@completeLesson');

$router->post('/internal/users', 'InternalController@syncUser');
