<?php
// Routes


/**
 * Eröffnet eine Datenbankverbindung
 */
function getDBConnection(){
    $user = 'wtecch_fhnwWebec';
    $pwd = 'bUa&2QaU&5fa!2D';
    $connectionString = "mysql:host=194.126.200.46;dbname=wtecch_fhnwWebecJira";
    try {
        return new PDO($connectionString, $user, $pwd);
    } catch (Exception $e){
        exit ($e->getMessage());
    }
}

/*
$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});
*/

/**
 * Projektdetails eines Benutzers aus der Datenbank auslesen
 */
$app->get('/user/{uid}/projects/[{pid}]', function($request, $response, $args){
    $db = getDBConnection();
    $selection = $db->prepare('SELECT * FROM users WHERE uid = ?');
    $selection->execute(array($args['uid']));
    $user = $selection->fetch(PDO::FETCH_ASSOC);

    if ($user != null){
        if ($args['pid'] == null) {
            $selection = $db->prepare('SELECT * FROM projects WHERE uid = ?');
            $selection->execute(array($user['uid']));
            $projects = $selection->fetchAll(PDO::FETCH_ASSOC);
            foreach ($projects as $index => $project) {
                print_r($project);
            }
        } else {
            $selection = $db->prepare('SELECT * FROM projects WHERE uid = ? AND pid = ?');
            $selection->execute(array($user['uid'], $args['pid']));
            $project = $selection->fetch(PDO::FETCH_ASSOC);
            print_r($project);
        }
    } else {
        $response = $response->withStatus(404)
            ->withHeader('Content-Type', 'text/html')
            ->write('Page not found');
    }

    $db = null;

    return $response;
});
