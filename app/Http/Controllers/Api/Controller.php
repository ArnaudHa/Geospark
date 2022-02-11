<?php

namespace App\Http\Controllers\Api;

use EasyRdf\Graph;
use EasyRdf\Http;
use EasyRdf\Sparql\Client;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use MadBob\EasyRDFonGuzzle\HttpClient;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function getTypes()
    {
        $client = new HttpClient();
        Http::setDefaultHttpClient($client);

        $query = 'SELECT ?item ?itemLabel ?coordinates ?picture
                WHERE
                {
                  ?item wdt:P31/wdt:P279* wd:Q33506 .
                  ?item wdt:P131 wd:Q6602 .
                  ?item wdt:P625 ?coordinates .
                  ?item wdt:P18 ?picture .
                  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr" }
                }';

        $clientSPARQL = new Client("https://query.wikidata.org/sparql");
        $results = $clientSPARQL->query($query);

        $types = new Collection();

        foreach ($results as $result) {
            $types->add([
                'uri' => $result->class->getUri(),
                'name' => $result->class->localName(),
                'label' => $result->classLabel->getValue(),
            ]);
        }

        return response()->json([
            'types' => $types,
        ]);
    }

    public function req($query)
    {
        $client = new HttpClient();
        Http::setDefaultHttpClient($client);
        $clientSPARQL = new Client("https://query.wikidata.org/sparql");
        return $clientSPARQL->query($query);
    }

    public function getResId($city)
    {
        $response = \Illuminate\Support\Facades\Http::get('https://fr.wikipedia.org/w/api.php?action=query&prop=pageprops&titles='.$city.'&format=json');
        $pages = $response->json()['query']['pages'];

        if(count($pages) !== 0) {
            return $pages[array_key_first($pages)]['pageprops']['wikibase_item'];
        }

        return null;
    }

    public function getMuseums(Request $request, $city)
    {
        //$city = 'wd:Q6602';
        $cityId = $this->getResId($city);

        if($cityId === null) {
            return response()->json([
                'error' => 'City not found'
            ])->status(404);
        }

        $query = 'SELECT ?item ?itemLabel ?coordinates ?picture
                WHERE
                {
                  ?item wdt:P31/wdt:P279* wd:Q33506 .
                  ?item wdt:P131 wd:' . $cityId . ' .
                  ?item wdt:P625 ?coordinates .
                  ?item wdt:P18 ?picture .
                  SERVICE wikibase:label { bd:serviceParam wikibase:language "fr" }
                } LIMIT 300';

        $results = $this->req($query);

        $items = new Collection();

        foreach ($results as $result) {
            $split = explode(' ', $result->coordinates->getValue());

            $items->add([
                'label' => Str::ucfirst($result->itemLabel->getValue()),
                'coordinates' => [
                    'x' => str_replace('Point(', '', $split[0]),
                    'y' => str_replace(')', '', $split[1]),
                ],
                'picture' => $result->picture->getUri(),
            ]);
        }

        return response()->json([
            'items' => $items,
        ]);
    }

    public function search(Request $request, $term)
    {
        $query = 'SELECT DISTINCT ?item ?cityName ?cityCoordinates ?zipCode ?countryName ?cityDescription
                    WHERE
                    {

                      ?item wdt:P31/wdt:P279* wd:Q515 ;
                            wdt:P1448 ?cityName ;
                            wdt:P625 ?cityCoordinates .

                      OPTIONAL {
                        ?item wdt:P17 ?country .
                        ?country wdt:P1448 ?countryName .
                        FILTER (LANG(?countryName) IN ("fr"))
                      }

                      OPTIONAL {
                        ?item schema:description ?cityDescription .
                        FILTER (LANG(?cityDescription) IN ("fr"))
                      }

                      OPTIONAL {
                        ?item wdt:P281 ?zipCode .
                      }

                      FILTER ( REGEX(?cityName, "'.$term.'") )

                    } LIMIT 20';

        $results = $this->req($query);

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
}
