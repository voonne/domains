<?php

(new \Nette\Loaders\RobotLoader())
	->setCacheStorage(new \Nette\Caching\Storages\FileStorage(__DIR__ . '/_output'))
	->addDirectory(__DIR__ . '/../src')
	->register();

if(!ini_get('date.timezone')) {
	date_default_timezone_set('GMT');
}
