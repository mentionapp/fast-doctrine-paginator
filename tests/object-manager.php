<?php

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;

return (function () {
    $dbPath = __DIR__ . '/db.sqlite';

    if (file_exists($dbPath)) {
        unlink($dbPath);
    }

    $config = Setup::createAnnotationMetadataConfiguration(
        [__DIR__.'/Data'],
        true,
        null,
        null,
        false
    );

    $conn = [
        'driver' => 'pdo_sqlite',
        'path' => $dbPath,
    ];

    $em = EntityManager::create($conn, $config);

    $metadatas = $em->getMetadataFactory()->getAllMetadata();
    $schemaTool = new SchemaTool($em);
    $schemaTool->createSchema($metadatas);

    return $em;
})();
