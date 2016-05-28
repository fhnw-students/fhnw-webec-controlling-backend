<?php

/**
 * This is the default jira backend uri
 */
define('BASE_URL', 'https://www.cs.technik.fhnw.ch/jira/rest');
/**
 * Route to the main jira api
 */
define('JIRA_ROUTE', '/api/2');
/**
 * Route to the jira plugin tempo
 */
define('TEMPO_ROUTE', '/tempo-timesheets/3/worklogs');

// Routes
$app->get('/', sayHello);
$app->post('/login', login);

$app->get('/all/projects', getAllProjects);
//$app->get('/projects[/{pid}]', getProjects);

//$app->get('/user/{uid}/projects[/{pid}]', getProjectByUser);
//$app->get('/user/{uid}/projects[/{pid}]', getProjectByUser);
//$app->post('/user/{uid}/projects', createProject);
//$app->put('/user/{uid}/projects', updateProject);
//$app->delete('/user/{uid}/projects/{pid}', deleteProject);

///////////////////////////////////////////////////////////////////////////////////////////
/**
 * open database connection to the MySQL server
 */
function getDBConnection() {
  $user = 'wtecch_fhnwWebec';
  $pwd = 'bUa&2QaU&5fa!2D';
  $connectionString = "mysql:host=194.126.200.46;dbname=wtecch_fhnwWebecJira";
  try {
    return new PDO($connectionString, $user, $pwd);
  } catch (Exception $e) {
    exit ($e->getMessage());
  }
}

/**
 * Builds a bad request 400 response
 * @param $response
 * @return mixed
 */
function badRequest($response) {
  return $response->withStatus(400)->withHeader('Content-Type', 'text/html')->write('Bad Request');
}

/**
 * Builds the response with the output form the jira call
 * @param $response
 * @param $httpResponse
 * @return mixed
 */
function buildResponseFromJira($response, $httpResponse) {
  return $response->withStatus($httpResponse->code)->withHeader('Content-Type', 'application/json')->withJson($httpResponse->body);
}

/**
 * Gets the username and the password from the access token and returns them.
 * @param $request
 * @return ArrayObject
 */
function decodeUserCredentials($request) {
  $token = substr($request->getHeaderLine('Authorization'), 6);
  list($username, $password) = explode(':', base64_decode($token));
  return array("username" => $username, "password" => $password);
}

/**
 * Demonstrates a login to the jira backend and returns the user. if it is the first
 * login than we store the user in our db too.
 * @param $request
 * @param $response
 * @return mixed
 */
function login($request, $response) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $uri = BASE_URL . JIRA_ROUTE . '/myself';
    $httpResponse = \Httpful\Request::get($uri)->authenticateWith($cred['username'], $cred['password'])->send();
    if ($httpResponse->code == 200) {
      $user = getUserByEmail($cred['username']);
      if (!$user) {
        createUser($cred['username']);
      }
    }
    $response = buildResponseFromJira($response, $httpResponse);
  } else {
    $response = badRequest($response);
  }
  return $response;
}

/**
 * Finds the user with the given username in our database
 * @param $username
 * @return mixed
 */
function getUserByEmail($username) {
  $db = getDBConnection();
  $selection = $db->prepare('SELECT * FROM users WHERE email = ?');
  $selection->execute(array($username));
  $db = null;
  return $selection->fetch(PDO::FETCH_ASSOC);
}

/**
 * Creates a new user in our database
 * @param $username
 */
function createUser($username) {
  $db = getDBConnection();
  $insert = $db->prepare('INSERT INTO users (email) VALUES (:email)');
  $insert->bindParam(':email', $username);

  $db->beginTransaction();

  $success = $insert->execute();
  if ($success) {
    $db->commit();
  } else {
    $db->rollBack();
  }

  $db = null;
}

/**
 * Test call to see if the backend is there :-)
 */
function sayHello() {
  echo 'Welcome to our API';
}

/**
 * @param $request
 * @param $response
 * @return mixed
 */
function getAllProjects($request, $response) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $uri = BASE_URL . JIRA_ROUTE . '/project';
    $httpResponse = \Httpful\Request::get($uri)->authenticateWith($cred['username'], $cred['password'])->send();
    $response = buildResponseFromJira($response, $httpResponse);
  } else {
    $response = badRequest($response);
  }
  return $response;
}

