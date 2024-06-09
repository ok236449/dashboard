<?php

namespace App\Http\Controllers;

use App\Classes\Convoy;
use App\Classes\Pterodactyl;
use App\Models\Domain;
use App\Models\Egg;
use App\Models\Location;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Product;
use App\Models\Server;
use App\Models\VirtualPrivateServer;
use App\Notifications\ServerCreationError;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Qirolab\Theme\Theme;

class ServerController extends Controller
{
    /** Display a listing of the resource. */
    public function index()
    {
        $servers = Auth::user()->servers->sortBy('created_at');

        //Get and set server infos each server
        foreach ($servers as $server) {

            //Get server infos from ptero
            $serverAttributes = Pterodactyl::getServerAttributes($server->pterodactyl_id); //remove delete on 404
            if (! $serverAttributes) {
                continue;
            }
            $serverRelationships = $serverAttributes['relationships'];
            $serverLocationAttributes = $serverRelationships['location']['attributes'];

            //Set server infos
            $server->location = $serverLocationAttributes['long'] ?
                $serverLocationAttributes['long'] :
                $serverLocationAttributes['short'];

            $server->egg = $serverRelationships['egg']['attributes']['name'];
            $server->nest = $serverRelationships['nest']['attributes']['name'];

            $server->node = $serverRelationships['node']['attributes']['name'];

            //Check if a server got renamed on Pterodactyl
            $savedServer = Server::query()->where('id', $server->id)->first();
            if ($savedServer->name != $serverAttributes['name']) {
                $savedServer->name = $serverAttributes['name'];
                $server->name = $serverAttributes['name'];
                $savedServer->save();
            }
            //get productname by product_id for server
            $product = Product::find($server->product_id);

            $server->product = $product;
        }

        $vpses = VirtualPrivateServer::where('user_id', Auth::user()->id)->get();
        
        foreach($vpses as $vps_key => $vps){
            $details = Convoy::fetchServer($vps->uuid);
            if(isset($details['data'])) {
                $addresses = array();
                foreach ($details['data']['limits']['addresses']['ipv4'] as $key => $address){
                    array_push($addresses, $address['address']);
                }
                $details['data']['limits']['addresses']['ipv4'] = $addresses;
                $vps->details = $details['data'];
            }
            //$convoy_node = Convoy::fetchNode($vps->details['node_id']);
            //if(isset($convoy_node['data'])) $vps->node = $convoy_node['data'];
            $vps->next_payment = $vps->last_payment?Carbon::createFromTimeString($vps->last_payment)->addDays(30):__("never");
        }

        //dd($vpses);

        return view('servers.index')->with([
            'servers' => $servers,
            'vpses' => $vpses
        ]);
    }

    /** Show the form for creating a new resource. */
    public function create(Request $request)
    {
        if (! is_null($this->validateConfigurationRules())) {
            return $this->validateConfigurationRules();
        }

        $productCount = Product::query()->where('disabled', '=', false)->count();
        $locations = Location::all();

        $nodeCount = Node::query()
            ->whereHas('products', function (Builder $builder) {
                $builder->where('disabled', '=', false);
            })->count();

        $eggs = Egg::query()
            ->whereHas('products', function (Builder $builder) {
                $builder->where('disabled', '=', false);
            })->get();

        $nests = Nest::query()
            ->whereHas('eggs', function (Builder $builder) {
                $builder->whereHas('products', function (Builder $builder) {
                    $builder->where('disabled', '=', false);
                });
            })->get();

            return view('servers.create')->with([
                'productCount' => $productCount,
                'nodeCount'    => $nodeCount,
                'nests'        => $nests,
                'locations'    => $locations,
                'eggs'         => $eggs,
                'user'         => Auth::user(),
                'preNest'    => $request->nest?$request->nest:null,
                'preEgg'    => $request->egg?$request->egg:null,
                'preNode'    => $request->node?$request->node:null
            ]);
    }

