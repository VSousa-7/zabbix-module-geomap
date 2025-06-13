/**
 * Create new control to make search bar
 */
L.Control.searchControlControl = L.Control.extend({

    /**
     * Initialize control with map and hosts array
     * @param {*} param0 
     */
    initialize: function ({ hosts, map }) {
        this._hosts = hosts;
        this._map = map;
    },

    /**
     * Add elements of search bar in document
     * @param {*} map 
     * @returns 
     */
    onAdd: function (map) {
        //Create differents HTML tags and set attributes
        const div = L.DomUtil.create('div', 'example');
        const input = L.DomUtil.create('input', 'searchInput', div);
        var datalist = L.DomUtil.create('datalist', '', div);
        var btn = L.DomUtil.create('button', 'searchButton', div);
        const ibalise = L.DomUtil.create('i', 'fa fa-search', btn);

        div.setAttribute('id', 'example');

        btn.setAttribute('title', 'Search host');
        btn.setAttribute('id', 'searchButton');

        input.setAttribute('type', 'text');
        input.setAttribute('id', 'input')
        input.setAttribute('placeholder', 'Search host...');
        input.setAttribute('list', 'hosts');
        input.setAttribute('autocomplete', 'off');

        datalist.setAttribute('id', 'hosts');

        //Assign search action on button if clicked
        L.DomEvent.on(btn, 'click', () => { search() });

        for (var i = 0; i < this._hosts.length; ++i) {
            const option = L.DomUtil.create('option', '', datalist);
            option.value = this._hosts[i]['name']
        }

        input.addEventListener("keyup", function (event) {
            // Number 13 is the "Enter" key on the keyboard
            if (event.keyCode === 13) {
                document.getElementById("searchButton").click();
            }
        });

        return div;
    },

});

L.control.searchControl = function (opts) {
    return new L.Control.searchControlControl(opts);
};

/**
 * Create new control to make severities filter
 */
L.Control.severityControlControl = L.Control.extend({

    /**
     * Initialize control with  severity array, if it is disable and severity_selected array
     * @param {*} param0 
     */
    initialize: function ({ severity_levels, disabled, severity_selected }) {
        this._severity_levels = severity_levels;
        this._disabled = disabled;
        this._severity_selected = severity_selected;
    },

    /**
     * Add elements to filter in document
     * @param {*} map 
     * @returns 
     */
    onAdd: function (map) {
        //Create differents HTML tags and set attributes
        const div = L.DomUtil.create('div', 'leaflet-bar leaflet-control');
        const btn = L.DomUtil.create('a', 'geomap-filter-button', div);
        this.bar = L.DomUtil.create('ul', 'checkbox-list geomap-filter', div);

        btn.ariaLabel = t('Severity filter');
        btn.title = t('Severity filter');
        btn.role = 'button';
        btn.href = '#';

        if (!this._disabled) {
            //For each severity create one checkbox
            for (const [severity, prop] of this._severity_levels) {
                const li = L.DomUtil.create('li', '', this.bar);
                const chbox = L.DomUtil.create('input', '', li);
                const label = L.DomUtil.create('label', '', li);
                const span = L.DomUtil.create('span', '');
                const chBoxId = 'filter_severity_' + map.elmntCounter();

                label.append(span, document.createTextNode(prop.name));
                chbox.classList.add('checkbox-radio');
                chbox.type = 'checkbox';
                chbox.value = severity;
                chbox.id = chBoxId;
                label.htmlFor = chBoxId;

                if (this._severity_selected.length != 14 && this._severity_selected.includes(severity)) {
                    chbox.checked = true;
                }

                //If checkbox changes update MAP
                chbox.addEventListener('change', () => {
                    var detail = [...this.bar.querySelectorAll('input[type="checkbox"]:checked')].map(n => n.value)
                    if (detail.length == 0) {
                        var severities = ["-1", "0", "1", "2", "3", "4", "5"];
                        severity_selected = severities;
                        updateMap();
                    } else {
                        var severities = detail;
                        severity_selected = severities;
                        updateMap();
                    }
                });
            }

            L.DomEvent.on(btn, 'click', () => {
                this.bar.classList.toggle('collapsed');
            });
            L.DomEvent.on(this.bar, 'dblclick', (e) => {
                L.DomEvent.stopPropagation(e);
            });

        }
        else {
            div.classList.add('disabled');
        }

        L.DomEvent.on(btn, 'dblclick', (e) => {
            L.DomEvent.stopPropagation(e);
        });

        return div;
    },

    close: function () {
        this.bar.classList.remove('collapsed');
    }
});

L.control.severityControl = function (opts) {
    return new L.Control.severityControlControl(opts);
};

