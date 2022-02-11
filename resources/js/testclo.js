/**************************/
// VARIABLES & CONSTANTS
/**************************/

let lat = 48.852969;
let lon = 2.349903;
let map = null;
let ville = "";

const searchInput = document.getElementById('search');
const resultList = document.getElementById('result-list');
const mapContainer = document.getElementById('map');
const currentMarkers = [];
const currentLayers = [];

let layersSwitches = document.getElementsByClassName('layers-switch');
const layersList = document.getElementById('layers-list');

let searchRequestPending = false;
let searchCurrentTerm = "";

let popup = L.popup();

const controller = new AbortController();



/**************************/
// FUNCTIONS
/**************************/

let initMap = () => {

    map = L.map('map')
        .setView([lat, lon], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        attribution: 'GEOSPARK',
        minZoom: 1,
        maxZoom: 20
    }).addTo(map);
}

let setResultList = (parsedResult) => {

    resultList.innerHTML = "";

    for (const marker of currentMarkers) {
        map.removeLayer(marker);
    }

    map.flyTo(new L.LatLng(20.13847, 1.40625), 2);

    for (let key in parsedResult) {
        if (!parsedResult.hasOwnProperty(key))
            continue;

        const result = parsedResult[key];

        const button = document.createElement('button');
        button.classList.add('list-group-item', 'list-group-item-action');
        button.innerHTML = result.city + ', ' + result.zipCode + ', ' + result.country;
        resultList.appendChild(button);

        const position = new L.LatLng(result.coordinates.y, result.coordinates.x);

        button.addEventListener('click', (event) => {
            ville = result.city;
            map.flyTo(position, 10);
        });

        currentMarkers.push(new L.marker(position).addTo(map));
    }
}

let requestPending = false;

let searchCity = () => {
    const query = searchInput.value;

    if (query.length < 3 || requestPending)
        return;

    requestPending = true;

    searchCurrentTerm = query;
    resultList.innerHTML = '' +
        '<div class="spinner-grow" style="width: 3rem; height: 3rem;" role="status">\n' +
        '  <span class="visually-hidden">Loading...</span>\n' +
        '</div>';

    axios.get('/api/search/city/' + query, {
        signal: controller.signal,
    })
        .then(response => {
            if(typeof response.data.error !== 'undefined' && searchCurrentTerm === searchInput.value)
                searchCity();
            setResultList(response.data.items);
        }).finally(() => {
            requestPending = false;
            if (searchCurrentTerm !== searchInput.value)
                searchCity();
        });
}

let setMuseumsList = (museums) => {

    document.getElementById('go-back').removeAttribute('hidden');

    for (const marker of currentMarkers) {
        map.removeLayer(marker);
    }

    for (let key in museums) {
        if (!museums.hasOwnProperty(key))
            continue;

        let museum = museums[key];

        const button = document.createElement('button');
        button.classList.add('list-group-item', 'list-group-item-action');

        const position = new L.LatLng(museum.coordinates.y, museum.coordinates.x);

        currentMarkers.push(new L.marker(position).addTo(map));

        button.innerHTML = '<i class="fa-solid '+ museum.icon +' mr-2"></i>' + museum.label;
        resultList.appendChild(button);
    }
}

let initLayersChecks = () => {

    axios.get('/api/layers')
        .then(response => {

            let layers = response.data.layers;
            layersList.innerHTML = "";

            for(let key in layers) {
                if(!layers.hasOwnProperty(key))
                    continue;

                let layer = layers[key];

                layersList.innerHTML += '' +
                    '<div class="px-5">\n' +
                    '   <div class="form-check form-switch">\n' +
                    '      <input class="form-check-input layers-switch" data-layer="'+layer.label+'" type="checkbox" role="switch" id="layers-switch-'+layer.label+'">\n' +
                    '      <label class="form-check-label" for="layers-switch-'+layer.label+'">\n' +
                    '        <i class="fa-solid '+layer.icon+' ml-3 mr-2"></i> '+layer.name+'\n' +
                    '      </label>\n' +
                    '   </div>\n' +
                    '</div>';
            }

            layersSwitches = document.getElementsByClassName('layers-switch');

            for(let key in layersSwitches) {
                if(!layersSwitches.hasOwnProperty(key))
                    continue;

                layersSwitches[key].addEventListener('change', (e) => {
                    if(e.target.checked) {
                        currentLayers.push(e.target.dataset.layer)
                    } else {
                        currentLayers.splice(currentLayers.indexOf(e.target.dataset.layer), 1);
                    }

                    getPoi();
                });
            }
        });
}


let getPoi = () => {
    document.getElementById('result-list').innerHTML = '';

    axios.get("/api/search/poi/" + ville + '?layers='+currentLayers.join(','))
        .then((response) => {
            setMuseumsList(response.data.items);
        });
}

let onMapClick = (e) => {
    popup
        .setLatLng(e.latlng)
        .setContent("Coordonnées : " + e.latlng.toString())
        .openOn(map);
}



/**************************/
// LISTENERS
/**************************/

initMap();
initLayersChecks();

map.on('click', onMapClick);

searchInput.addEventListener('input', (e) => {
    searchCity();
});

document.getElementById('go-back').onclick = function(e) {
    e.target.setAttribute('hidden', true);
    searchCity();
};
/*===================================================
                    OSM  LAYER
===================================================*/
/*
    var osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    });
    osm.addTo(macarte);
*/