    /**
     * @return null|RedirectResponse
     */
    private function validateConfigurationRules()
    {
        //limit validation
        if (Auth::user()->servers()->count() >= Auth::user()->server_limit) {
            return redirect()->route('servers.index')->with('error', __('Server limit reached!'));
        }

        // minimum credits && Check for Allocation
        if (FacadesRequest::has('product')) {
            $product = Product::findOrFail(FacadesRequest::input('product'));

            // Get node resource allocation info
            $node = $product->nodes()->findOrFail(FacadesRequest::input('node'));
            $nodeName = $node->name;

            // Check if node has enough memory and disk space
            $checkResponse = Pterodactyl::checkNodeResources($node, $product->memory, $product->disk);
            if ($checkResponse == false) {
                return redirect()->route('servers.index')->with('error', __("The node '".$nodeName."' doesn't have the required memory or disk left to allocate this product."));
            }

            // Min. Credits
            if (
                Auth::user()->credits <
                ($product->minimum_credits == -1
                    ? config('SETTINGS::USER:MINIMUM_REQUIRED_CREDITS_TO_MAKE_SERVER', 50)
                    : $product->minimum_credits)
            ) {
                return redirect()->route('servers.index')->with('error', 'You do not have the required amount of '.CREDITS_DISPLAY_NAME.' to use this product!');
            }
        }

        //Required Verification for creating an server
        if (config('SETTINGS::USER:FORCE_EMAIL_VERIFICATION', 'false') === 'true' && ! Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('profile.index')->with('error', __('You are required to verify your email address before you can create a server.'));
        }

        //Required Verification for creating an server

        if (! config('SETTINGS::SYSTEM:CREATION_OF_NEW_SERVERS', 'true') && Auth::user()->role != 'admin') {
            return redirect()->route('servers.index')->with('error', __('The system administrator has blocked the creation of new servers.'));
        }

        //Required Verification for creating an server
        if (config('SETTINGS::USER:FORCE_DISCORD_VERIFICATION', 'false') === 'true' && ! Auth::user()->discordUser) {
            return redirect()->route('profile.index')->with('error', __('You are required to link your discord account before you can create a server.'));
        }

        return null;
    }

    /** Store a newly created resource in storage. */
    public function store(Request $request)
    {
        /** @var Node $node */
        /** @var Egg $egg */
        /** @var Product $product */
        if (! is_null($this->validateConfigurationRules())) {
            return $this->validateConfigurationRules();
        }

        $request->validate([
            'name' => 'required|max:191',
            'node' => 'required|exists:nodes,id',
            'egg' => 'required|exists:eggs,id',
            'product' => 'required|exists:products,id',
        ]);

        //get required resources
        $product = Product::query()->findOrFail($request->input('product'));
        $egg = $product->eggs()->findOrFail($request->input('egg'));
        $node = $product->nodes()->findOrFail($request->input('node'));

        $server = $request->user()->servers()->create([
            'name' => $request->input('name'),
            'product_id' => $request->input('product'),
        ]);

        //get free allocation ID
        $allocationId = Pterodactyl::getFreeAllocationId($node);
        if (! $allocationId) {
            return $this->noAllocationsError($server);
        }

        //create server on pterodactyl
        $response = Pterodactyl::createServer($server, $egg, $allocationId);
        if ($response->failed()) {
            return $this->serverCreationFailed($response, $server);
        }

        $serverAttributes = $response->json()['attributes'];
        //update server with pterodactyl_id
        $server->update([
            'pterodactyl_id' => $serverAttributes['id'],
            'identifier' => $serverAttributes['identifier'],
        ]);

        if (config('SETTINGS::SYSTEM:SERVER_CREATE_CHARGE_FIRST_HOUR', 'true') == 'true') {
            if ($request->user()->credits >= $server->product->getHourlyPrice()) {
                $request->user()->decrement('credits', $server->product->getHourlyPrice());
            }
        }

        return redirect()->route('servers.index')->with('success', __('Server created'));
    }

    /**
     * return redirect with error
     *
     * @param  Server  $server
     * @return RedirectResponse
     */
    private function noAllocationsError(Server $server)
    {
        $server->delete();

        Auth::user()->notify(new ServerCreationError($server));

        return redirect()->route('servers.index')->with('error', __('No allocations satisfying the requirements for automatic deployment on this node were found.'));
    }

    /**
     * return redirect with error
     *
     * @param  Response  $response
     * @param  Server  $server
     * @return RedirectResponse
     */
    private function serverCreationFailed(Response $response, Server $server)
    {
        $server->delete();

        return redirect()->route('servers.index')->with('error', json_encode($response->json()));
    }

    /** Remove the specified resource from storage. */
    public function destroy(Server $server)
    {
        try {
            $server->delete();

            return redirect()->route('servers.index')->with('success', __('Server removed'));
        } catch (Exception $e) {
            return redirect()->route('servers.index')->with('error', __('An exception has occurred while trying to remove a resource "').$e->getMessage().'"');
        }
    }

