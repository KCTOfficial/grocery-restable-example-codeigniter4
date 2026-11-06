<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// Demo: the home page opens the Grocery Restable admin GUI (like the native demo).
$routes->addRedirect('/', 'grocery-restable');
