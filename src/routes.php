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
$app->get('/', sayHelloRoute);
$app->post('/auth/login', loginRoute);
$app->get('/all/projects', getAllProjectsRoute);
$app->get('/projects[/{pid}]', getProjectsRoute);
$app->post('/projects', createProjectRoute);
$app->put('/projects[/{pid}]', updateProjectRoute);
$app->delete('/projects[/{pid}]', destroyProjectRoute);
$app->get('/projects/{pid}/worklogs', getWorklogsRoute);
$app->get('/projects/{pid}/members', getProjectMemberRoute);
$app->get('/projects/{pid}/resources/graph', getProjectResourcesGraphRoute);
$app->get('/projects/{pid}/resources/table', getProjectResourcesTableRoute);
$app->get('/projects/{pid}/efficiency/graph', getProjectEfficiencyGraphRoute);
$app->get('/projects/{pid}/team/graph', getProjectTeamGraphRoute);

///////////////////////////////////////////////////////////////////////////////////////////
// Routes
///////////////////////////////////////////////////////////////////////////////////////////
/**
 * Test call to see if the backend is there :-)
 */
function sayHelloRoute() {
  echo 'Welcome to our API';
}

/**
 * Demonstrates a login to the jira backend and returns the user. if it is the first
 * login than we store the user in our db too.
 * @param $request
 * @param $response
 * @return mixed
 */
function loginRoute($request, $response) {
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
 * Get all projects from JIRA of logged in user
 * @param $request
 * @param $response
 * @return $response
 */
function getAllProjectsRoute($request, $response) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $httpResponse = requestJiraProjects($cred);
      $allProjects = $httpResponse->body;
      $projects = getProjectsFromUser($user);
      $result = [];
      for ($i=0; $i < count($allProjects); $i++) {
        $ok = true;
        for ($n=0; $n < count($projects); $n++) {
          if($allProjects[$i]->key == $projects[$n]['pid']){
            $ok = false;
          }
        }
        if($ok == true){
          array_push($result, $allProjects[$i]);
        }
      }
      return $response->withStatus($httpResponse->code)->withHeader('Content-Type', 'application/json')->withJson($result);
    } else {
      $response = unauthorized($response);
    }
  } else {
    $response = badRequest($response);
  }
  // return $response;
}

/**
 * Get one project from JIRA
 * @param $request
 * @param $response
 * @return $response
 */
function getProjectsRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      //Gets all jira projects of the user
      $httpResponse = requestJiraProjects($cred, $args['pid']);
      if ($httpResponse->code === 200) {
        // Gets all stored projects in our database
        if ($args['pid']) {
          $projects = getProjectById($args['pid'], $user);
          if (!$projects) {
            return notFound($response);
          }
        } else {
          $projects = getProjectsFromUser($user);
          for($i=0; $i < count($projects); $i++){
            $worklogHttpResponse = getWorklogs($projects[$i]['pid'], $projects[$i]['rangestart'], $projects[$i]['rangeend'], $cred);
            $worklogs = $worklogHttpResponse->body;
            $worklogs = parseWeeklogs($worklogs);
            $real = 0;
            for ($n = 0; $n < count($worklogs); $n++) {
              $real += $worklogs[$n]['timeSpentSeconds'];
            }
            $projects[$i]['timeSpent'] = $real / 3600;
          }
        }
        $output = concatJiraAndOurProjects($httpResponse->body, $projects, $args['pid']);
        return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withJson($output);
      } else {
        return buildResponseFromJira($response, $httpResponse);
      }
    } else {
      return unauthorized($response);
    } // user check
  } else {
    return badRequest($response);
  } // header check
  return $response;
}

/**
 * Create a new project (add to database)
 * @param $request
 * @param $response
 * @return $response
 */
