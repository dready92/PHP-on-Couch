<?php

return array(
    'databases'=>array(
        'client_test1'=>array(
            'dbname'=>'couch_test_db1',
            'uri'=>'http://127.0.0.1:5984',
            'host'=>'127.0.0.1:5984',
            'isadmin'=>false,
            'create'=>false,
        ),
        'client_admin'=>array(
            'dbname'=>'couch_test_db1',
            'uri'=>'http://admin:foobar@127.0.0.1:5984',
            'host'=>'127.0.0.1:5984',
            'user'=>'admin',
            'password'=>'foobar',
            'isadmin'=>true,
            'create'=>true,
        ),
        'not_exists_database'=>array(
            'dbname'=>'klinai_not_exists_db',
            'host'=>'http://127.0.0.1:5984',
            'create'=>false,
        ),
    )
);
