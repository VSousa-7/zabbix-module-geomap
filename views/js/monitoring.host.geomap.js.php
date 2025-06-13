<?php

/**
 * @var CView $this
 */

?>

<script type="text/javascript">
  //All global severities variables
  var SEVERITY_NO_PROBLEM = -1;
	var SEVERITY_NOT_CLASSIFIED = 0;
	var SEVERITY_INFORMATION = 1;
	var SEVERITY_WARNING = 2;
	var SEVERITY_AVERAGE = 3;
	var SEVERITY_HIGH = 4;
	var SEVERITY_DISASTER = 5;

  //All JS and CSS import
  $('head').append('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"/>');
  $('head').append('<link rel="stylesheet" type="text/css" href="modules/zabbix-module-geomap/views/css/style.css"/>');
  $('head').append('<script type="text/javascript" src="modules/zabbix-module-geomap/views/js/Leaflet/leaflet.js"/>');
  $('head').append('<script type="text/javascript" src="modules/zabbix-module-geomap/views/js/Mask.js"/>');
  $('head').append('<script type="text/javascript" src="modules/zabbix-module-geomap/views/js/Control.js"/>');
  $('head').append('<script type="text/javascript" src="modules/zabbix-module-geomap/views/js/Leaflet/leaflet.markercluster.js"/>');

  //Callback to read geojson file 
  var getJSON = function (url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.responseType = 'json';
    xhr.onload = function () {
        var status = xhr.status;
        if (status === 200) {
          callback(null, xhr.response);
        } else {
          callback(status, xhr.response);
        }
    };
    xhr.send();
  };

  //List of hosts get by action PHP file
  var hosts = <?php echo json_encode($data['hosts']); ?>;

  var geofiles = <?php echo json_encode($data['values']); ?>;

  //List of countries
  var countries = [];

  //Name of country selected (default: France)
  var country_selected = "";

  //Url for geojson file for selected department
  var url_file = "";

  //Store the filter in geomap to reset it
  var limit_filter = [];

  //Store the control in geomap to remove it
  var limit_control = [];

  //List of all severity selected
  var severity_selected = ["-1","0","1","2","3","4","5"];
  //If severity selected store in cache
  if(localStorage['severity_selected'] != "" && localStorage['severity_selected'] != undefined){
    severity_selected = localStorage['severity_selected'];
  }

  //List of polygons corresponding to department or region selected
  var polygons = new Array();


  //Create a geographical map
  var url = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
  var map = L.map('map').setView([ -23.55052, -46.633308 ], 6);  // Pode ajustar a posição inicial

L.tileLayer(url, {
  attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
  maxZoom: 19,
  minZoom: 3,
  noWrap: true
}).addTo(map);


const botao = document.querySelector('.custom-button-perso');
// Adiciona linhas do GeoJSON
fetch('modules/zabbix-module-geomap/views/data/linhas.geojson')
  .then(response => response.json())
  .then(data => {
    const geojsonLayer = L.geoJSON(data, {
      style: feature => ({
        color: feature.properties.cor || 'blue',
        weight: 4,
        opacity: 0.8
      }),
      onEachFeature: function (feature, layer) {
        if (feature.properties.nome) {
          layer.bindPopup(`<strong>${feature.properties.nome}</strong>`);
        }
      }
    });

    geojsonLayer.addTo(map);
    map.fitBounds(geojsonLayer.getBounds());
  })
  .catch(error => console.error('Erro ao carregar GeoJSON:', error));

