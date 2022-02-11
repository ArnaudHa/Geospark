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

const checkMuseum = document.getElementById('Museum');

let searchRequestPending = false;
let searchCurrentTerm = "";

let popup = L.popup();



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

let searchCity = () => {
    const query = searchInput.value;

    if (query.length < 3 || searchRequestPending)
        return;

    searchRequestPending = true;
    searchCurrentTerm = query;
    resultList.innerHTML = '' +
        '<div class="spinner-grow" style="width: 3rem; height: 3rem;" role="status">\n' +
        '  <span class="visually-hidden">Loading...</span>\n' +
        '</div>';

    axios.get('/api/search/' + query)
        .then(response => {
            setResultList(response.data.items);
        }).finally(() => {
            searchRequestPending = false;
            if (searchCurrentTerm !== searchInput.value)
                searchCity();
        });
}

let setMuseumsList = (museums) => {
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

        currentMarkers.push(new L.marker(position).addTo(map).bindPopup("<div>" + museum.label + "</div>" + "<img heigth='200px' width='200px' src=\'" + museum.picture + "\'/>"));

        button.innerHTML = museum.label;
        resultList.appendChild(button);
    }
}


let getMuseums = () => {
    if (checkMuseum.checked === true) {
        document.getElementById('result-list').innerHTML = '';

        axios.get("http://localhost/api/museums/" + ville)
            .then((response) => {
                setMuseumsList(response.data.items);
            })
    }
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

map.on('click', onMapClick);

searchInput.addEventListener('input', (e) => {
    searchCity();
});

checkMuseum.addEventListener('click', () => {
    getMuseums();
})

document.getElementById('go-back').onclick = function(e) {
    searchCity();
};