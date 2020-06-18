<?php

namespace DigitalMx\Flames;

// use DigitalMx\Flames\DocPage;
// use DigitalMx\Flames\Assets;
use Pimple\Container;
use Monolog\Logger;
use Monolog\Handler\SwiftMailerHandler;
use Monolog\Formatter\HtmlFormatter;

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
$container['assetsearch'] = function ($c) {
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
$container['comment'] = function ($c) {
	return new Comment($c);
};
// logger is monolog, with swiftmail used to email Critical incidents
$container['logger-prod'] = function($c) {
	$logger = new Logger('prod');
	$daycode = date('ymd');
	$logger->pushHandler(new \Monolog\Handler\StreamHandler
	(REPO_PATH . "/var/mono/prod.${daycode}.log" , Logger::INFO )
		);

	 $logger->setTimezone(new \DateTimeZone('America/Los_Angeles'));
	$transport = new \Swift_SendmailTransport('/usr/sbin/sendmail -bs');
	$mailer = new \Swift_Mailer($transport);
	$message = (new \Swift_Message('CRITICAL log in f2'));
	$message->setFrom(['admin@amdflames.org' => 'Flames Admin']);
	$message->setTo(['admin@amdflames.org' => 'Flames Admin']);
	//$message->setContentType("text/html");
	$mailerHandler = new SwiftMailerHandler($mailer, $message, Logger::CRITICAL,true);
	//$mailerHandler->setFormatter(new HtmlFormatter());
	$logger->pushHandler($mailerHandler);
	return $logger;
};

$container['logger-dbug'] = function($c) {
	$logger = new Logger('dbug');
	$daycode = date('ymd');
	$logger->pushHandler(new \Monolog\Handler\StreamHandler
	(REPO_PATH . "/var/mono/debug.${daycode}.log" , Logger::DEBUG )
		);
	 $logger->setTimezone(new \DateTimeZone('America/Los_Angeles'));
	return $logger;
};

//$container['logger-prod']->error('service running');
//$container['logger-prod']->addCritical('Critical Ran');
//$container['logger-dbug']->critical('simple critical');