function createProjectRoute($request, $response) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    $data = getJsonBody($request);
    if ($user) {
      $jiraHttpResponse = requestJiraProjects($cred, $data['pid']);
      if ($jiraHttpResponse->code == 200) {
        $jiraProject = $jiraHttpResponse->body;
        // Create project in the database
        $db = getDBConnection();
        $insert = $db->prepare('INSERT INTO projects (uid, pid, name, weekload, maxhours, rangestart, rangeend, teamSize, description) VALUES (:uid, :pid, :name, :weekload, :maxhours, :rangestart, :rangeend, :teamSize, :description)');
        $insert->bindParam(':uid', $user['uid']);
        $insert->bindParam(':pid', $data['pid']);
        $insert->bindParam(':name', $data['name']);
        $insert->bindParam(':weekload', $data['weekload']);
        $insert->bindParam(':maxhours', $data['maxhours']);
        $insert->bindParam(':rangestart', $data['rangestart']);
        $insert->bindParam(':rangeend', $data['rangeend']);
        $insert->bindParam(':teamSize', $data['teamSize']);
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
 * Update a project (update database)
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function updateProjectRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    $data = getJsonBody($request);
    if ($user) {
      $jiraHttpResponse = requestJiraProjects($cred, $args['pid']);
      if ($jiraHttpResponse->code == 200) {
        $db = getDBConnection();
        $update = $db->prepare('UPDATE projects SET pid=?, name=?, weekload=?, maxhours=?, rangestart=?, rangeend=?, teamSize=?, description=? WHERE uid=? AND pid=?');
        $db->beginTransaction();
        $success = $update->execute(array($args['pid'], $data['name'], $data['weekload'], $data['maxhours'], $data['rangestart'], $data['rangeend'], $data['teamSize'], $data['description'], $user['uid'], $args['pid']));
        if ($success) {
          $db->commit();
          $db = null;
          $output = concatJiraAndOurProjects($jiraHttpResponse->body, $data, true);
          return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withJson($output);
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
 * Delete a project (remove from database)
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function destroyProjectRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $jiraHttpResponse = requestJiraProjects($cred, $args['pid']);
      if ($jiraHttpResponse->code == 200) {
        $db = getDBConnection();
        $delete = $db->prepare('DELETE FROM projects WHERE uid=? AND pid=?');
        $db->beginTransaction();
        $success = $delete->execute(array($user['uid'], $args['pid']));
        if ($success) {
          $db->commit();
          $db = null;
          return $response->withStatus(204);
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
 * Get worklogs from JIRA
 * if date range is given, it returns all worklogs within this range
 * if date range is not given, it returns all worklogs of the date range defined in the project (database)
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function getWorklogsRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $params = $request->getQueryParams();
      $project = getProjectById($args['pid'], $user);
      $dateFrom = ($params['dateFrom']) ? $params['dateFrom'] : $project['rangestart'];
      $dateTo = ($params['dateTo']) ? $params['dateTo'] : $project['rangeend'];
      $httpResponse = getWorklogs($args['pid'], $dateFrom, $dateTo, $cred);
      return buildResponseFromJira($response, $httpResponse);
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 * Get all members of a project from JIRA
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function getProjectMemberRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $project = getProjectById($args['pid'], $user);
      $httpResponse = getWorklogs($args['pid'], $project['rangestart'], $project['rangeend'], $cred);
      $result = getProjectMembers($httpResponse->body);
      return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withJson($result);
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 * Get resources data to build a graph
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function getProjectResourcesGraphRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $project = getProjectById($args['pid'], $user);
      $httpResponse = getWorklogs($args['pid'], $project['rangestart'], $project['rangeend'], $cred);
      $worklogs = $httpResponse->body;
      $members = getProjectMembers($worklogs);
      // Gets labels
      $worklogs = parseWeeklogs($worklogs);
      $labels = getWeekLabel($worklogs);

      // builds datasets
      $datasets = array();
      foreach ($members as $k => $v) {
        $data = array();
        for ($w = 0; $w < count($labels); $w++) {
          $data[$w] = 0;
        }

        for ($i = 0; $i < count($worklogs); $i++) {
          $key = $worklogs[$i]['week'] . '/' . $worklogs[$i]['year'];
          $index = array_search($key, $labels);
          if ($worklogs[$i]['name'] == $v->name) {
            $data[$index] = $data[$index] + $worklogs[$i]['timeSpentSeconds'];
          }
        }

        for ($n = 0; $n < count($data); $n++) {
          if ($n > 0) {
            $data[$n] = $data[$n - 1] + $data[$n];
          }
        }

        for ($n = 0; $n < count($data); $n++) {
          $data[$n] = $data[$n] / 3600;
        }

        array_push($datasets, array('label' => $v->displayName, 'data' => $data));
      }

      $dataWeekLoad = array();
      $dataTarget = array();
      for ($n = 0; $n < count($labels); $n++) {
        $dataWeekLoad[$n] = intval($project['weekload']);
        if ($n > 0) {
          $dataWeekLoad[$n] = $dataWeekLoad[$n] + $dataWeekLoad[$n - 1];
        }
        $dataTarget[$n] = intval($project['maxhours']);
      }
      array_push($datasets, array('label' => 'Weekload', 'data' => $dataWeekLoad));
      array_push($datasets, array('label' => 'Target', 'data' => $dataTarget));

      $result = array('labels' => $labels, 'datasets' => $datasets);

      return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withJson($result);
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 * Get efficiency data to build a graph
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function getProjectEfficiencyGraphRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $project = getProjectById($args['pid'], $user);
      $httpResponse = getWorklogs($args['pid'], $project['rangestart'], $project['rangeend'], $cred);
      $worklogs = $httpResponse->body;
      $members = getProjectMembers($worklogs);
      // Gets labels
      $worklogs = parseWeeklogs($worklogs);
      $labels = getWeekLabel($worklogs);

      // builds datasets
      $datasets = array();
      foreach ($members as $k => $v) {
        $data = array();
        for ($w = 0; $w < count($labels); $w++) {
          $data[$w] = 0;
        }

        for ($i = 0; $i < count($worklogs); $i++) {
          $key = $worklogs[$i]['week'] . '/' . $worklogs[$i]['year'];
          $index = array_search($key, $labels);
          if ($worklogs[$i]['name'] == $v->name) {
            $data[$index] = $data[$index] + $worklogs[$i]['timeSpentSeconds'];
          }
        }

        for ($n = 0; $n < count($data); $n++) {
          $data[$n] = $data[$n] / 3600;
        }

        array_push($datasets, array('label' => $v->displayName, 'data' => $data));
      }

      $dataWeekPlaned = array();
      for ($n = 0; $n < count($labels); $n++) {
        $dataWeekPlaned[$n] = intval($project['weekload']);
      }
      array_push($datasets, array('label' => 'Weekload', 'data' => $dataWeekPlaned));

      $result = array('labels' => $labels, 'datasets' => $datasets);

      return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withJson($result);
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 * Get team data to build a graph
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function getProjectTeamGraphRoute($request, $response, $args) {
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $project = getProjectById($args['pid'], $user);
      $httpResponse = getWorklogs($args['pid'], $project['rangestart'], $project['rangeend'], $cred);
      $worklogs = $httpResponse->body;
      $worklogs = parseWeeklogs($worklogs);
      $labels = getWeekLabel($worklogs);
      $datasets = array();
      $dataPlaned = array();
      $dataReal = array();
      for ($w = 0; $w < count($labels); $w++) {
        $dataPlaned[$w] = intval($project['weekload']) * intval($project['teamSize']);
        if ($w > 0) {
          $dataPlaned[$w] = $dataPlaned[$w] + $dataPlaned[$w - 1];
        }
        $dataReal[$w] = 0;
      }

      for ($i = 0; $i < count($worklogs); $i++) {
        $key = $worklogs[$i]['week'] . '/' . $worklogs[$i]['year'];
        $index = array_search($key, $labels);
        $dataReal[$index] = $dataReal[$index] + $worklogs[$i]['timeSpentSeconds'];
      }

      for ($n = 1; $n < count($labels); $n++) {
        $dataReal[$n] = $dataReal[$n] + $dataReal[$n - 1];
      }

      for ($n = 0; $n < count($labels); $n++) {
        $dataReal[$n] = $dataReal[$n] / 3600;
        $dataReal[$n] = intval($dataReal[$n]);
      }

      array_push($datasets, array('label' => 'Planed', 'data' => $dataPlaned));
      array_push($datasets, array('label' => 'Real', 'data' => $dataReal));

      $result = array('labels' => $labels, 'datasets' => $datasets);

      return $response->withStatus(200)->withHeader('Content-Type', 'application/json')->withJson($result);
    } else {
      return unauthorized($response);
    }
  } else {
    return badRequest($response);
  }
}

/**
 *
 * @param $request
 * @param $response
 * @param $args
 * @return $response
 */
function getProjectResourcesTableRoute($request, $response, $args){
  if ($request->hasHeader('Authorization')) {
    $cred = decodeUserCredentials($request);
    $user = getUserByEmail($cred['username']);
    if ($user) {
      $project = getProjectById($args['pid'], $user);
      $httpResponse = getWorklogs($args['pid'], $project['rangestart'], $project['rangeend'], $cred);
      $worklogs = $httpResponse->body;
      $members = getProjectMembers($worklogs);
      // Gets labels
      $worklogs = parseWeeklogs($worklogs);
      $labels = getWeekLabel($worklogs);


      for($i = 0; $i < count($members); $i++) {
        $member = $members[$i];
        $member->hours = 0;

        //workload of a member
        for ($n = 0; $n < count($worklogs); $n++) {
          if ($worklogs[$n]['name'] === $member->name) {
            $member->hours += $worklogs[$n]['timeSpentSeconds'] / 3600;
          }
        }


        //difference to planned weekly workload
        $member->difference = 0;
        for ($w = 0; $w < count($labels); $w++) {
          $dataPlaned[$w] = (intval($project['weekload']) * intval($project['teamSize'])) / 4;
          if ($w > 0) {
            $dataPlaned[$w] = $dataPlaned[$w] + $dataPlaned[$w - 1];
          }
          $max = $dataPlaned[count($dataPlaned) - 1];
        }
        $member->difference = $member->hours - $max;

      }
        print_r($members);
        //print_r($max);


    }
  }

}

///////////////////////////////////////////////////////////////////////////////////////////
// Helpers
///////////////////////////////////////////////////////////////////////////////////////////
  /**
   * open database connection to the MySQL server
   */
  function getDBConnection()
  {
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
  function badRequest($response)
  {
    return $response->withStatus(400)->withHeader('Content-Type', 'text/html')->write('Bad Request');
  }

  /**
   * Builds a not found 404 response
   * @param $response
   * @return mixed
   */
  function notFound($response)
  {
    return $response->withStatus(404)->withHeader('Content-Type', 'text/html')->write('Not Found');
  }

  /**
   * Builds a unauthorized 401 response
   * @param $response
   * @return $response
   */
  function unauthorized($response)
  {
    return $response->withStatus(401)->withHeader('Content-Type', 'text/html')->write('Unauthorized');
  }

  /**
   * JSON: Decode body
   * @param $request
   * @return {Object} body
   */
  function getJsonBody($request)
  {
    return json_decode($request->getBody(), true);
  }

  /**
   * Builds the response with the output form the jira call
   * @param $response
   * @param $httpResponse
   * @return $response
   */
  function buildResponseFromJira($response, $httpResponse)
  {
    return $response->withStatus($httpResponse->code)->withHeader('Content-Type', 'application/json')->withJson($httpResponse->body);
  }

  /**
   * Gets the username and the password from the access token and returns them.
   * @param $request
   * @return ArrayObject
   */
  function decodeUserCredentials($request)
  {
    $token = substr($request->getHeaderLine('Authorization'), 6);
    list($username, $password) = explode(':', base64_decode($token));
    return array("username" => $username, "password" => $password);
  }

  /**
   * Finds the user with the given username in our database
   * @param $username
   * @return ArrayObject
   */
  function getUserByEmail($username)
  {
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
  function createUser($username)
  {
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
   * Concats the jira and our project information
   * @param $jiraProjects
   * @param $projects
   * @param $key
   * @return array
   */
  function concatJiraAndOurProjects($jiraProjects, $projects, $key)
  {
    if ($key) {
      $projects['jira'] = $jiraProjects;
      return $projects;
    } else {
      for ($i = 0; $i < count($projects); $i++) {
        $jiraProject = null;
        for ($j = 0; $j < count($jiraProjects); $j++) {
          if ($projects[$i]['pid'] == $jiraProjects[$j]->key) {
            $jiraProject = $jiraProjects[$j];
          }
        }
        $projects[$i]['jira'] = $jiraProject;
      }
    }
    return $projects;
  }

  /**
   * HTTP Request to get all JIRA projects of a user
   * @param $key
   * @param $cred
   * @return \Httpful\Response
   */
  function requestJiraProjects($cred, $key)
  {
    //Gets all jira projects of the user
    $uri = BASE_URL . JIRA_ROUTE . '/project';
    if ($key) {
      $uri = $uri . '/' . $key;
    }
    $httpResponse = \Httpful\Request::get($uri)->authenticateWith($cred['username'], $cred['password'])->send();
    return $httpResponse;
  }

  /**
   * Get one project from database of a user
   * @param $key
   * @param $user
   * @return ArrayObject
   */
  function getProjectById($key, $user)
  {
    $db = getDBConnection();
    $selection = $db->prepare('SELECT * FROM projects WHERE uid = ? AND pid = ?');
    $selection->execute(array($user['uid'], $key));
    $project = $selection->fetch(PDO::FETCH_ASSOC);
    $db = null;
    return $project;
  }

  /**
   * Get all projects from database of a user
   * @param $user
   * @return ArrayObject
   */
  function getProjectsFromUser($user)
  {
    $db = getDBConnection();
    $selection = $db->prepare('SELECT * FROM projects WHERE uid = ?');
    $selection->execute(array($user['uid']));
    $projects = $selection->fetchAll(PDO::FETCH_ASSOC);
    $db = null;
    return $projects;
  }

  /**
   * HTTP Request to get worklogs from JIRA
   * @param $key
   * @param $dateFrom
   * @param $dateTo
   * @return \Httpful\Response
   */
  function getWorklogs($key, $dateFrom, $dateTo, $cred)
  {
    $uri = BASE_URL . TEMPO_ROUTE . '?projectKey=' . $key . '&dateFrom=' . $dateFrom . '&dateTo=' . $dateTo;
    return \Httpful\Request::get($uri)->authenticateWith($cred['username'], $cred['password'])->send();
  }

  /**
   * Get all members of a project
   * @param $worklogs
   * @return array
   */
  function getProjectMembers($worklogs)
  {
    $mapFunction = function ($item) {
      return $item->author;
    };
    $authors = array_map($mapFunction, $worklogs);
    $hasMembers = array();
    $result = array();
    for ($i = 0; $i < count($authors); $i++) {
      if ($hasMembers[$authors[$i]->name] !== true) {
        $hasMembers[$authors[$i]->name] = true;
        array_push($result, $authors[$i]);
      }
    }
    return $result;
  }

/**
 * Parse Weeklogs into an array
 * @param $worklogs
 * @return array
 */
  function parseWeeklogs($worklogs)
  {
    $mapFunctionWorklog = function ($log) {
      $date = new DateTime($log->dateStarted);
      return array('id' => $log->id, 'timeSpentSeconds' => $log->timeSpentSeconds, 'dateStarted' => $log->dateStarted, 'displayName' => $log->author->displayName, 'name' => $log->author->name, 'year' => $date->format('Y'), 'week' => $date->format('W'));
    };
    return array_map($mapFunctionWorklog, $worklogs);
  }

/**
 * Get labels of the weeks (e.g. 22/2016)
 * @param $worklogs
 * @return array
 */
  function getWeekLabel($worklogs)
  {
    $mapFunctionWeeks = function ($item) {
      return $item['week'] . '/' . $item['year'];
    };
    $weeks = array_map($mapFunctionWeeks, $worklogs);
    $weeks = array_unique($weeks, SORT_STRING);
    return array_values($weeks);
  }
