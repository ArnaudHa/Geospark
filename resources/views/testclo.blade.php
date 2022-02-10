<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.3.1/dist/leaflet.css" integrity="sha512-Rksm5RenBEKSKFjgI3a41vrjkw4EVPlJ3+OiI65vTjIdo9brlAacEuKOiQ5OFh7cOI1bkDwLqdLw3Zg0cRJAAQ==" crossorigin="" />
        <link rel="stylesheet" href="./css/app.css">
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
        <title>GeoSpark</title>
    </head>
    <body>

<main>
  <header class="py-3 mb-3 border-bottom">
  <div class="container-fluid d-grid gap-3 align-items-center" style="grid-template-columns: 1fr 2fr;">
    
    <div class="geospark"> GEOSPARK </div>

      <div class="d-flex align-items-center">
        <form class="w-100 me-3">
          <input id="search" type="text"  class="form-control" placeholder="Search for an adress ..." aria-label="Search">
        </form>
    </div>
  </header>

  <div class="container-fluid pb-3">
    <div class="d-grid gap-3" style="grid-template-columns: 1fr 2fr;">
      <div class="bg-light border rounded-3">
        <div style="grid-template-columns: 1fr 2fr;">
          <label for="Accept">
            <input type="checkbox" id="Museum" name="Accept" value="yes"> Museum
          </label>
         </div>
          <div id="result-list" class="list-group"> </div>
          <div id="city-result"> </div>
      </div>
      <div class="bg-light border rounded-3">
            <div id="map"></div>
      </div>
    </div>
  </div>

</main>

    <script src="https://unpkg.com/leaflet@1.3.1/dist/leaflet.js" integrity="sha512-/Nsx9X4HebavoBvEBuyp3I7od5tA0UzAxs+j83KgC8PU0kgB4XiK4Lfe4y4cgBtaRJQEIFCW+oC506aPT2L1zw==" crossorigin=""></script>
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
    <script src="./js/app.js"></script>
    </body>

</html>