//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
/**
 * get details of one or all projects from an user
 *
 * @param $request
 * @param $response
 * @param $args
 *
 * @returns {json}
 */
//function getProjectByUser($request, $response, $args) {
//  $db = getDBConnection();
//  $selection = $db->prepare('SELECT * FROM users WHERE uid = ?');
//  $selection->execute(array($args['uid']));
//  $user = $selection->fetch(PDO::FETCH_ASSOC);
//
//  if ($user != null) {
//    if ($args['pid'] == null) {
//      $selection = $db->prepare('SELECT * FROM projects WHERE uid = ?');
//      $selection->execute(array($user['uid']));
//      $projects = $selection->fetchAll(PDO::FETCH_ASSOC);
//
//      $response = $response->withHeader('Content-Type', 'application/json');
//      $response = $response->withJson($projects);
//    } else {
//      $selection = $db->prepare('SELECT * FROM projects WHERE uid = ? AND pid = ?');
//      $selection->execute(array($user['uid'], $args['pid']));
//      $project = $selection->fetch(PDO::FETCH_ASSOC);
//
//      $response = $response->withHeader('Content-Type', 'application/json');
//      $response = $response->withJson($project);
//    }
//  } else {
//    $response = $response->withStatus(404)->withHeader('Content-Type', 'text/html')->write('Page not found');
//  }
//
//  $db = null;
//
//  return $response;
//}

/**
 * insert a new project for an user
 *
 * @param $request
 * @param $response
 * @param $args
 */
//function createProject($request, $response, $args) {
//  $json = $request->getBody();
//  $data = json_decode($json, true);
//
//  $db = getDBConnection();
//  $insert = $db->prepare('INSERT INTO projects (uid, pid, name, weekload, maxhours, rangestart, rangeend, description) VALUES (:uid, :pid, :name, :weekload, :maxhours, :rangestart, :rangeend, :description)');
//  $insert->bindParam(':uid', $args['uid']);
//  $insert->bindParam(':pid', $data['pid']);
//  $insert->bindParam(':name', $data['name']);
//  $insert->bindParam(':weekload', $data['weekload']);
//  $insert->bindParam(':maxhours', $data['maxhours']);
//  $insert->bindParam(':rangestart', $data['rangestart']);
//  $insert->bindParam(':rangeend', $data['rangeend']);
//  $insert->bindParam(':description', $data['description']);
//
//  $db->beginTransaction();
//
//  $success = $insert->execute();
//  if ($success) {
//    $db->commit();
//  } else {
//    $db->rollBack();
//  }
//
//  $db = null;
//}

/**
 * update information of a project
 *
 * @param $request
 * @param $response
 * @param $args
 */
//function updateProject($request, $response, $args) {
//  $json = $request->getBody();
//  $data = json_decode($json, true);
//
//  $db = getDBConnection();
//  $update = $db->prepare('UPDATE projects SET pid=?, name=?, weekload=?, maxhours=?, rangestart=?, rangeend=?, description=? WHERE uid=? AND pid=?');
//  $update->bindParam(':uid', $args['uid']);
//  $update->bindParam(':pid', $data['pid']);
//
//  $db->beginTransaction();
//
//  $success = $update->execute(array($data['pid'], $data['name'], $data['weekload'], $data['maxhours'], $data['rangestart'], $data['rangeend'], $data['description'], $args['uid'], $data['pid']));
//  if ($success) {
//    $db->commit();
//  } else {
//    $db->rollBack();
//  }
//
//  $db = null;
//}

/**
 * delete a project from an user
 *
 * @param $request
 * @param $response
 * @param $args
 */
//function deleteProject($request, $response, $args) {
//  $db = getDBConnection();
//  $delete = $db->prepare('DELETE FROM projects WHERE uid=? AND pid=?');
//  $db->beginTransaction();
//  $success = $delete->execute(array($args['uid'], $args['pid']));
//  if ($success) {
//    $db->commit();
//  } else {
//    $db->rollBack();
//  }
//
//  $db = null;
//}
