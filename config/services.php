<?php

namespace DigitalMx\Flames;

// use DigitalMx\Flames\DocPage;
// use DigitalMx\Flames\Assets;
use Pimple\Container;


/* set up services in pimple container */

$container = new Container();

$container['pdo'] = function ($c) {
    return \DigitalMx\MyPDO::instance();
};
$container['member'] = function ($c) {
    return new Member($c);
};
$container['membera'] = function ($c) {
    return new MemberAdmin($c);
};
$container['assets'] = function ($c) {
    return new Assets($c);
};
$container['asseta'] = function ($c) {
    return new AssetAdmin($c);
};
$container['assetsrch'] = function ($c) {
	return new AssetSearch($c);
};

$container['templates'] = function ($c) {
	$pl = new \League\Plates\Engine(REPO_PATH . '/templates/plates','tpl');
	$pl->addFolder('help', REPO_PATH . '/templates/help');
    return $pl;
};

$container['article'] = function ($c) {
    return new Article($c);
};
$container['articlea'] = function ($c) {
		return new ArticleAdmin($c);
};


$container['news'] = function ($c) {
    return new News($c);
};
$container['messenger'] = function ($c) {
    return new Messenger($c);
};
$container['voting'] = function ($c) {
		return new Voting();
};
$container['publish'] = function ($c) {
		return new Publish($c);
};
$container['opps'] = function ($c) {
		return new Opportunities($c);
};
$container['galleries'] = function ($c) {
		return new Galleries($c);
};