/**
 * Create new control to make clear button
 */
L.Control.clearControlControl = L.Control.extend({

    /**
     * Initialize control 
     */
    initialize: function ({ }) {
    },

    /**
     * Add elements to filter in document
     * @param {*} map 
     * @returns 
     */
    onAdd: function (map) {
        //Create differents HTML tags and set attributes
        const div = L.DomUtil.create('div');
        const input = L.DomUtil.create('input', 'button-clear button-top', div);

        div.setAttribute('id', 'clear');
        input.setAttribute('id', 'clearFilter');
        input.setAttribute('title', 'Clear select limit');
        input.setAttribute('type', 'button');
        input.setAttribute('value', 'Clear limit');

        //Assign clearDepartmentAndRegion action on button if clicked
        L.DomEvent.on(input, 'click', () => { clearLimit() });

        return div;
    },
});

L.control.clearControl = function (opts) {
    return new L.Control.clearControlControl(opts);
};

/**
 * Create new control to make country filter
 */
L.Control.countryControlControl = L.Control.extend({

    /**
     * Initialize control with countries array and if it is disable
     * @param {*} param0 
     */
    initialize: function ({ countries, country_selected }) {
        this._countries = countries;
        this._country_selected = country_selected;
    },

    /**
     * Add elements to filter in document
     * @param {*} map 
     * @returns 
     */
    onAdd: function (map) {
        //Create differents HTML tags and set attributes
        const div = L.DomUtil.create('div');
        const select = L.DomUtil.create('select', 'custom-select-perso select-top', div);
        const btn = L.DomUtil.create('button', 'custom-button-perso button-top', div);
        const ibalise = L.DomUtil.create('i', 'fa fa-search', btn);

        div.setAttribute('id', 'country');
        select.setAttribute('id', 'select-country');
        btn.setAttribute('id', 'getCountry');
        btn.setAttribute('title', 'Search country');

        //Assign getCountry action on button if clicked
        L.DomEvent.on(btn, 'click', () => { getCountry() });

        //Create one option for each country
        this._countries.forEach(country => {
            const option = L.DomUtil.create('option', '', select);
            value = country.name;
            option.value = value;
            option.innerHTML = value;
            if (country.name == this._country_selected) {
                option.setAttribute('selected', '');
            }
        });

        select.addEventListener('change', () => {
            document.getElementById("getCountry").click();
        });

        select.addEventListener("keyup", function (event) {
            // Number 13 is the "Enter" key on the keyboard
            if (event.keyCode === 13) {
                document.getElementById("getCountry").click();
            }
        });

        return div;
    },
});

L.control.countryControl = function (opts) {
    return new L.Control.countryControlControl(opts);
};

/**
 * Create new control to make limit filter
 */
L.Control.limitControlControl = L.Control.extend({

    /**
     * Initialize control with limits array and if it is disable
     * @param {*} param0 
     */
    initialize: function ({ limits, limit_selected, default_limit, type }) {
        this._limits = limits;
        this._limit_selected = limit_selected;
        this._default_limit = default_limit;
        this._type = type;
    },

    /**
     * Add elements to filter in document
     * @param {*} map 
     * @returns 
     */
    onAdd: function (map) {
        //Create differents HTML tags and set attributes
        const div = L.DomUtil.create('div');
        const select = L.DomUtil.create('select', 'custom-select-perso select-top', div);
        const btn = L.DomUtil.create('button', 'custom-button-perso button-top', div);
        const ibalise = L.DomUtil.create('i', 'fa fa-search', btn);

        div.setAttribute('id', 'limit');
        select.setAttribute('id', 'select-limit-' + this._type);
        btn.setAttribute('id', 'getLimit' + this._type);

        //Assign limit action on button if clicked
        L.DomEvent.on(btn, 'click', () => {
            value = document.getElementById("select-limit-" + this._type).value;
            getFileGeoJson(this._type, this._default_limit, value);
        });
        //Create one option for each limit
        this._limits.forEach(limit => {
            const option = L.DomUtil.create('option', '', select);
            option.value = limit;
            option.innerHTML = limit;
            if (limit == this._limit_selected) {
                option.setAttribute('selected', '');
            }
        });

        select.addEventListener('change', () => {
            document.getElementById("getLimit" + this._type).click();
        });

        select.addEventListener("keyup", function (event) {
            // Number 13 is the "Enter" key on the keyboard
            if (event.keyCode === 13) {
                document.getElementById("getLimit" + this._type).click();
            }
        });

        return div;
    },
});

L.control.limitControl = function (opts) {
    return new L.Control.limitControlControl(opts);
};