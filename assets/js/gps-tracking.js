/* FleetSimplify VBMS — gps-tracking.js
   Leaflet map bootstrap, marker management, ETA recompute. */

(function () {
    'use strict';

    function haversineKm(a, b, c, d) {
        const R = 6371;
        const toR = function (x) { return x * Math.PI / 180; };
        const dLat = toR(c - a);
        const dLon = toR(d - b);
        const x = Math.sin(dLat/2)**2 + Math.cos(toR(a)) * Math.cos(toR(c)) * Math.sin(dLon/2)**2;
        return 2 * R * Math.asin(Math.min(1, Math.sqrt(x)));
    }

    function etaText(km, avgKmh) {
        avgKmh = avgKmh || 35;
        const mins = Math.round((km / avgKmh) * 60);
        if (mins < 1) return 'Arriving';
        if (mins < 60) return mins + ' min';
        const h = Math.floor(mins / 60), r = mins % 60;
        return h + 'h ' + r + 'm';
    }

    // ---------- Mechanic dashboard map ----------
    window.fsMechanicMap = function (opts) {
        const map = L.map(opts.elId).setView([opts.lat, opts.lng], opts.zoom || 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        const marker = L.marker([opts.lat, opts.lng], { draggable: false }).addTo(map);
        marker.bindPopup('Your last known location').openPopup();

        document.getElementById(opts.btnId).addEventListener('click', function () {
            if (!navigator.geolocation) {
                window.toast('Geolocation not available.', 'error');
                return;
            }
            const btn = this;
            btn.disabled = true;
            btn.textContent = 'Locating…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                const lat = pos.coords.latitude, lng = pos.coords.longitude;
                const fd = new FormData();
                fd.append('latitude', lat);
                fd.append('longitude', lng);
                fd.append('csrf', opts.csrf);
                fetch(opts.updateUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            marker.setLatLng([lat, lng]);
                            map.setView([lat, lng], 14);
                            window.toast('Location updated.', 'success');
                        } else {
                            window.toast(data.error || 'Failed to update location.', 'error');
                        }
                    })
                    .catch(function () { window.toast('Network error.', 'error'); })
                    .finally(function () { btn.disabled = false; btn.textContent = 'Update My Location'; });
            }, function (err) {
                window.toast('Location denied: ' + err.message, 'error');
                btn.disabled = false;
                btn.textContent = 'Update My Location';
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    };

    // ---------- User-side: track mechanic location with ETA ----------
    window.fsTrackMechanic = function (opts) {
        const map = L.map(opts.elId).setView([opts.driverLat, opts.driverLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        const driverIcon = L.divIcon({ html: '<div style="background:#0A1628;color:#fff;border-radius:50%;padding:6px 9px;font-weight:700;">You</div>', className: 'fs-pin', iconSize: [40,30] });
        const mechIcon   = L.divIcon({ html: '<div style="background:#FF6B35;color:#fff;border-radius:50%;padding:6px 9px;font-weight:700;">M</div>', className: 'fs-pin', iconSize: [30,30] });

        const driverMarker = L.marker([opts.driverLat, opts.driverLng], { icon: driverIcon }).addTo(map).bindPopup('Your location');
        let mechMarker = null;
        let line = null;

        function refresh() {
            fetch(opts.fetchUrl, { credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (!data.ok || !data.location) {
                        const pill = document.getElementById(opts.etaId);
                        if (pill) { pill.textContent = 'Awaiting mechanic location'; pill.classList.add('warn'); }
                        return;
                    }
                    const m = data.location;
                    const ll = [parseFloat(m.latitude), parseFloat(m.longitude)];
                    if (!mechMarker) mechMarker = L.marker(ll, { icon: mechIcon }).addTo(map).bindPopup('Mechanic');
                    else mechMarker.setLatLng(ll);
                    if (line) map.removeLayer(line);
                    line = L.polyline([[opts.driverLat, opts.driverLng], ll], { color: '#FF6B35', weight: 3, dashArray: '6 6' }).addTo(map);
                    map.fitBounds(line.getBounds(), { padding: [40,40] });

                    const km = haversineKm(opts.driverLat, opts.driverLng, ll[0], ll[1]);
                    const pill = document.getElementById(opts.etaId);
                    if (pill) {
                        pill.textContent = 'ETA ~ ' + etaText(km) + '  •  ' + km.toFixed(1) + ' km';
                        pill.classList.remove('warn');
                    }
                })
                .catch(function () { /* noop */ });
        }

        refresh();
        setInterval(refresh, 15000);
    };

    // ---------- Mechanic chat: see the driver, update own location ----------
    window.fsTrackDriver = function (opts) {
        const map = L.map(opts.elId).setView([opts.driverLat, opts.driverLng], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        const driverIcon = L.divIcon({ html: '<div style="background:#0A1628;color:#fff;border-radius:50%;padding:6px 9px;font-weight:700;">D</div>', className: 'fs-pin', iconSize: [30,30] });
        const mechIcon   = L.divIcon({ html: '<div style="background:#FF6B35;color:#fff;border-radius:50%;padding:6px 9px;font-weight:700;">You</div>', className: 'fs-pin', iconSize: [40,30] });

        const driverMarker = L.marker([opts.driverLat, opts.driverLng], { icon: driverIcon }).addTo(map).bindPopup('Driver location').openPopup();
        let mechMarker = L.marker([opts.mechLat, opts.mechLng], { icon: mechIcon }).addTo(map).bindPopup('You (last known)');
        let line = L.polyline([[opts.mechLat, opts.mechLng], [opts.driverLat, opts.driverLng]], { color: '#FF6B35', weight: 3, dashArray: '6 6' }).addTo(map);
        map.fitBounds(line.getBounds(), { padding: [40, 40] });

        const pill = document.getElementById(opts.etaId);
        function refreshETA(mLat, mLng) {
            const km = haversineKm(mLat, mLng, opts.driverLat, opts.driverLng);
            if (pill) pill.textContent = 'ETA ~ ' + etaText(km) + '  •  ' + km.toFixed(1) + ' km away';
        }
        refreshETA(opts.mechLat, opts.mechLng);

        const btn = document.getElementById(opts.btnId);
        if (btn) btn.addEventListener('click', function () {
            if (!navigator.geolocation) {
                window.toast('Geolocation not available.', 'error');
                return;
            }
            btn.disabled = true;
            btn.textContent = 'Locating…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                const lat = pos.coords.latitude, lng = pos.coords.longitude;
                const fd = new FormData();
                fd.append('latitude', lat);
                fd.append('longitude', lng);
                fd.append('csrf', opts.csrf);
                fetch(opts.updateUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            mechMarker.setLatLng([lat, lng]);
                            if (line) map.removeLayer(line);
                            line = L.polyline([[lat, lng], [opts.driverLat, opts.driverLng]], { color: '#FF6B35', weight: 3, dashArray: '6 6' }).addTo(map);
                            map.fitBounds(line.getBounds(), { padding: [40, 40] });
                            refreshETA(lat, lng);
                            window.toast('Location updated.', 'success');
                        } else {
                            window.toast(data.error || 'Failed to update location.', 'error');
                        }
                    })
                    .catch(function () { window.toast('Network error.', 'error'); })
                    .finally(function () { btn.disabled = false; btn.textContent = 'Update my location'; });
            }, function (err) {
                window.toast('Location denied: ' + err.message, 'error');
                btn.disabled = false;
                btn.textContent = 'Update my location';
            }, { enableHighAccuracy: true, timeout: 10000 });
        });
    };

    // ---------- Map for find-services list ----------
    window.fsServicesMap = function (opts) {
        const map = L.map(opts.elId).setView([opts.lat, opts.lng], 7);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);
        L.marker([opts.lat, opts.lng]).addTo(map).bindPopup('You are here').openPopup();
        (opts.markers || []).forEach(function (m) {
            const ll = [parseFloat(m.latitude), parseFloat(m.longitude)];
            const icon = L.divIcon({
                html: '<div style="background:#FF6B35;color:#fff;padding:4px 8px;border-radius:6px;font-size:.78rem;font-weight:700;">M' + m.id + '</div>',
                className: 'fs-pin', iconSize: [40,24]
            });
            L.marker(ll, { icon: icon })
                .addTo(map)
                .bindPopup('<strong>' + m.business_name + '</strong><br>' + m.town);
        });
    };
})();
