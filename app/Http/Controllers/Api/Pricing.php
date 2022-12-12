<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Product;
use Illuminate\Http\Request;

class Pricing extends Controller
{
    public static function index(Request $request)
    {
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
    }
}
