<?php

namespace App\Http\Controllers\Api;

use App\Classes\Pterodactyl;
use App\Http\Controllers\Controller;
use App\Models\Egg;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Product;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class Pricing extends Controller
{
    public static function index(Request $request)
    {
        if(!($request->token == env('HOME_API_KEY'))) return response()->json(['message' => 'Unauthorized - wrong token.'], 401);
        $pricing = Cache::remember('pricing', 3600, function(){
            $output = array();
            $products = Product::where('disabled', false)->orderBy('price')->get();
            $nests = Nest::get();
            foreach($products as $product)
            {
                $eggs = $product->eggs()->get();
                $linkedNodes = $product->nodes()->get();
                foreach($linkedNodes as $linkedNode)
                {
                    if(!isset($output[$linkedNode['id']]))$output[$linkedNode['id']] = [ 'name' => $linkedNode['name']];
                    foreach($eggs as $egg)
                    {
                        $nest = $nests->where('id', $egg['nest_id'])->first();
                        if(!isset($output[$linkedNode['id']][$nest['id']]))$output[$linkedNode['id']][$nest['id']] = [
                            'name' => $nest['name'],
                            'description' => $nest['description'],
                        ];
                        if(!isset($output[$linkedNode['id']][$nest['id']][$egg['id']]))$output[$linkedNode['id']][$nest['id']][$egg['id']] = [
                            'name' => $egg['name'],
                            'description' => $egg['description'],
                        ];
                        $output[$linkedNode['id']][$nest['id']][$egg['id']][$product['id']] = [
                            'name' => $product['name'],
                            'description' => $product['description'],
                            'price' => $product['price'],
                            'memory' => $product['memory'],
                            'cpu' => $product['cpu'],
                            'disk' => $product['disk'],
                            'databases' => $product['databases'],
                            'backups' => $product['backups'],
                            'allocations' => $product['allocations'],
                            'on_sale' => $product['on_sale'],
                            'max_servers_per_user' => $product['max_servers_per_user'],
                            'custom_ribbon_text' => $product['custom_ribbon_text'],
                        ];
                    }
                }
            }
            ksort($output);
            return $output;
        });
        return $pricing;
    }
    public static function favourites(Request $request)
    {
        if(!($request->token == env('HOME_API_KEY'))) return response()->json(['message' => 'Unauthorized - wrong token.'], 401);
        $favourites = Cache::remember('favourites', 3600, function(){
            $servers = Server::get();
            $pteroServers = Pterodactyl::getServers();
            $CPProducts = Product::get();
            
            $midSave = array();
            foreach($pteroServers as $pteroServer)//Get basic info for each product´s usage
            {
                if($pteroServer['attributes']['nest'] == 1 && $pteroServer['attributes']['egg'] != 1)
                {
                    $server = $servers->where('pterodactyl_id', $pteroServer['attributes']['id'])->first();
                    if(!isset($server)) continue;
                    $productId = $servers->where('pterodactyl_id', $pteroServer['attributes']['id'])->first()['product_id'];
                    if(!isset($midSave[$productId])) $midSave[$productId] = [
                        'count' => 0,
                        'eggs' => array()
                    ];
                    $midSave[$productId]['count']++;
                    if(!isset($midSave[$productId]['eggs'][$pteroServer['attributes']['egg']])) $midSave[$productId]['eggs'][$pteroServer['attributes']['egg']] = [
                        'count' => 0,
                        'nodes' => array()
                    ];
                    $midSave[$productId]['eggs'][$pteroServer['attributes']['egg']]['count']++;
                    if(!isset($midSave[$productId]['eggs'][$pteroServer['attributes']['egg']]['nodes'][$pteroServer['attributes']['node']])) $midSave[$productId]['eggs'][$pteroServer['attributes']['egg']]['nodes'][$pteroServer['attributes']['node']] = 0;
                    $midSave[$productId]['eggs'][$pteroServer['attributes']['egg']]['nodes'][$pteroServer['attributes']['node']]++;
                }

            }
            foreach($CPProducts as $product)//If a product is on sale, make it stand out, even if it wasn´t used
            {
                if($product['on_sale'])
                {
                    if(isset($midSave[$product['id']])) $midSave[$product['id']]['count'] += 1000;
                    else{
                        $egg = $product->eggs()->first();
                        $node = $product->nodes()->first();
                        if($egg['nest_id']==1) $egg = Egg::where('id', 3)->first();
                        
                        $midSave[$product['id']] = [
                            'count' => 1000,
                            'eggs' => array()
                        ];
                        $midSave[$product['id']]['eggs'][$egg['id']] = [
                            'count' => 0,
                            'nodes' => array()
                        ];
                        $midSave[$product['id']]['eggs'][$egg['id']]['nodes'][$node['id']] = 0;
                    }
                }
            }
            $midSave = (collect($midSave)->sortByDesc('count'))->toArray();//Sort by all nested counters
            foreach($midSave as $key => $product)
            {
                $midSave[$key]['eggs'] = (collect($midSave[$key]['eggs'])->sortByDesc('count'))->toArray();
                foreach($midSave[$key]['eggs'] as $eggId => $egg)
                {
                    arsort($midSave[$key]['eggs'][$eggId]['nodes']);
                }
            }
            $output = array();
            foreach(array_slice($midSave, 0, 3) as $key => $product)//Keep only three packages, structure the final output array and get the nescesarry information for each product
            {
                $productDetails = $CPProducts->where('id', $key)->first();
                $commonEggId = collect(array_keys(array_slice($product['eggs'], 0, 1, true)))->first();
                $commonEgg = Egg::where('id', $commonEggId)->first();
                $commonNode = Node::where('id', collect(array_keys(array_slice($product['eggs'][$commonEggId]['nodes'], 0, 1, true)))->first())->first();
                $output[$key] = [
                    'name' => $productDetails['name'],
                    'description' => $productDetails['description'],
                    'price' => $productDetails['price'],
                    'memory' => $productDetails['memory'],
                    'cpu' => $productDetails['cpu'],
                    'disk' => $productDetails['disk'],
                    'databases' => $productDetails['databases'],
                    'backups' => $productDetails['backups'],
                    'allocations' => $productDetails['allocations'],
                    'on_sale' => $productDetails['on_sale'],
                    'max_servers_per_user' => $productDetails['max_servers_per_user'],
                    'custom_ribbon_text' => $productDetails['custom_ribbon_text'],
                    'node' => [
                        'id' => $commonNode['id'],
                        'name' => $commonNode['name'],
                        'description' => $commonNode['description']
                    ],
                    'common' => [
                        'egg' => [
                            'id' => $commonEgg['id'],
                            'name' => $commonEgg['name'],
                            'description' => $commonEgg['description']
                        ]
                    ]
                ];
            }
            $output = (collect($output)->sortBy('price'))->toArray();//Sort the final three packages from cheapest
            return $output;
        });
        return $favourites;
    }
}
