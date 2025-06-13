/**
 * Create transparent mask for all map outer bounds 
 */
L.Mask = L.Polygon.extend({
    options: {
        stroke: false,
        color: '#333',
        fillOpacity: 0.5,
        clickable: true,
        outerBounds: new L.LatLngBounds([-90, -360], [90, 360])
    },

    initialize: function (latLngs, options) {
        var outerBoundsLatLngs = [
            this.options.outerBounds.getSouthWest(),
            this.options.outerBounds.getNorthWest(),
            this.options.outerBounds.getNorthEast(),
            this.options.outerBounds.getSouthEast()
        ];
        L.Polygon.prototype.initialize.call(this, [outerBoundsLatLngs, latLngs], options);
    }

});

L.mask = function (latLngs, options) {
    return new L.Mask(latLngs, options);
};

/**
 * Create mask to delete opacity for all map outer bounds
 */
L.MaskReset = L.Polygon.extend({
    options: {
        stroke: false,
        fillOpacity: 0.0,
        clickable: true,
        outerBounds: new L.LatLngBounds([-90, -360], [90, 360])
    },

    initialize: function (latLngs, options) {
        var outerBoundsLatLngs = [
            this.options.outerBounds.getSouthWest(),
            this.options.outerBounds.getNorthWest(),
            this.options.outerBounds.getNorthEast(),
            this.options.outerBounds.getSouthEast()
        ];
        L.Polygon.prototype.initialize.call(this, [outerBoundsLatLngs, latLngs], options);
    }

});

L.maskReset = function (latLngs, options) {
    return new L.MaskReset(latLngs, options);
};

var options = {
    style: function (feature) {
        return {
            "color": "#ddd",
            "weight": 3,
            "opacity": 1,
            "fillColor": "transparent",
            "fillOpacity": 0.0
        };
    }
};