    /** Show Server Settings */
    public function show(Server $server)
    {
        if ($server->user_id != Auth::user()->id) {
            return back()->with('error', __('´This is not your Server!'));
        }
        $serverAttributes = Pterodactyl::getServerAttributes($server->pterodactyl_id);
        $serverRelationships = $serverAttributes['relationships'];
        $serverLocationAttributes = $serverRelationships['location']['attributes'];

        //Get server networking
        $address = "";
        $ports = array();
        $main_port = "";
        //$web_ports = array();
        foreach(Pterodactyl::getNetworking($server->identifier)['data'] as $network)
        {
            $ports[] = $network['attributes']['port'];
            if($network['attributes']['is_default'])
            {
                $address = $network['attributes']['ip_alias'];
                $main_port = $network['attributes']['port'];
            //    if(in_array($serverAttributes['nest'], [8, 12])) $web_ports[] = $network['attributes']['port'];
            }
            //else $web_ports[] = $network['attributes']['port'];
        }

        //Get current product
        $currentProduct = Product::where('id', $server->product_id)->first();

        //Set server infos
        $server->location = $serverLocationAttributes['long'] ?
            $serverLocationAttributes['long'] :
            $serverLocationAttributes['short'];

        $server->node = $serverRelationships['node']['attributes']['name'];
        $server->name = $serverAttributes['name'];
        $server->egg = $serverRelationships['egg']['attributes']['name'];

        $pteroNode = Pterodactyl::getNode($serverRelationships['node']['attributes']['id']);

        $products = Product::orderBy('created_at')
        ->whereHas('nodes', function (Builder $builder) use ($serverRelationships) { //Only show products for that node
            $builder->where('id', '=', $serverRelationships['node']['attributes']['id']);
        })
        ->get();

        // Set the each product eggs array to just contain the eggs name
        foreach ($products as $product) {
            $product->eggs = $product->eggs->pluck('name')->toArray();
            if ($product->memory - $currentProduct->memory > ($pteroNode['memory'] * ($pteroNode['memory_overallocate'] + 100) / 100) - $pteroNode['allocated_resources']['memory'] || $product->disk - $currentProduct->disk > ($pteroNode['disk'] * ($pteroNode['disk_overallocate'] + 100) / 100) - $pteroNode['allocated_resources']['disk']) {
                $product->doesNotFit = true;
            }
        }

        //Get all tabs as laravel view paths
        $tabs = [];
        if(file_exists(Theme::getViewPaths()[0] . '/servers/tabs/')){
            $tabspath = glob(Theme::getViewPaths()[0] . '/servers/tabs/*.blade.php');
        }else{
            $tabspath = glob(Theme::path($path = 'views', $themeName = 'default').'/servers/tabs/*.blade.php');
        }

          foreach ($tabspath as $filename) {
            $tabs[] = 'servers.tabs.'.basename($filename, '.blade.php');
        }
        //dd($tabs);

        //swap tabs
        switch($serverAttributes['egg']){
            case 1://minecraft eggs
            case 2:
            case 3:
            case 22:
            case 26:
            case 58:
                //show all tabs
            //    [$tabs[0], $tabs[3]] = [$tabs[3], $tabs[0]];
            //    [$tabs[0], $tabs[1]] = [$tabs[1], $tabs[0]];
                //[$tabListItems[0], $tabListItems[3]] = [$tabListItems[3], $tabListItems[0]];
                //[$tabListItems[0], $tabListItems[1]] = [$tabListItems[1], $tabListItems[0]];
                break;
            
            case 21://web eggs
            case 81:
            case 98:
            case 31://discord.js
            case 61://discord.py
            case 80: // fivem
            case 65: //vscode
                //show only web tabs
                /*foreach([0, 1, 2] as $i){
                    unset($tabs[$i]);
                //    unset($tabListItems[$i]);
                }*/
                unset($tabs[0]);//hide minecraft domains
                break;
        }


        //Generate a html list item for each tab based on tabs file basename, set first tab as active
        $tabListItems = [];
        foreach ($tabs as $tab) {
            $tabName = str_replace('servers.tabs.', '', $tab);
            $tabListItems[] = '<li class="nav-item">
            <a class="nav-link '.(!$tabListItems == 1 ? 'active' : '').'" data-toggle="pill" href="#'.$tabName.'">
            '.__(ucfirst($tabName)).'
            </a></li>';
        }

        
        

        //dd($tabs);
        /*$temp = $tabs[1];
        $tabs[1] = $tabs[2];
        $tabs[2] = $temp;*/
        //$tabs = array('servers.tabs.domains', 'servers.tabs.protection', 'servers.tabs.lobby');
        /*[$tabs[1], $tabs[2]] = [$tabs[2], $tabs[1]];
        [$tabListItems[1], $tabListItems[2]] = [$tabListItems[2], $tabListItems[1]];*/
        //dd($serverAttributes);


        /*if(!in_array($serverAttributes['egg'], [3, 22, 58])){
            unset($tabs[1], $tabs[2], $tabListItems[1], $tabListItems[2]);
        }*/

        $themes = array_diff(scandir(base_path('themes')), array('..', '.'));

        $domains = Domain::where('server_id', $server->identifier)/*->where('status', '!=', 'deletion pending')*/->get();
        //dd([$tabs, $tabListItems]);
        //dd(DomainController::availableSubdomains(true));
        foreach($domains->where('target', 'web')->where('status', 'certificate generation failed') as $domain)
        {
            $domain->last_attempt = Carbon::createFromTimeString($domain->created_at)->addDay();
        }

        return view('servers.settings', [
            'tabs' => $tabs,
            'tabListItems' => $tabListItems,
            'themes' => $themes,
            'active_theme' => Theme::active(),
            'server' => $server,
            'products' => $products,
            'availableSubdomains' => DomainController::availableSubdomains('', true),
            //přidat target
            'minecraft_subdomains' => $domains->where('type', "subdomain")->where('target', 'minecraft'),
            'minecraft_domains' => $domains->where('type', "domain")->where('target', 'minecraft'),
            'address' => $address,
            'web_router_address' => 'web.vagonbrei.eu',
            'ports' => $ports,
            'web_subdomains' => $domains->where('type', "subdomain")->where('target', 'web'),
            'web_domains' => $domains->where('type', "domain")->where('target', 'web'),
            'main_port' => $main_port,
            'nest_id' => $serverAttributes['nest'],
            'egg_id' => $serverAttributes['egg']
        ]);
    }
    
