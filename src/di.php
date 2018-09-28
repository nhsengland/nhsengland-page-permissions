<?php

$registrar->addInstance(new \NHSEngland\PagePermissions\Settings());
$registrar->addInstance(new \NHSEngland\PagePermissions\User(
    $registrar->getInstance(\NHSEngland\PagePermissions\Settings::class)
));
$registrar->addInstance(new \NHSEngland\PagePermissions\Permissions(
    $registrar->getInstance(\NHSEngland\PagePermissions\User::class),
    $registrar->getInstance(\NHSEngland\PagePermissions\Settings::class)
));
$registrar->addInstance(new \NHSEngland\PagePermissions\UI(
    $registrar->getInstance(\NHSEngland\PagePermissions\User::class),
    $registrar->getInstance(\NHSEngland\PagePermissions\Permissions::class),
    $registrar->getInstance(\NHSEngland\PagePermissions\Settings::class)
));
