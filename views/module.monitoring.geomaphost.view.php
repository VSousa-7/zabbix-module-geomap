<?php declare(strict_types = 1);

/**
 * @var CView $this
 */

$widget = (new CWidget())->setTitle(_('Geomap'));
$widget->addItem((new CDiv())->setId('map')->addStyle('width: 1500;')->addStyle('height: 800px;')->addStyle('border: 1px solid #AAA;'));
$widget->show();

$this->includeJsFile('monitoring.host.geomap.js.php');
