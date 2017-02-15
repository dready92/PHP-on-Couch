<?php

$config = require __DIR__ . '/config.php';
foreach ( $config['databases'] as $databaseKey => $databaseData ) {
    if ( isset($databaseData['create']) && $databaseData['create'] === false ) {
        continue;
    }
    createDatabase($databaseData['host'],$databaseData['dbname']);
    checkDatabase($databaseData['host'],$databaseData['dbname']);

    if ( isset($databaseData['isadmin']) && $databaseData['isadmin'] ) {
        createAdmin($databaseData['host'],$databaseData['user'],$databaseData['password']);
    } elseif (isset($databaseData['user'])) {
        // create user
    }
}


function createDatabase ($host,$dbname) {

    $url = 'http://' . $host . '/' . $dbname;


    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );

    curl_setopt($ch, CURLOPT_PUT, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    // führe die Aktion aus und gebe die Daten an den Browser weiter
    curl_exec($ch);

    // schließe den cURL-Handle und gebe die Systemresourcen frei
    curl_close($ch);
}
function createAdmin ($host,$user,$password) {

    $url = $host . '/_config/admins/' . $user;
    $data = json_encode($password);
    $datasize = strlen($data);

    echo $url . PHP_EOL;
    var_dump($data);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url );
#    curl_setopt($ch, CURLOPT_VERBOSE, 1); // for debug

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Content-Length: ' . strlen($data)));

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );
    // führe die Aktion aus und gebe die Daten an den Browser weiter
    echo curl_exec($ch) . PHP_EOL;

    // schließe den cURL-Handle und gebe die Systemresourcen frei
    curl_close($ch);
}


function checkDatabase ($host,$dbname) {

    $url = 'http://' . $host . '/' . $dbname;
    echo $url . PHP_EOL;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true );

    curl_setopt($ch, CURLOPT_HEADER, 0);

    // führe die Aktion aus und gebe die Daten an den Browser weiter
    echo curl_exec($ch);

    // schließe den cURL-Handle und gebe die Systemresourcen frei
    curl_close($ch);
}
