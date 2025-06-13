<?php declare(strict_types = 1);

/*
** Zabbix
** Copyright (C) 2001-2021 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

namespace Modules\GeomapHosts\Actions;

use CController;
use CControllerResponseData; 
use CControllerResponseFatal;
use CRoleHelper;
use API;

class CControllerGeomapHostView extends CController {
    protected function init(): void {
		$this->disableSIDValidation();
	}

	protected function checkInput(): bool {
		return TRUE;
	}

	protected function checkPermissions(): bool {
		$permit_user_types = [USER_TYPE_ZABBIX_ADMIN, USER_TYPE_SUPER_ADMIN];         
		return in_array($this->getUserType(), $permit_user_types);
	}

	protected function doAction(): void {
		$hosts = API::Host()->get([
			'output'=> ['name', 'hostid'],
			'selectInventory' => ['location_lat', 'location_lon'],
            'searchWildcardsEnabled' => true,
            'searchInventory' => ['location_lat' => '*'],
		]);

        $final_hosts = array();
        foreach($hosts as $host){
            if ($host['inventory']['location_lat'] != "" && $host['inventory']['location_lon']!=""){
                array_push($final_hosts, $host);
            }
        }

		$index=0;
		foreach($final_hosts as $host){
			$problems = API::Problem()->get([
				'hostids' => $host['hostid'],
				'output' => ['severity']
			]);

			$final_hosts[$index]['problems'] = $problems;
			$index++;
		}

		$value = array();
		$index = 0;
		foreach(glob('modules/zabbix-module-geomap/resources/*', GLOB_ONLYDIR) as $directory){
			$id = pathinfo($directory)['filename'];
			$value[$index]['name'] = $id;
			$limits = array();
			$index_dir = 0;
			foreach(glob('modules/zabbix-module-geomap/resources/'.$id.'/*', GLOB_ONLYDIR) as $under_directory){ 				
				$val = pathinfo($under_directory)['filename'];
				$files = array();
				foreach(glob('modules/zabbix-module-geomap/resources/'.$id.'/'.$val.'/*.geojson') as $filename){
					array_push($files, pathinfo($filename)['filename']);
				}
				array_unshift($files, "All ".$val);
				$limits[$index_dir]['name'] = $val;
				$limits[$index_dir]['values'] = $files;
				$limits[$index_dir]['default'] = "All ".$val;
				$index_dir++;
				$value[$index]['limits'] = $limits;
			}
			$index++;
		}		
        // echo("<script>console.log('PHP OUTPUT: " . json_encode($value) . "');</script>");

		$data = [
			'values' => $value,
			'hosts' => $final_hosts,
        ];

		$response = new CControllerResponseData($data);
		$response->setTitle(_('Hosts'));
		$this->setResponse($response);
	}
}
