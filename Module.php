<?php declare(strict_types = 1);

namespace Modules\GeomapHosts;
use APP;
use CController as CAction;
use Zabbix\Core\CModule;
use Zabbix\Core\CAction;
use Zabbix\Core\CMenuItem;
use Zabbix\Core\CMenu;

class Module extends CModule {

    public function getModuleInfo(): array {
        return [
            'name' => _('Geomap'),
            'version' => '1.0',
            'description' => _('Mapa com hosts por coordenadas'),
            'author' => 'Vinicius Sousa'
        ];
    }

    public function getPages(): array {
        return [
            new \Modules\GeomapHosts\Pages\GeomapPage()
        ];
    }

    public function init(): void {
        \APP::Component()->get('menu.main')
            ->findOrAdd(_('Monitoring'))
                ->getSubmenu()
                    ->insertAfter(_('Dashboard'), (new CMenuItem(_('Geomap')))
                        ->setAction('geomaphost.view'));
    }

    public function onBeforeAction(CAction $action): void {
        // Você pode deixar vazio se não for usar
    }

    public function onTerminate(CAction $action): void {
        // Você pode deixar vazio se não for usar
    }
}
