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

$app->post('/user', function($request, $response, $args){
    $json = $request->getBody();
    $data = json_decode($json, true);

    $db = getDBConnection();
    $insert = $db->prepare('INSERT INTO users (email) VALUES (:email)');
    $insert->bindParam(':email', $data['email']);

    $db->beginTransaction();

    $success = $insert->execute();
    if($success){
        $db->commit();
    } else {
        $db->rollBack();
    }

	$db = null;
});

$app->post('/user/{uid}/projects', function($request, $response, $args){
    $json = $request->getBody();
    $data = json_decode($json, true);

    $db = getDBConnection();
    $insert = $db->prepare('INSERT INTO projects (uid, pid, name, weekload, maxhours, rangestart, rangeend, description) VALUES (:uid, :pid, :name, :weekload, :maxhours, :rangestart, :rangeend, :description)');
    $insert->bindParam(':uid', $args['uid']);
    $insert->bindParam(':pid', $data['pid']);
    $insert->bindParam(':name', $data['name']);
    $insert->bindParam(':weekload', $data['weekload']);
    $insert->bindParam(':maxhours', $data['maxhours']);
    $insert->bindParam(':rangestart', $data['rangestart']);
    $insert->bindParam(':rangeend', $data['rangeend']);
    $insert->bindParam(':description', $data['description']);

    $db->beginTransaction();

    $success = $insert->execute();
    if($success){
        $db->commit();
    } else {
        $db->rollBack();
    }

    $db = null;
});

$app->put('/user/{uid}/projects',function($request, $response, $args){
    $json = $request->getBody();
    $data = json_decode($json, true);

    $db = getDBConnection();
    $update = $db->prepare('UPDATE projects SET pid=?, name=?, weekload=?, maxhours=?, rangestart=?, rangeend=?, description=? WHERE uid=? AND pid=?');
    $update->bindParam(':uid', $args['uid']);
    $update->bindParam(':pid', $data['pid']);

    $db->beginTransaction();

    $success = $update->execute(array($data['pid'],$data['name'],$data['weekload'],$data['maxhours'],$data['rangestart'],$data['rangeend'],$data['description'],$args['uid'],$data['pid']));
    if($success){
        $db->commit();
    } else {
        $db->rollBack();
    }

    $db = null;
});

$app->delete('/user/{uid}/projects/{pid}', function($request, $response, $args){
    $db = getDBConnection();
    $delete = $db->prepare('DELETE FROM projects WHERE uid=? AND pid=?');
    $db->beginTransaction();
    $success = $delete->execute(array($args['uid'], $args['pid']));
    if($success){
        $db->commit();
    } else {
        $db->rollBack();
    }

    $db = null;
});
