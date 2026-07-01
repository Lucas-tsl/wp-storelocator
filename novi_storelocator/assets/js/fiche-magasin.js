document.addEventListener('DOMContentLoaded', () => {
    const canvas = document.querySelector('.fiche-magasin-map-canvas');
    if (!canvas || typeof L === 'undefined') {
        return;
    }

    const lat = parseFloat(canvas.dataset.lat);
    const lon = parseFloat(canvas.dataset.lon);
    if (isNaN(lat) || isNaN(lon)) {
        return;
    }

    const apikey = (typeof fiche_magasin_vars !== 'undefined') ? fiche_magasin_vars.apikey : '';

    const map = L.map(canvas.id, {
        center: [lat, lon],
        zoom: 15,
        attribution: '© MapTiler',
    });

    L.tileLayer(`https://api.maptiler.com/maps/basic-v2/{z}/{x}/{y}.png?key=${apikey}`).addTo(map);
    L.marker([lat, lon]).addTo(map)
        .bindPopup(canvas.dataset.name || '')
        .openPopup();
});
