(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.eventsMap = {
    attach: function attach(context) {
      $('.events-map').once('eventsMap').each(function () {
        // Prepare data.
        var $pins = drupalSettings.wwd_map.markers;
        var $token = drupalSettings.wwd_map.access_token;
        var $map_style = drupalSettings.wwd_map.map_style;
        var $markers = [];
        // Initiate the map.
        mapboxgl.accessToken = $token;
        const map = new mapboxgl.Map({
          container: 'events-map',
          style: $map_style,
          center: [12.323947, 30.811214],
          zoom: 2,
          hash: false,
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
            const sameMarkers = $markers.filter(m => m.lat === marker.lat && m.lon === marker.lon);

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
        document.getElementById('fly').addEventListener('click', () => {
          // Fly to a random location.
          map.flyTo({
            center: [(Math.random() - 0.5) * 360, (Math.random() - 0.5) * 100],
            essential: true,
          });
        });
      });

    }
  };
}(jQuery, Drupal, drupalSettings));
