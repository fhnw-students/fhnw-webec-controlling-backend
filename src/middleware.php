<?php

//// Application middleware
$corsOptions = array(
  'origin' => '*',
  'exposeHeaders' => array('Access-Control-Allow-Headers', 'Authorization', 'Origin', 'X-Requested-With', 'Content-Type', 'Accept'),
  'maxAge' => 1728000,
  'allowCredentials' => True,
  'allowMethods' => array('GET', 'POST', 'OPTIONS', 'DELETE', 'PUT'),
  'allowHeaders' => array('Access-Control-Allow-Headers', 'Authorization', 'Origin', 'X-Requested-With', 'Content-Type', 'Accept')
);
$cors = new \CorsSlim\CorsSlim($corsOptions);
$app->add($cors);