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
        $client = new \GuzzleHttp\Client();
        $response = \Illuminate\Support\Facades\Http::get('https://fr.wikipedia.org/w/api.php?action=query&prop=pageprops&titles='.$city.'&format=json');
        $pages = $response->json()['query']['pages'];
        return $pages[array_key_first($pages)]['pageprops']['wikibase_item'];
    }

    public function getMuseums(Request $request, $city)
    {
        //$city = 'wd:Q6602';
        $cityId = $this->getResId($city);

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
}
