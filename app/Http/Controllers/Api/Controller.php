<?php

namespace App\Http\Controllers\Api;

use EasyRdf\Graph;
use EasyRdf\Http;
use EasyRdf\Sparql\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MadBob\EasyRDFonGuzzle\HttpClient;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function req($query)
    {
        try {
            $client = new HttpClient();
            Http::setDefaultHttpClient($client);
            $clientSPARQL = new Client("https://query.wikidata.org/sparql");
            return $clientSPARQL->query($query);
        } catch (\Exception $exception) {
            return null;
        }
    }

    public function getResId($city)
    {
        $response = \Illuminate\Support\Facades\Http::get('https://fr.wikipedia.org/w/api.php?action=query&prop=pageprops&titles='.$city.'&format=json');
        $pages = $response->json()['query']['pages'];

        if(count($pages) !== 0) {
            return $pages[array_key_first($pages)]['pageprops']['wikibase_item'] ?? null;
        }

        return null;
    }

    public function searchCity($term)
    {
        $query = 'SELECT DISTINCT ?item ?cityName ?cityCoordinates ?cityDescription

                (MIN(DISTINCT ?countryName_pre) as ?countryName)
                (MIN(DISTINCT ?zipCode_pre) as ?zipCode)

                WHERE
                {
                  {
                    ?item wdt:P31/wdt:P279* wd:Q484170 .
                  } UNION {
                    ?item wdt:P31/wdt:P279* wd:Q5119 .
                  } UNION {
                    ?item wdt:P31/wdt:P279* wd:Q515 .
                  } UNION {
                    ?item wdt:P31/wdt:P279* wd:Q15284 .
                  }

                  ?item wdt:P1448 ?cityName ;
                        wdt:P625 ?cityCoordinates .

                  OPTIONAL {
                    ?item wdt:P17 ?country .
                    ?country wdt:P1448 ?countryName_pre .
                    FILTER (LANG(?countryName_pre) IN ("fr"))
                  }

                  OPTIONAL {
                    ?item schema:description ?cityDescription .
                    FILTER (LANG(?cityDescription) IN (\'fr\'))
                  }

                  OPTIONAL {
                    ?item wdt:P281 ?zipCode_pre .
                  }

                  FILTER ( regex(?cityName, "'.$term.'") )

                } GROUP BY ?item ?cityName ?cityCoordinates ?cityDescription LIMIT 20';

        $results = $this->req($query);

        if($results === null) {
            return response()->json([
                'error' => 'Request error, please retry...',
            ]);
        }

        $items = new Collection();

        foreach ($results as $result) {
            $split = explode(' ', $result->cityCoordinates->getValue());

            $items->add([
                'city' => Str::ucfirst($result->cityName->getValue()),
                'zipCode' => $result->zipCode->getValue(),
                'country' => $result->countryName->getValue(),
                'coordinates' => [
                    'x' => str_replace('Point(', '', $split[0]),
                    'y' => str_replace(')', '', $split[1]),
                ]
            ]);
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    public function searchPoi(Request $request, $city)
    {

        $referenceLayers = [
            'museum' => [
                'classes' => [ 'wd:Q33506' ],
            ],
            'park' => [
                'classes' => [ 'wd:Q22698' ],
            ],
            'monument' => [
                'classes' => [ 'wd:Q4989906' ],
            ]
        ];

        $reverse = [
            'Q33506' => 'fa-building-columns',
            'Q22698' => 'fa-tree',
            'Q4989906' => 'fa-archway',
        ];

        $cityId = $this->getResId($city);

        if($cityId === null) {
            return response()->json([
                'error' => 'City not found'
            ])->status(404);
        }

        $layers = explode( ',', $request->input('layers'));

        if(count($layers) === 0) {
            return response()->json([
                'error' => 'No layers...',
            ]);
        }

        $layersToQuery = '{';
        $layersToQueryFilter = '(';

        foreach ($layers as $layer) {
            $layersToQuery .= ' ' . implode(' ', $referenceLayers[$layer]['classes']);
            //$layersToQueryFilter .= ' ' . implode(" , ", $referenceLayers[$layer]['classes']);

            // implode a decidé de plus fonctionner
            foreach ($referenceLayers[$layer]['classes'] as $elem) {
                $layersToQueryFilter .= $elem . ',';
            }
        }

        $layersToQuery .= ' }';
        $layersToQueryFilter .= ')';

        $layersToQueryFilter = str_replace(',)', ')', $layersToQueryFilter);


        $query = 'SELECT ?type ?item ?itemLabel ?coordinates ?picture
                WHERE
                {
                  VALUES ?layers '. $layersToQuery .'

                  ?item wdt:P31/wdt:P279* ?layers .
                  ?item wdt:P131 wd:' . $cityId . ' .
                  ?item wdt:P625 ?coordinates .
                  ?item wdt:P18 ?picture .

                  {
                    ?item wdt:P31/wdt:P279* ?type .
                    FILTER(?type IN '.$layersToQueryFilter.')
                  }

                  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr" }
                } LIMIT ' . count($layers) * 150;

        $results = $this->req($query);

        $items = new Collection();

        foreach ($results as $result) {
            $split = explode(' ', $result->coordinates->getValue());

            $entityId = explode('/', $result->type->getUri());
            $entityId = $entityId[count($entityId) - 1];

            $items->add([
                'label' => Str::ucfirst($result->itemLabel->getValue()),
                'coordinates' => [
                    'x' => str_replace('Point(', '', $split[0]),
                    'y' => str_replace(')', '', $split[1]),
                ],
                'picture' => $result->picture->getUri(),
                'icon' => $reverse[$entityId],
            ]);
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    public function getLayers()
    {
        return response()->json([
            'layers' => [
                [
                    'label' => 'museum',
                    'name' => 'Musées',
                    'icon' => 'fa-building-columns'
                ],
                [
                    'label' => 'park',
                    'name' => 'Parcs',
                    'icon' => 'fa-tree'
                ],
                [
                    'label' => 'monument',
                    'name' => 'Monuments',
                    'icon' => 'fa-archway'
                ],
            ],
        ]);
    }
}