    // H

    public function upgrade(Server $server, Request $request)
    {
        if ($server->user_id != Auth::user()->id) {
            return redirect()->route('servers.index');
        }
        if (! isset($request->product_upgrade)) {
            return redirect()->route('servers.show', ['server' => $server->id])->with('error', __('this product is the only one'));
        }
        $user = Auth::user();
        $oldProduct = Product::where('id', $server->product->id)->first();
        $newProduct = Product::where('id', $request->product_upgrade)->first();
        $serverAttributes = Pterodactyl::getServerAttributes($server->pterodactyl_id);
        $serverRelationships = $serverAttributes['relationships'];

        // Get node resource allocation info
        $nodeId = $serverRelationships['node']['attributes']['id'];
        $node = Node::where('id', $nodeId)->firstOrFail();
        $nodeName = $node->name;

        // Check if node has enough memory and disk space
        $requireMemory = $newProduct->memory - $oldProduct->memory;
        $requiredisk = $newProduct->disk - $oldProduct->disk;
        $checkResponse = Pterodactyl::checkNodeResources($node, $requireMemory, $requiredisk);
        if ($checkResponse == false) {
            return redirect()->route('servers.index')->with('error', __("The node '".$nodeName."' doesn't have the required memory or disk left to upgrade the server."));
        }

        $priceupgrade = $newProduct->getHourlyPrice();

        if ($priceupgrade < $oldProduct->getHourlyPrice()) {
            $priceupgrade = 0;
        }
        if ($user->credits >= $priceupgrade && $user->credits >= $newProduct->minimum_credits) {
            $server->product_id = $request->product_upgrade;
            $server->update();
            $server->allocation = $serverAttributes['allocation'];
            $response = Pterodactyl::updateServer($server, $newProduct);
            if ($response->failed()) return redirect()->route('servers.index')->with('error', __("The system was unable to update your server product. Please try again later or contact support."));
            //update user balance
            $user->decrement('credits', $priceupgrade);
            //restart the server
            $response = Pterodactyl::powerAction($server, 'restart');
            if ($response->failed()) {
                return redirect()->route('servers.index')->with('error', $response->json()['errors'][0]['detail']);
            }

            return redirect()->route('servers.show', ['server' => $server->id])->with('success', __('Server Successfully Upgraded'));
        } else {
            return redirect()->route('servers.show', ['server' => $server->id])->with('error', __('Not Enough Balance for Upgrade'));
        }
    }
}
