(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.eventsMap = {
    attach: function attach(context) {
      $('.events-map').once('eventsMap').each(function () {
        // Prepare data.
        var $pins = drupalSettings.wwd_map.markers;
        var $token = drupalSettings.wwd_map.access_token;
        var $map_style = drupalSettings.wwd_map.map_style;
        var $markers = [];
        const bounds = [
          [-180, -78],
          [180, 78]
        ];
        const $countries = drupalSettings.wwd_map.countries;
        // Initiate the map.
        mapboxgl.accessToken = $token;
        const map = new mapboxgl.Map({
          container: 'events-map',
          style: $map_style,
          center: [12.323947, 30.811214],
          zoom: 0.8,
          hash: false,
          maxBounds: bounds,
        });
        // Add zoom and rotation controls to the map.
        map.addControl(new mapboxgl.NavigationControl());
        // Set markers.
        if ($pins.length) {
          $.each($pins, function (key, value) {
            $markers[key] = new mapboxgl.Marker()
              .setLngLat([value.coords.lng, value.coords.lat])
              .setPopup(new mapboxgl.Popup().setHTML(value.popup))
              .addTo(map);
          });
          for (const marker of $markers) {
            // Get all markers with the same coordinates.
            const sameMarkers = $markers.filter(m => m._lngLat.lat === marker._lngLat.lat && m._lngLat.lng === marker._lngLat.lng);
            // Ff there is more than one marker with the same coordinates.
            if (sameMarkers.length > 1) {
              // Get the index of the current marker.
              const index = sameMarkers.indexOf(marker);

              // Calculate the offset for the current marker.
              const offset = 10 * (index - (sameMarkers.length - 1) / 2);

              // Set the offset.
              marker.setOffset([offset, 0]);
            }
          }
        }
        // Fly to selected country.
        $(context).on('change', '.event-country-filter', function () {
          let $iso = this.value;
          let $found = $countries.find(country => country.iso3 === $iso);
          if (typeof $found === 'object' && $found !== null) {
            map.flyTo({
              center: [$found.lng, $found.lat],
              essential: true,
              zoom: 4,
            });
          } else {
            // Fly to default location.
            map.flyTo({
              center: [12.323947, 30.811214],
              essential: true,
              zoom: 2,
            });
          }

        });

      });

    }
  };
}(jQuery, Drupal, drupalSettings));
