const { default: axios } = require("axios");
const { result } = require("lodash");

// Données de départ (Paris)
var lat = 48.852969;
var lon = 2.349903;
var macarte = null;
var ville = "";


function initMap() {
    macarte = L.map('map').setView([lat, lon], 11);
    // Récupération des données sur openstreetmap
    L.tileLayer('https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        // Source de données et taille du zoom
        attribution: 'données © <a href="//osm.org/copyright">OpenStreetMap</a>/ODbL - rendu © <a href="//openstreetmap.fr">OSM France</a>',
        minZoom: 1,
        maxZoom: 20
    }).addTo(macarte);
}

window.onload = function() {
    initMap();

    var popup = L.popup();

    function onMapClick(e) {
        popup
            .setLatLng(e.latlng)
            .setContent("You clicked the map at " + e.latlng.toString())
            .openOn(macarte);
    }

    macarte.on('click', onMapClick);

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
/*===================================================
                     TILE LAYER               
===================================================*/
/*
   // Google Map Layer

   googleStreets = L.tileLayer('http://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
       maxZoom: 20,
       subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
   });
   googleStreets.addTo(macarte);
  
               // Satelite Layer
               googleSat = L.tileLayer('http://{s}.google.com/vt/lyrs=s&x={x}&y={y}&z={z}', {
                   maxZoom: 20,
                   subdomains: ['mt0', 'mt1', 'mt2', 'mt3']
               });
               googleSat.addTo(macarte);

               var Stamen_Watercolor = L.tileLayer('https://stamen-tiles-{s}.a.ssl.fastly.net/watercolor/{z}/{x}/{y}.{ext}', {
                   attribution: 'Map tiles by <a href="http://stamen.com">Stamen Design</a>, <a href="http://creativecommons.org/licenses/by/3.0">CC BY 3.0</a> &mdash; Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
                   subdomains: 'abcd',
                   minZoom: 1,
                   maxZoom: 16,
                   ext: 'jpg'
               });
               Stamen_Watercolor.addTo(macarte);
           */


const searchInput = document.getElementById('search');
const resultList = document.getElementById('result-list');
const mapContainer = document.getElementById('map');
const currentMarkers = [];



searchInput.addEventListener('input', (e) => {
    const query = searchInput.value;
    fetch('https://nominatim.openstreetmap.org/search?format=json&polygon=1&addressdetails=1&q=' + query)
        .then(result => result.json())
        .then(parsedResult => {
            setResultList(parsedResult);
        });
});


function setResultList(parsedResult) {
    resultList.innerHTML = "";
    for (const marker of currentMarkers) {
        macarte.removeLayer(marker);
    }
    macarte.flyTo(new L.LatLng(20.13847, 1.40625), 2);
    for (const result of parsedResult) {
        const button = document.createElement('button');
        button.classList.add('list-group-item', 'list-group-item-action');
        button.innerHTML = JSON.stringify({
            displayName: result.display_name,
            lat: result.lat,
            lon: result.lon
        }, undefined, 2);


        button.addEventListener('click', (event) => {
            for (const child of resultList.children) {
                child.classList.remove('active');
            }
            event.target.classList.add('active');
            const clickedData = JSON.parse(event.target.innerHTML);
            ville = clickedData.displayName.split(',')[0].toString();
            const position = new L.LatLng(clickedData.lat, clickedData.lon);
            macarte.flyTo(position, 10);
        })
        const position = new L.LatLng(result.lat, result.lon);
        currentMarkers.push(new L.marker(position).addTo(macarte));
        resultList.appendChild(button);
    }

}


const checkMuseum = document.getElementById('Museum');

checkMuseum.addEventListener('click', () => {
        if (checkMuseum.checked == true) {
            document.getElementById('result-list').innerHTML = '';

            axios.get("http://localhost/api/museums/" + ville)
                .then((response) => {
                    console.log(response)

                    for (const result in response) {
                        const button = document.createElement('button');
                        button.classList.add('list-group-item', 'list-group-item-action');
                        button.innerHTML = JSON.stringify({
                            result: result.label,
                            lat: result.x,
                            lon: result.y
                        }, undefined, 2);
                    }
                })
        }
    })
    /*
    checkMuseum.addEventListener('click', () => {
        if (checkMuseum.checked == true) {

            document.getElementById('result-list').innerHTML = '';

            axios.get("http://localhost/api/museums/" + ville)
                .then(result => result.json())
                .then(parsedResult => {
                    setResultList(parsedResult);

                    console.log(parsedResult)
                    for (const response of parsedResult) {
                        const button = document.createElement('button');
                        button.classList.add('list-group-item', 'list-group-item-action');
                        button.innerHTML = JSON.stringify({
                            label: response.label
                        }, undefined, 2);
                    }
                })
        }
    })
    */