// Funções estendidas do Leaflet
L.Map.include({
  updateFilter: function (filter_data) {
    this.getContainer().dispatchEvent(new CustomEvent('filter', { detail: filter_data }));
  },

  elmntCounter: (function () {
    let static = 0;
    return function () {
      return ++static;
    }
  })()
});
        (async () => {
  const response = await fetch('/zabbix/api_jsonrpc.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      jsonrpc: '2.0',
      method: 'host.get',
      params: {
        output: ['hostid', 'name'],
        selectInventory: ['location_lat', 'location_lon'],
        filter: { status: 0 } // apenas hosts habilitados
      },
      auth: window.ZABBIX_SESSION_TOKEN, // sessão atual
      id: 1
    })
  });

  const result = await response.json();
  const hosts = result.result;

  hosts.forEach(host => {
    const lat = parseFloat(host.inventory?.location_lat);
    const lon = parseFloat(host.inventory?.location_lon);

    if (!isNaN(lat) && !isNaN(lon)) {
      const marker = L.circleMarker([lat, lon], {
        radius: 8,
        color: 'blue',
        fillColor: '#33ccff',
        fillOpacity: 0.8
      }).addTo(map);

      marker.bindPopup(`<strong>${host.name}</strong>`);
    }
  });
})();


  //Initialize all severities and all icons
  let val = initSeveritiesAndIcons();
  var severity_levels = val[0], icons = val[1];

  //Initialize all elements on a map

  initSearchBar(map); 
  initFilter(map, severity_levels);
  initCountryValues();
  //If file for mask store in cache
  if(localStorage['url_file'] != "" && localStorage['url_file'] != undefined){
    url_file = localStorage['url_file'];
    setMaskOnMap(url_file);
    var dir = url_file.split("/");
    country_selected = dir[3];
    initSelectCountry(map, countries);
    getCountry();
    document.getElementById("select-country").value = dir[3];
    document.getElementById("select-limit-"+dir[4]).value = dir[5].replace('.geojson', '');
  }else{
    //Get last country selected if store in cache
    if(localStorage['country_selected'] != "" && localStorage['country_selected'] != undefined){
      country_selected = localStorage['country_selected'];
    }
    initSelectCountry(map, countries);
    getCountry();
    initMarker();
  }  

  /**
   * Get all countries available
   */
  function initCountryValues(){
    for(var i=0; i<geofiles.length; i++){
      countries.push({name: geofiles[i].name})
    }
    country_selected = countries[0].name;
  }
  
  /**
   * Search the host ask by user in search bar
   */
  function search(){
    var saisie=document.getElementById("input").value;
    for(var i=0; i<hosts.length; i++){
      var name = hosts[i]['name'];
      if(name.toLowerCase()==saisie.toLowerCase()){
        //Remove all elements selected by user
        document.getElementById("input").value = '';
        url_file = "";
        limit_filter.forEach(limit =>{
          document.getElementById(limit.id).value = limit.default;
        });
        severity_selected = ["-1","0","1","2","3","4","5"];
        clearSeverityFilter();
        updateMap();
        map.setView([hosts[i]['inventory']['location_lat'], hosts[i]['inventory']['location_lon']], 14)
      }
    }
  }

  /**
   * Initialize search bar with control
   * @param {*} map 
   */
  function initSearchBar(map){
    map.searchFilterControl = L.control.searchControl({
      position: 'topright',
      hosts: hosts,
    }).addTo(map);
  }
  
  /**
   * Initialize button filter severities with control
   * @param {*} map
   * @param {Map} severity_levels  
   */
  function initFilter(map, severity_levels){
    map.severityFilterControl = L.control.severityControl({
      position: 'topright',
      severity_levels: severity_levels,
      disabled: false, 
      severity_selected: severity_selected,
    }).addTo(map);

    map.getContainer().addEventListener('click', (e) => {
    if (e.target.classList.contains('leaflet-container')) {
      map.severityFilterControl.close();
    }
    }, false);
  }
  
   /**
   * Clear all severity filter selected when user make a search
   */
  function clearSeverityFilter() {
    for (let index = 1; index < 8; index++) {
      document.getElementById("filter_severity_" + index.toString()).checked = false;
    }
  }

  /**
   * Initialize all limit filter for country
   */
  function initLimitSectionDependCountry(){
    //Remove old filter and control
    limit_filter = [];
    if(limit_control.length!=0){
      for(var i=0; i<limit_control.length; i++){
        map.removeControl(limit_control[i]);
      }
      limit_control = [];
    }

    for(var i=0; i<geofiles.length; i++){
      //Get all limits available for country selected
      if(geofiles[i].name==country_selected){
        if(geofiles[i]['limits'] != undefined){
          for(var j=0; j<geofiles[i]['limits'].length; j++){
            var type = geofiles[i]['limits'][j]['name'];
            var values = geofiles[i]['limits'][j]['values'];
            var default_value = geofiles[i]['limits'][j]['default'];

            //Create a control element
            map.limitFilterControl = L.control.limitControl({
              position: 'topright',
              limits: values,
              limit_selected: default_value,
              default_limit: default_value,
              type: type
            }).addTo(map);

            //Add filter in list to reset it after
            limit_filter.push({id: "select-limit-"+type, default: default_value})
            limit_control.push(map.limitFilterControl)
          }  
        }      
      }
    }
    initClearLimit(map);
  }

  /**
   * Initalize select country with control
   * @param {*} map
   * @param {Array} countries
   */
  function initSelectCountry(map, countries){
    //If country store in cache
    if(localStorage['country_selected'] != "" && localStorage['country_selected'] != undefined){
      country_selected = localStorage['country_selected'];
    }
    map.countryFilterControl = L.control.countryControl({
      position: 'topright',
      countries: countries,
      country_selected: country_selected,
    }).addTo(map);
  }

  /**
   * Initalize clear button with control
   * @param {*} map
   */
  function initClearLimit(map){
    map.clearFilterControl = L.control.clearControl({
      position: 'topright',
    }).addTo(map);
    limit_control.push(map.clearFilterControl)
  }

  /**
   * Clear the filters limit to initial values
   */
  function clearLimit(){
    url_file = "";
    limit_filter.forEach(limit =>{
      document.getElementById(limit.id).value = limit.default;
    });
    updateMap();
    map.setView([46.4336, 2.640771],6);
  }

  /**
   * Get country selected by user
   */
  function getCountry(){
    country_selected=document.getElementById("select-country").value;
    localStorage['country_selected'] = country_selected;
    initLimitSectionDependCountry();
    if(localStorage['url_file'] != "" && localStorage['url_file'] != undefined){
      url_file = localStorage['url_file'];
      var dir = url_file.split("/");
      country_file = dir[3];
      if(country_file == country_selected){
        document.getElementById("select-limit-"+dir[4]).value = dir[5].replace('.geojson', '');
      }
    }
  }

  /**
   * Get the GeoJson file depending country and filter selected
   */
  function getFileGeoJson(type, default_value, value){
    if(default_value != value){
      url_file = "modules/zabbix-module-geomap/resources/"+country_selected+"/"+type+"/".concat("",value.concat("",".geojson"));
    }
    updateMap();
  }

  /**
   * Set mask on MAP
   * @param {String} url_file
   */
  function setMaskOnMap(url_file){
    if(url_file != ""){
      getJSON(url_file, function (err, values) {
        if (err !== null) {
          console.log(err)
          console.log('Something went wrong: ' + err);
        } else {
          showGeoJSON(values);
        }
      });
    }else{
      showGeoJSON(null);
    }   
  }
  
  /**
   * Show geoJSON file selected and initialize marker
   * @param {Array | null} values
   */
  function showGeoJSON(values) {
    var latLngs=[];
    if(values!=null){
      //Display geoJSON and mask in MAP
      var geoJSON = new L.geoJSON(values, options).addTo(map);
      latLngs.push(geoJSON.getLayers()[0].getLatLngs());
      L.mask(latLngs).addTo(map);

      //Get polygons elements in geoJSON
      polygons = latLngs[0];

      //Set view on the first LatLng object in list
      var object = latLngs[0][0][0];
      if(Array.isArray(object)){
        object = object[0];
      }
      map.setView(new L.LatLng(object.lat, object.lng), 8);
    }else{
      L.maskReset(latLngs).addTo(map);
      polygons = new Array();
    }
    initMarker();
  }

  /**
   * Update map after filter changements
   */
  function updateMap(){
    //Reset layer on MAP
    map.eachLayer(function (layer) {
        if (url != layer._url){map.removeLayer(layer)};
    });

    //URL file storage in cache to conserve user preference
    localStorage['url_file'] = url_file;
    setMaskOnMap(url_file); 
  }

  /**
   * Zoom on marker selected when user make simple click on it
   */
  function onClickMarker(e) {
    map.setView(this.getLatLng(), 14);
  }

  /**
   * Open information about the host when user make double click on it
   */
  function onDoubleClickMarker(e) {
    host = e.sourceTarget.options.hostVal
    url = window.location.origin+window.location.pathname+"?name="+host['name']+"&action=host.view"
    window.open(url, '_blank').focus();
  }

  /**
   * Calculate if marker is inside a polygon
   * @param {float} x
   * @param {float} y
   * @param {Array} poly
   */
  function isMarkerInsidePolygon(x, y, poly) {
    var inside = false;
    for (var i = 0, j = poly.length - 1; i < poly.length; j = i++) {
        var xi = poly[i].lat, yi = poly[i].lng;
        var xj = poly[j].lat, yj = poly[j].lng;

        var intersect = ((yi > y) != (yj > y))
            && (x < (xj - xi) * (y - yi) / (yj - yi) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
  };

  /**
   * Initialize markers on MAP
   */
  function initMarker() {
    //Create clusters of markers
    var marker_clusters = L.markerClusterGroup({
      iconCreateFunction: function(cluster){
        const markers = cluster.getAllChildMarkers();
		    const childCount = cluster.getChildCount();

        //Set severities list depending host in cluster
        var list_severity = []
        var color = "";      
        for(const element of markers){
          var severity=-1;
          switch (element.options.className) {
            case 'notClassifiedClass':
              severity = 0;
              break;
            case 'informationClass':
              severity = 1;
              break;
            case 'warningClass':
              severity = 2;
              break;
            case 'averageClass':
              severity = 3;
              break;
            case 'highClass':
              severity = 4;
              break;
            case 'disasterClass':
              severity = 5;
              break;
          }
          //Add severity only one time in list
          if(!list_severity.includes(severity)){
            list_severity.push(severity)
          }
        }

        //Get the most import severity
        var max_severity = Math.max(...list_severity)
        if(list_severity.length==0){
          max_severity=-1;
        }

        //Get color of the max severity
        color = severity_levels.get(max_severity).color;
        //Create div icon on map
        return new L.DivIcon({
					html: `
						<div style="background-color: ${color};text-align: center;border-radius: 50%;margin-left: 5px;margin-top: 5px;height: 40px;width: 40px;">
							<span style="line-height: 40px;font-size:20px">${childCount}</span>
						</div>`,
					className: 'geomap-cluster',
					iconSize: new L.Point(40, 40)
				});
      }
    });

    //Create marker and popup for all hosts
    for(var i=0; i<hosts.length; ++i){
      //Calculate how many problems in each severity
      var list_severity = []
      var nb_not_classified = 0;
      var nb_information = 0;
      var nb_warning = 0;
      var nb_average = 0;
      var nb_high = 0;
      var nb_disaster = 0;
      for(var j=0; j<hosts[i]['problems'].length; j++){
        const severity = parseInt(hosts[i]['problems'][j]['severity']);
        if(!list_severity.includes(severity)){
          list_severity.push(severity)
        }
        switch (severity) {
          case 0:
            ++nb_not_classified;
            break;
          case 1:
            ++nb_information;
            break;
          case 2:
            ++nb_warning;
            break;
          case 3:
            ++nb_average;
            break;
          case 4:
            ++nb_high;
            break;
          case 5:
            ++nb_disaster;
            break;
        }
      }

      //Get the most important severity
      var max_severity = Math.max(...list_severity)
      if(list_severity.length==0){
        max_severity=-1;
      }

      //Store severity selected in cache
      localStorage['severity_selected'] = severity_selected;

      //if the most important severity is in severity selected by the user
      if(severity_selected.includes(max_severity.toString())){
        var inside = false;
        //Check if host is in polygons of department or region selected
        if(polygons.length != 0){
          for(var m=0; m<polygons.length; m++){
            var poly = polygons[m];
            if(Array.isArray(poly[0])){
              poly = poly[0];
            }
            if(isMarkerInsidePolygon(hosts[i]['inventory']['location_lat'], hosts[i]['inventory']['location_lon'], poly)){
              inside = true;
            }
          }
        }else{
          //If polygons list is empty => all hosts are displayed
          inside = true;
        }
        
        if(inside){
          //Create host with essential information
          const host = {
            'id': hosts[i]['hostid'],
            'name': hosts[i]['name'],
            0: nb_not_classified,
            1: nb_information,
            2: nb_warning,
            3: nb_average,
            4: nb_high,
            5: nb_disaster
          }

          //Create popup
          const popup = makePopupContent([host], severity_levels);

          //Create Marker
          var m = L.marker( [hosts[i]['inventory']['location_lat'], hosts[i]['inventory']['location_lon']], {icon: icons[max_severity], className: severity_levels.get(max_severity).classMarker, hostVal: host})
                  .bindPopup( popup ).on('click', onClickMarker).on('dblclick', onDoubleClickMarker);

          //Open popup if mouse over
          m.on("mouseover", function(e){
            this.openPopup();
          });

          //Close popup if mouse out
          m.on("mouseout", function(e){
            this.closePopup();
          });

          //Add marker in clusters
          marker_clusters.addLayer(m);
        }
        
      }
    }

    //Create and open popup if mouse over
    marker_clusters.on('clustermouseover', function(c) {
      var markers = c.layer.getAllChildMarkers();
      var hosts = []
      for(const element of markers){
        const host = element.options.hostVal;
        hosts.push(host);
      }
      var textPopup = makePopupContent(hosts, severity_levels);
      var popup = L.popup().setLatLng(c.layer.getLatLng()).setContent(textPopup).openOn(map);

    })

    //Close popup if mouse out
    marker_clusters.on('clustermouseout',function(c){
      map.closePopup();
    })

    //Close popup if cluster is clicked
    marker_clusters.on('clusterclick',function(c){
      map.closePopup();
    }); 
    
    //Add cluster in MAP
    map.addLayer(marker_clusters);
  }

  /**
   * Create popup for host or cluster
   * @param {Array} hosts
   * @param {Map} severity_levels
   */
  function makePopupContent(hosts, severity_levels){
    /**
     * Make cell information in table row
     */
    const makeDataCell = (host, severity) => {
			if (host[severity]!=0) {
				const style = severity_levels.get(severity).class;
				const problems = host[severity];
				return `<td class="${style}">${problems}</td>`;
			}
			else {
				return `<td></td>`;
			}
		};

    /**
     * Make row in table
     */
    const makeTableRows = () => {
			hosts.sort((a, b) => {
				if (a['name'] < b['name']) {
					return -1;
				}
				if (a['name'] > b['name']) {
					return 1;
				}
				return 0;
			});

			let rows = ``;
			hosts.forEach(host => {
				rows += `
					<tr>
						<td class="nowrap">${host['name']}</td>
						${makeDataCell(host, SEVERITY_DISASTER, severity_levels)}
						${makeDataCell(host, SEVERITY_HIGH, severity_levels)}
						${makeDataCell(host, SEVERITY_AVERAGE, severity_levels)}
						${makeDataCell(host, SEVERITY_WARNING, severity_levels)}
						${makeDataCell(host, SEVERITY_INFORMATION, severity_levels)}
						${makeDataCell(host, SEVERITY_NOT_CLASSIFIED, severity_levels)}
					</tr>`;
			});

			return rows;
		};

    //Make table
    var html = `
			<table class="list-table">
			<thead>
			<tr>
				<th>Host</th>
				<th>${severity_levels.get(SEVERITY_DISASTER).abbr}</th>
				<th>${severity_levels.get(SEVERITY_HIGH).abbr}</th>
				<th>${severity_levels.get(SEVERITY_AVERAGE).abbr}</th>
				<th>${severity_levels.get(SEVERITY_WARNING).abbr}</th>
				<th>${severity_levels.get(SEVERITY_INFORMATION).abbr}</th>
				<th>${severity_levels.get(SEVERITY_NOT_CLASSIFIED).abbr}</th>
			</th>
			</thead>
			<tbody>${makeTableRows()}</tbody>
			</table>`;

    return html
  }

  /**
   * Initialize severities and icons
   */
  function initSeveritiesAndIcons() {
    //Add all severity and his information in Map
    const severity_levels = new Map();
    severity_levels.set(SEVERITY_NO_PROBLEM, {
			name: t('No problem'),
			abbr: t('O'),
			class: 'na-bg',
      color: '#86cc89',
      classMarker: 'noProblemClass'
		});
		severity_levels.set(SEVERITY_NOT_CLASSIFIED, {
			name: t('Not classified'),
			abbr: t('N'),
			class: 'na-bg',
      color: '#97aab3',
      classMarker: 'notClassifiedClass'
		});
		severity_levels.set(SEVERITY_INFORMATION, {
			name: t('Information'),
			abbr: t('I'),
			class: 'info-bg',
      color: '#7499ff',
      classMarker: 'informationClass'
		});
		severity_levels.set(SEVERITY_WARNING, {
			name: t('Warning'),
			abbr: t('W'),
			class: 'warning-bg',
      color: '#ffc859',
      classMarker: 'warningClass'
		});
		severity_levels.set(SEVERITY_AVERAGE, {
			name: t('Average'),
			abbr: t('A'),
			class: 'average-bg',
      color: '#ffa059',
      classMarker: 'averageClass'
		});
		severity_levels.set(SEVERITY_HIGH, {
			name: t('High'),
			abbr: t('H'),
			class: 'high-bg',
      color: '#e97659',
      classMarker:'highClass'
		});
		severity_levels.set(SEVERITY_DISASTER, {
			name: t('Disaster'),
			abbr: t('D'),
			class: 'disaster-bg',
      color: '#e45959',
      classMarker: 'disasterClass'
		});

    //Add all possibile icons depending severity
    const icons = {}
    icons[SEVERITY_NOT_CLASSIFIED] = L.icon({
			iconUrl: 'modules/zabbix-module-geomap/views/images/marker-icon-2x-grey.png',
			shadowUrl: 'modules/zabbix-module-geomap/views/images/marker-shadow.png',
			iconSize: [30, 50],
		  iconAnchor: [12, 41],
		  popupAnchor: [1, -34],
		  shadowSize: [41, 41],
			shadowAnchor: [13, 40]
		});
    icons[SEVERITY_INFORMATION] = L.icon({
			iconUrl: 'modules/zabbix-module-geomap/views/images/marker-icon-2x-blue.png',
			shadowUrl: 'modules/zabbix-module-geomap/views/images/marker-shadow.png',
			iconSize: [30, 50],
		  iconAnchor: [12, 41],
		  popupAnchor: [1, -34],
		  shadowSize: [41, 41],
			shadowAnchor: [13, 40]
		});
    icons[SEVERITY_WARNING] = L.icon({
			iconUrl: 'modules/zabbix-module-geomap/views/images/marker-icon-2x-gold.png',
			shadowUrl: 'modules/zabbix-module-geomap/views/images/marker-shadow.png',
			iconSize: [30, 50],
		  iconAnchor: [12, 41],
		  popupAnchor: [1, -34],
		  shadowSize: [41, 41],
			shadowAnchor: [13, 40]
		});
    icons[SEVERITY_AVERAGE] = L.icon({
			iconUrl: 'modules/zabbix-module-geomap/views/images/marker-icon-2x-yellow.png',
			shadowUrl: 'modules/zabbix-module-geomap/views/images/marker-shadow.png',
			iconSize: [30, 50],
		  iconAnchor: [12, 41],
		  popupAnchor: [1, -34],
		  shadowSize: [41, 41],
			shadowAnchor: [13, 40]
		});
    icons[SEVERITY_HIGH] = L.icon({
			iconUrl: 'modules/zabbix-module-geomap/views/images/marker-icon-2x-orange.png',
			shadowUrl: 'modules/zabbix-module-geomap/views/images/marker-shadow.png',
			iconSize: [30, 50],
		  iconAnchor: [12, 41],
		  popupAnchor: [1, -34],
		  shadowSize: [41, 41],
			shadowAnchor: [13, 40]
		});
    icons[SEVERITY_DISASTER] = L.icon({
			iconUrl: 'modules/zabbix-module-geomap/views/images/marker-icon-2x-red.png',
			shadowUrl: 'modules/zabbix-module-geomap/views/images/marker-shadow.png',
			iconSize: [30, 50],
		  iconAnchor: [12, 41],
		  popupAnchor: [1, -34],
		  shadowSize: [41, 41],
			shadowAnchor: [13, 40]
		});
    icons[SEVERITY_NO_PROBLEM] = L.icon({
			iconUrl: 'modules/zabbix-module-geomap/views/images/marker-icon-2x-green.png',
			shadowUrl: 'modules/zabbix-module-geomap/views/images/marker-shadow.png',
			iconSize: [30, 50],
		  iconAnchor: [12, 41],
		  popupAnchor: [1, -34],
		  shadowSize: [41, 41],
			shadowAnchor: [13, 40]
		});
    return [severity_levels, icons];
	}

</script>
