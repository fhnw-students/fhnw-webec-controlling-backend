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
/**
 * Routes
 */
$app->get('/', sayHello);
$app->post('/login', login);
$app->get('/all/projects', getAllProjects);
$app->get('/projects[/{pid}]', getProjects);
$app->post('/projects', createProject);
//$app->put('/projects[/{pid}]', updateProject);
//$app->delete('/projects[/{pid}]', destroyProject);
$app->get('/projects/{pid}/worklogs', getWorklogs);

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
 * Builds a unauthorized 401 response
 * @param $response
 * @return mixed
 */
function unauthorized($response) {
  return $response->withStatus(401)->withHeader('Content-Type', 'text/html')->write('Unauthorized');
}

/**
 * @param $request
 * @return mixed
 */
function getJsonBody($request) {
  return json_decode($request->getBody(), true);
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
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $uri = BASE_URL . JIRA_ROUTE . '/project';
      $httpResponse = \Httpful\Request::get($uri)->authenticateWith($cred['username'], $cred['password'])->send();
      $response = buildResponseFromJira($response, $httpResponse);
    } else {
      $response = unauthorized($response);
    }
  } else {
    $response = badRequest($response);
  }
  return $response;
}

/**
 * @param $request
 * @param $response
 * @return mixed
 */
function getProjects($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      //Gets all jira projects of the user
      $httpResponse = getAllJiraProjects($args['pid'], $cred);
      $jiraProjects = $httpResponse->body;
      // Gets all stored projects in our database
      if ($args['pid']) {
        $projects = getProjectById($args['pid'], $user);
      } else {
        $projects = getProjectsFromUser($user);
      }
      $output = concatJiraAndOurProjects($jiraProjects, $projects, $args['pid']);
      return $response->withStatus($httpResponse->code)->withHeader('Content-Type', 'application/json')->withJson($output);
    } else {
      $response = unauthorized($response);
    }
  } else {
    $response = badRequest($response);
  }
  return $response;
}

/**
 * Concats the jira and our project information
 * @param $jiraProjects
 * @param $projects
 * @param $key
 * @return array
 */
function concatJiraAndOurProjects($jiraProjects, $projects, $key) {
  if ($key) {
    return array('config' => $projects, 'jira' => $jiraProjects);
  } else {
    $output = array();
    for ($i = 0; $i < count($projects); $i++) {
      $jiraProject = null;
      for ($j = 0; $j < count($jiraProjects); $j++) {
        if ($projects[$i]['pid'] == $jiraProjects[$j]->key) {
          $jiraProject = $jiraProjects[$j];
        }
      }
      array_push($output, array('config' => $projects[$i], 'jira' => $jiraProject));
    }
  }
  return $output;
}

/**
 * @param $key
 * @param $cred
 * @return \Httpful\Response
 */
function getAllJiraProjects($key, $cred) {
  //Gets all jira projects of the user
  $uri = BASE_URL . JIRA_ROUTE . '/project';
  if ($key) {
    $uri = $uri . '/' . $key;
  }
  $httpResponse = \Httpful\Request::get($uri)->authenticateWith($cred['username'], $cred['password'])->send();
  return $httpResponse;
}

/**
 * @param $key
 * @param $user
 * @return mixed
 */
function getProjectById($key, $user) {
  $db = getDBConnection();
  $selection = $db->prepare('SELECT * FROM projects WHERE uid = ? AND pid = ?');
  $selection->execute(array($user['uid'], $key));
  $project = $selection->fetchAll(PDO::FETCH_ASSOC);
  $db = null;
  return $project[0];
}

/*
 * @param $user
 * @return mixed
 */
function getProjectsFromUser($user) {
  $db = getDBConnection();
  $selection = $db->prepare('SELECT * FROM projects WHERE uid = ?');
  $selection->execute(array($user['uid']));
  $projects = $selection->fetchAll(PDO::FETCH_ASSOC);
  $db = null;
  return $projects;
}

/**
 * @param $request
 * @param $response
 * @return mixed
 */
function createProject($request, $response) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    $data = getJsonBody($request);
    if ($user) {
      $jiraHttpResponse = getAllJiraProjects($data['pid'], $cred);
      if ($jiraHttpResponse->code == 200) {
        $jiraProject = $jiraHttpResponse->body;
        // Create project in the database
        $db = getDBConnection();
        $insert = $db->prepare('INSERT INTO projects (uid, pid, name, weekload, maxhours, rangestart, rangeend, description) VALUES (:uid, :pid, :name, :weekload, :maxhours, :rangestart, :rangeend, :description)');
        $insert->bindParam(':uid', $user['uid']);
        $insert->bindParam(':pid', $data['pid']);
        $insert->bindParam(':name', $data['name']);
        $insert->bindParam(':weekload', $data['weekload']);
        $insert->bindParam(':maxhours', $data['maxhours']);
        $insert->bindParam(':rangestart', $data['rangestart']);
        $insert->bindParam(':rangeend', $data['rangeend']);
        $insert->bindParam(':description', $data['description']);
        $db->beginTransaction();
        $success = $insert->execute();
        // Creation was successful
        if ($success) {
          $db->commit();
          $db = null;
          $project = getProjectById($data['pid'], $user);
          $output = concatJiraAndOurProjects($jiraProject, $project, $data['pid']);
          return $response->withStatus(201)->withHeader('Content-Type', 'application/json')->withJson($output);
        } else {
          $db->rollBack();
          $db = null;
          return badRequest($response);
        }
      } else {
        return buildResponseFromJira($response, $jiraHttpResponse);
      }
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 * @param $request
 * @param $response
 * @param $args
 * @return mixed
 */
function updateProject($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    $data = getJsonBody($request);
    if ($user) {
      $jiraHttpResponse = getAllJiraProjects($args['pid'], $cred);
      if ($jiraHttpResponse->code == 200) {
        // TODO update project in our database and return the jira project and our information like in the GET request (old code is at the bottom)


      } else {
        return buildResponseFromJira($response, $jiraHttpResponse);
      }
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 * @param $request
 * @param $response
 * @param $args
 * @return mixed
 */
function destroyProject($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $jiraHttpResponse = getAllJiraProjects($args['pid'], $cred);
      if ($jiraHttpResponse->code == 200) {
        // TODO delete project in our database (old code is at the bottom)

      } else {
        return buildResponseFromJira($response, $jiraHttpResponse);
      }
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 * @param $request
 * @param $response
 * @param $args
 * @return mixed
 */
function getWorklogs($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $params = $request->getQueryParams();
      if ($params['dateFrom'] && $params['dateTo']) {
        $uri = BASE_URL . TEMPO_ROUTE . '?projectKey=' . $args['pid'] . '&dateFrom=' . $params['dateFrom'] . '&dateTo=' . $params['dateTo'];
        $httpResponse = \Httpful\Request::get($uri)->authenticateWith($cred['username'], $cred['password'])->send();
        return buildResponseFromJira($response, $httpResponse);
      } else {
        return badRequest($response);
      }
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////
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
