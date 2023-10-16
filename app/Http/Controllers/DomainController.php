<?php

namespace App\Http\Controllers;

use App\Classes\Cloudflare;
use App\Classes\Pterodactyl;
use App\Models\Domain;
use App\Models\Server;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Yaml\Yaml;

class DomainController extends Controller
{
    public static $availableSubdomains = [
        'mc.vagonbrei.eu' => [
            'cf_token' => '06caf79d8d0a268e8aafe0c21c4cb67d',
            'available_for' => ['minecraft']
        ],
        'web.vagonbrei.eu' => [
            'cf_token' => '06caf79d8d0a268e8aafe0c21c4cb67d',
            'available_for' => ['web']
        ],
        'czmc.cz' => [
            'cf_token' => '319696931950f2accdb2adf30c22a93e',
            'available_for' => ['minecraft', 'web']
        ],
        /*'czmc.online' => [
            'cf_token' => '828f41e1760de9e481c9594cd8cdde28',
            'available_for' => ['minecraft', 'web']
        ],*/
        'hraj.xyz' => [
            'cf_token' => '5c0b482aab8b76c58ece61515bf39d90',
            'available_for' => ['minecraft', 'web']
        ]
    ];
    //public function toFirstLevel
    public static function availableSubdomains($target = '', $dotOnStart = false){ //returns array of available domains if target is provided or array domain_name=>[available_for]
        $domains = array();
        foreach(DomainController::$availableSubdomains as $key => $as) {
            if($target&&in_array($target, $as['available_for'])) $domains[] = ($dotOnStart?'.':'') . $key;
            else $domains[($dotOnStart?'.':'') . $key] = $as['available_for'];
        }
        return $domains;
    }
    public static function toBaseDomain($domain_name){
        $domain_parts = explode('.', $domain_name);
        return implode('.', [$domain_parts[count($domain_parts)-2], $domain_parts[count($domain_parts)-1]]);
    }
    public function checkAvailability(Request $request)
    {
        //Check for missing data
        if(!$request->type||!($request->domain||($request->subdomain_prefix&&$request->subdomain_suffix))) return response()->json(['error' => __('Missing data.')], 422);

        switch($request->type){
            case 'subdomain':
                return response()->json(['available' => (Domain::where('subdomain_prefix', $request->subdomain_prefix)->where('subdomain_suffix', $request->subdomain_suffix)->first())?false:true]);
                break;
            case 'domain':
                return response()->json(['available' => (Domain::where('domain', $request->domain)->first())?false:true]);
                break;
        }
        //dd($request);
    }

    public function uploadBungeeGuard($server_id){
        //get latest version of the bungeeguard
        $response = Http::get(env('BUNGEEGUARD_DOWNLOAD_URL'));
        $fileContents = $response->body();

        //write file to server
        $response = Pterodactyl::writeFileContent($server_id, ['plugins', 'VB-bungeeguard.jar'], $fileContents);
        return $response;
    }
    public function deleteBungeeGuard($server_id){
        return Pterodactyl::deleteFileContent($server_id, 'plugins', 'VB-bungeeguard.jar');
    }

    public function linkMinecraftSubdomain(Request $request){
        $server = Server::where('identifier', $request->server_id)->first();
        
        //perform the nescesarry checks
        if(!preg_match("/^[a-zA-Z0-9]+$/", $request->subdomain_prefix)) return response()->json(['errors' => ['minecraft_subdomain' => __('Special characters are not allowed. Please use only letters and numbers.')]], 422);
        if(!$server||$server->user_id!=Auth::user()->id|| //server does not exist or user is not the owner
            !in_array($request->subdomain_suffix, $this->availableSubdomains('minecraft', true)) //unknown base for subdomain
        ) return response()->json(['errors' => ['minecraft_subdomain' => __('Wrong data.')]], 422);

        if(!$request->subdomain_prefix||!$request->subdomain_suffix||strlen($request->subdomain_prefix)<3||strlen($request->subdomain_prefix)>30) return response()->json(['errors' => ['minecraft_subdomain' => __('Subdomain must be 3-30 characters long.')]], 422);
        if(Domain::where('subdomain_prefix', $request->subdomain_prefix)->where('subdomain_suffix', $request->subdomain_suffix)->where('target', 'minecraft')->first()) return response()->json(['errors' => ['minecraft_subdomain' => __('Domain is already taken') . '.']], 422);
        if(Domain::where('server_id', $server->identifier)->where('type', 'subdomain')->where('target', 'minecraft')->count()>=5) return response()->json(['errors' => ['minecraft_subdomain' => __('The server has reached the limit of linked domains.')]], 422);

        //create the database record for the subdomain
        $domain = new Domain();
        $domain -> subdomain_prefix = $request->subdomain_prefix;
        $domain -> subdomain_suffix = $request->subdomain_suffix;
        $domain -> type = 'subdomain';
        $domain -> target = 'minecraft';
        $domain -> server_id = $server->identifier;

        //Get server networking - only the main port and store it in the record
        foreach(Pterodactyl::getNetworking($server->identifier)['data'] as $network)
        {
            if($network['attributes']['is_default'])
            {
                $domain -> node_domain = $network['attributes']['ip_alias'];
                $domain -> port = $network['attributes']['port'];
                break;
            }
        }

        //create the DNS record on cloudflare
        $cf = Cloudflare::createRecord(DomainController::$availableSubdomains[ltrim($request->subdomain_suffix, '.')]['cf_token'], "SRV", $request->subdomain_prefix . $request->subdomain_suffix, $domain -> node_domain, $domain -> port);
        
        //check cloudflare response
        if(!$cf||!isset($cf['success'])||$cf['success']!=true) return response()->json(['errors' => ['minecraft_subdomain' => __('Could not create the cloudflare record.'), 'cloudflare_errors' => $cf]], 422);
        $domain -> cf_id = $cf['result']['id'];

        //success
        $domain -> save();
        return response()->json('ok');
    }

    public function refreshMinecraftSubdomain(Request $request){
        $server = Server::where('identifier', $request->server_id)->first();
        
        //perform the nescesarry checks
        //server does not exist or user is not the owner
        if(!$server||$server->user_id!=Auth::user()->id) return response()->json(['errors' => ['minecraft_subdomain' => __('Wrong data.')]], 422);
        $domain = Domain::where('server_id', $request->server_id)->where('type', 'subdomain')->where('target', 'minecraft')->where('subdomain_prefix', $request->subdomain_prefix)->where('subdomain_suffix', $request->subdomain_suffix)->first();
        if(!$domain) return response()->json(['errors' => ['minecraft_subdomain' => __('Missing data in the database.')]], 422);

        //Get server networking - only the main port and store it in the record
        foreach(Pterodactyl::getNetworking($server->identifier)['data'] as $network)
        {
            if($network['attributes']['is_default'])
            {
                $domain -> node_domain = $network['attributes']['ip_alias'];
                $domain -> port = $network['attributes']['port'];
                break;
            }
        }

        //create the DNS record on cloudflare
        $cf = Cloudflare::patchRecord(DomainController::$availableSubdomains[ltrim($domain->subdomain_suffix, '.')]['cf_token'], $domain->cf_id, "SRV", $domain->subdomain_prefix . $domain->subdomain_suffix, $domain->node_domain, $domain->port);
        
        //check cloudflare response
        if(!$cf||!isset($cf['success'])||$cf['success']!=true) return response()->json(['errors' => ['minecraft_subdomain' => __('Could not update the cloudflare record.'), 'cloudflare_errors' => $cf]], 422);
        
        //success
        $domain -> save();
        return response()->json('ok');
    }

    public function unlinkMinecraftSubdomain(Request $request){
        $server = Server::where('identifier', $request->server_id)->first();
        
        //perform the nescesarry checks
        //server does not exist or user is not the owner
        if(!$server||$server->user_id!=Auth::user()->id) return response()->json(['errors' => ['minecraft_subdomain' => __('Wrong data.')]], 422);
        $domain = Domain::where('server_id', $request->server_id)->where('type', 'subdomain')->where('target', 'minecraft')->where('subdomain_prefix', $request->subdomain_prefix)->where('subdomain_suffix', $request->subdomain_suffix)->first();
        if(!$domain) return response()->json(['errors' => ['minecraft_subdomain' => __('Missing data in the database.')]], 422);

        //delete the DNS record on cloudflare
        $cf = Cloudflare::deleteRecord(DomainController::$availableSubdomains[ltrim($domain->subdomain_suffix, '.')]['cf_token'], $domain->cf_id);
        
        //check cloudflare response
        if(!$cf||!isset($cf['success'])||$cf['success']!=true) return response()->json(['errors' => ['minecraft_subdomain' => __('Could not delete the cloudflare record.'), 'cloudflare_errors' => $cf]], 422);

        //success
        $domain->delete();
        return response()->json('ok');
    }

    public function linkWebSubdomain(Request $request){
        $server = Server::where('identifier', $request->server_id)->first();
        //perform the nescesarry checks
        if(!$request->web_port) return response()->json(['errors' => ['web_subdomain' => __('No port specified.')]], 422);
        if(!preg_match("/^[a-zA-Z0-9]+$/", $request->subdomain_prefix)) return response()->json(['errors' => ['web_subdomain' => __('Special characters are not allowed. Please use only letters and numbers.')]], 422);
        if(!$server||$server->user_id!=Auth::user()->id|| //server does not exist or user is not the owner
            !in_array($request->subdomain_suffix, $this->availableSubdomains('web', true))|| //unknown base for subdomain
            !$request->web_port||!preg_match("/^[0-9]+$/", $request->web_port)
        ) return response()->json(['errors' => ['web_subdomain' => __('Wrong data.')]], 422);

        if(!$request->subdomain_prefix||!$request->subdomain_suffix||strlen($request->subdomain_prefix)<3||strlen($request->subdomain_prefix)>30) return response()->json(['errors' => ['web_subdomain' => __('Subdomain must be 3-30 characters long.')]], 422);
        if(Domain::where('server_id', $server->identifier)->where('type', 'subdomain')->where('target', 'web')->count()>=5) return response()->json(['errors' => ['web_subdomain' => __('The server has reached the limit of linked domains.')]], 422);
        if(Domain::where('subdomain_prefix', $request->subdomain_prefix)->where('subdomain_suffix', $request->subdomain_suffix)->where('target', 'web')->first()) return response()->json(['errors' => ['web_subdomain' => __('Domain is already taken') . '.']], 422);

        //create the database record for the subdomain
        $domain = new Domain();
        $domain -> subdomain_prefix = $request->subdomain_prefix;
        $domain -> subdomain_suffix = $request->subdomain_suffix;
        $domain -> type = 'subdomain';
        $domain -> target = 'web';
        $domain -> server_id = $server->identifier;

        //get Pterodactyl info
        $serverAttributes = Pterodactyl::getServerAttributes($server->pterodactyl_id);

        //Get server networking - only the main port for a web server or all ports ports for other servers
        $web_ports = array();
        foreach(Pterodactyl::getNetworking($server->identifier)['data'] as $network)
        {
            if($network['attributes']['is_default'])
            {
                $domain->node_domain = $network['attributes']['ip_alias'];
                if($serverAttributes['nest']==8) $web_ports[] = $network['attributes']['port'];
            }
            else $web_ports[] = $network['attributes']['port'];
        }

        //check if the selected port is valid
        if(!in_array($request->web_port, $web_ports)) return response()->json(['errors' => ['web_subdomain' => __('Wrong data.')]], 422);
        $domain->port = $request->web_port;
        
        //create the DNS record on cloudflare
        $cf = Cloudflare::createRecord(DomainController::$availableSubdomains[ltrim($request->subdomain_suffix, '.')]['cf_token'], "CNAME", $request->subdomain_prefix . $request->subdomain_suffix, 'web.vagonbrei.eu');
        
        //check cloudflare response
        if(!$cf||!isset($cf['success'])||$cf['success']!=true) return response()->json(['errors' => ['web_subdomain' => __('Could not create the cloudflare record.'), 'cloudflare_errors' => $cf]], 422);
        
        //cloudflare success
        $domain -> cf_id = $cf['result']['id'];

        /*//generate certificate
        if($this->getDomainCertificate($request->subdomain_prefix . $request->subdomain_suffix)!=1) return response()->json(['errors' => ['web_subdomain' => __('An error occured while generating the certificate.')]], 422);;

        //create nginx config file
        if(!$this->writeNginxConfigFile($request->subdomain_prefix . $request->subdomain_suffix, $domain->node_domain, $domain->port)){
            $cf2 = Cloudflare::deleteRecord(DomainController::$availableSubdomains[ltrim($domain->subdomain_suffix, '.')]['cf_token'], $domain->cf_id);
            if(!$cf||!isset($cf['success'])||$cf['success']!=true) return response()->json(['errors' => ['web_subdomain' => __('Could not create web config file and failed during removal of the subdomain. Please contact support as soon as possible.'), 'cloudflare_errors' => $cf]], 422);
            return response()->json(['errors' => ['web_subdomain' => __('Could not create the web config file. The changes have been successfully reverted.')]], 422);
        };*/

        //success, the daemon will generate the certificate automatically.
        $domain->status = "certificate generation pending";
        $domain -> save();
        //$output = exec('nginx -s reload');
        return response()->json('ok');
    }

    public function unlinkWebSubdomain(Request $request){
        $server = Server::where('identifier', $request->server_id)->first();
        
        //perform the nescesarry checks
        //server does not exist or user is not the owner
        if(!$server||$server->user_id!=Auth::user()->id) return response()->json(['errors' => ['web_subdomain' => __('Wrong data.')]], 422);
        $domain = Domain::where('server_id', $request->server_id)->where('type', 'subdomain')->where('target', 'web')->where('subdomain_prefix', $request->subdomain_prefix)->where('subdomain_suffix', $request->subdomain_suffix)->first();
        if(!$domain) return response()->json(['errors' => ['web_subdomain' => __('Missing data in the database.')]], 422);

        $cf = Cloudflare::deleteRecord(DomainController::$availableSubdomains[ltrim($domain->subdomain_suffix, '.')]['cf_token'], $domain->cf_id);
        
        //check cloudflare response
        if(!$cf||!isset($cf['success'])||$cf['success']!=true) return response()->json(['errors' => ['web_subdomain' => __('Could not delete the cloudflare record.'), 'cloudflare_errors' => $cf]], 422);

        /*//delete config file without checking
        $this->deleteNginxConfig($domain->subdomain_prefix . $domain->subdomain_suffix);*/

        //success
        $domain->status = "deletion pending";
        $domain->save();
        return response()->json('ok');
    }

    public function linkWebDomain(Request $request){
        $server = Server::where('identifier', $request->server_id)->first();
        
        //perform the nescesarry checks
        if(!$request->web_port) return response()->json(['errors' => ['web_domain' => __('No port specified.')]], 422);
        if(!preg_match("/^[a-zA-Z0-9.]+$/", $request->domain)) return response()->json(['errors' => ['web_domain' => __('Special characters are not allowed. Please use only letters and numbers.')]], 422);
        if(substr_count($request->domain, '.')<1) return response()->json(['errors' => ['web_domain' => __('Invalid domain.')]], 422);
        //server does not exist or user is not the owner
        if(!$server||$server->user_id!=Auth::user()->id) return response()->json(['errors' => ['web_domain' => __('Wrong data.')]], 422);

        if(!$request->domain||strlen($request->domain)<6||strlen($request->domain)>40) return response()->json(['errors' => ['web_domain' => __('Domain must be 6-40 characters long.')]], 422);
        if(Domain::where('server_id', $server->identifier)->where('type', 'domain')->where('target', 'web')->count()>=5) return response()->json(['errors' => ['web_domain' => __('The server has reached the limit of linked domains.')]], 422);
        if(Domain::where('domain', $request->domain)->where('target', 'web')->first()) return response()->json(['errors' => ['web_domain' => __('Domain is already taken.') . '.']], 422);

        //check for blacklisted domains
        foreach(DomainController::availableSubdomains() as $blacklisted_domain => $details){
            if(str_ends_with($request->domain, $this->toBaseDomain($blacklisted_domain))) return response()->json(['errors' => ['web_domain' => __('Blacklisted domain.')]], 422);
        }

        //create the database record for the subdomain
        $domain = new Domain();
        $domain -> domain = $request->domain;
        $domain -> type = 'domain';
        $domain -> target = 'web';
        $domain -> server_id = $server->identifier;

        //get Pterodactyl info
        $serverAttributes = Pterodactyl::getServerAttributes($server->pterodactyl_id);

        //Get server networking - only the main port for a web server or all ports ports for other servers
        $web_ports = array();
        foreach(Pterodactyl::getNetworking($server->identifier)['data'] as $network)
        {
            if($network['attributes']['is_default'])
            {
                $domain->node_domain = $network['attributes']['ip_alias'];
                if($serverAttributes['nest']==8) $web_ports[] = $network['attributes']['port'];
            }
            else $web_ports[] = $network['attributes']['port'];
        }

        //check if the selected port is valid
        if(!in_array($request->web_port, $web_ports)) return response()->json(['errors' => ['web_domain' => __('Wrong data.')]], 422);
        $domain->port = $request->web_port;

        /*//generate certificate
        if($this->getDomainCertificate($request->subdomain_prefix . $request->subdomain_suffix)!=1) return response()->json(['errors' => ['web_subdomain' => __('An error occured while generating the certificate.') . ' ' . __('Please make sure your domain records are set correctly. Some domain registrators may need up to 12 hours for the records to take effect.')]], 422);

        //create nginx config file
        if(!$this->writeNginxConfigFile($request->domain, $domain->node_domain, $domain->port))
            return response()->json(['errors' => ['web_domain' => __('Could not create the web config file.')]], 422);*/

        //success
        $domain->status = "certificate generation pending";
        $domain -> save();
        //$output = exec('nginx -s reload');
        return response()->json('ok');
    }

    public function unlinkWebDomain(Request $request){
        $server = Server::where('identifier', $request->server_id)->first();
        
        //perform the nescesarry checks
        //server does not exist or user is not the owner
        if(!$server||$server->user_id!=Auth::user()->id) return response()->json(['errors' => ['web_domain' => __('Wrong data.')]], 422);
        $domain = Domain::where('server_id', $request->server_id)->where('type', 'domain')->where('target', 'web')->where('domain', $request->domain)->first();
        //dd($domain);
        if(!$domain) return response()->json(['errors' => ['web_domain' => __('Missing data in the database.')]], 422);
        if($domain->server_id!=$server->identifier) return response()->json(['errors' => ['web_domain' => __('This domain does not belong to this server!')]], 422);

        /*//delete config file without checking
        $this->deleteNginxConfig($domain->domain);*/

        //success
        $domain->status = "deletion pending";
        $domain->save();
        return response()->json('ok');
    }

    public function getDomainCertificate($domain)
    {
        exec('doas certbot certonly --nginx -d ' . $domain . ' -n --quiet', $output, $result);
        return $result;
    }
    
    public function writeNginxConfigFile($domain, $node_domain, $port)
    {
        $fileContents = 
'server {
    listen 80;
    server_name ' . $domain . ' www.' . $domain . ';
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name ' . $domain . ';

    ssl_certificate /etc/letsencrypt/live/' . $domain . '/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/' . $domain . '/privkey.pem;

    location / {
        proxy_pass http://' . $node_domain . ':' . $port . ';
        proxy_set_header Host $server_name;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_ssl_verify off;
    }
}';

        //check if the folder exists
        if (!is_dir(storage_path('web_domains'))) {
            mkdir(storage_path('web_domains'));
        }

        return file_put_contents(storage_path('web_domains/' . $domain . '.conf'), $fileContents);
    }

    public function deleteNginxConfig($domain){
        $file = storage_path('web_domains/' . $domain . '.conf');
        if (file_exists($file)) {
            return unlink($file);
        }
        return true; // file does not exist, so it "deleted" succesfully
    }
    
    /*public function updateProtection(Request $request)
    {
        //dd($request);
        if(!$request->server_id) return redirect()->back()->with(['error' => __('Missing data.')])->withFragment('protection');
        $server = Server::where('identifier', $request->server_id)->first();
        if(!$server) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Missing data.')])->withFragment('protection');
        $domains = Domain::where('server_id', $request->server_id)->get();
        if(!$domains||!isset($domains[0])) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('You must link a domain in the domains tab first.')])->withFragment('protection');

        //dd($domains->where('type', 'subdomain')->first()['id']);

        if($request->bungee_active){ //bungee enabled
            if(!$domains[0]->bungee_active){ //toggle bungee on
                $subdomain = $domains->where('type', 'subdomain')->where('target', 'minecraft')->first();
                if(!$this->editYMLConfig($server->identifier, ['spigot.yml'], ['settings' => ['bungeecord' => true]])) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Unable to modify spigot config.')])->withFragment('protection');
                if(!$this->editServerProperties($server->identifier, ['online-mode' => 'false'])) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Unable to modify server properties.')])->withFragment('protection');
                //update bungeeguard
                if($this->uploadBungeeGuard($server->identifier)!=204) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Could not upload Vagonbrei bungeeguard plugin to your server.')])->withFragment('protection');
                Pterodactyl::powerAction($server, 'restart');
                if($subdomain){
                    $cf = Cloudflare::patchRecord(DomainController::$availableSubdomains[ltrim($subdomain->subdomain_suffix, '.')]['cf_token'], $subdomain->cf_id, "SRV", $subdomain->subdomain_prefix . $subdomain->subdomain_suffix, $subdomain->subdomain_prefix . "." . env('BUNGEECORD_ADDRESS'), env('BUNGEECORD_PORT'));
                    //dd($cf);
                    if(!$cf||!isset($cf['success'])||$cf['success']!=true) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Unable to update cloudflare record.')])->withFragment('protection');
                    else { // save database values
                        foreach($domains as $domain){
                            $domain->bungee_active = 1;
                            $domain->save();
                        }
                    }
                }
            }
        }
        else{ //bungee disabled
            if($domains[0]->bungee_active){ //toggle bungee off
                $subdomain = $domains->where('type', 'subdomain')->where('target', 'minecraft')->first();
                if(!$this->editYMLConfig($server->identifier, ['spigot.yml'], ['settings' => ['bungeecord' => false]])) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Unable to modify spigot config.')])->withFragment('protection');
                if(!$this->editServerProperties($server->identifier, ['online-mode' => 'true'])) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Unable to modify server properties.')])->withFragment('protection');
                //delete BungeeGuard
                if($this->deleteBungeeGuard($server->identifier)!=204) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Could not delete Vagonbrei bungeeguard plugin from your server.')])->withFragment('protection');
                Pterodactyl::powerAction($server, 'restart');
                if($subdomain){
                    $cf = Cloudflare::patchRecord(DomainController::$availableSubdomains[ltrim($subdomain->subdomain_suffix, '.')]['cf_token'], $subdomain->cf_id, "SRV", $subdomain->subdomain_prefix . $subdomain->subdomain_suffix, $subdomain->subdomain_prefix . "." . $subdomain->node_domain, $subdomain->port);
                    //dd($cf);
                    if(!$cf||!isset($cf['success'])||$cf['success']!=true) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('Unable to update cloudflare record.')])->withFragment('protection');
                    else { // save database values
                        foreach($domains as $domain){
                            $domain->bungee_active = 0;
                            $domain->show_on_lobby = 0;
                            $domain->save();
                        }
                    }
                }
            }
        }
        
        return redirect()->route('servers.show', ['server' => $server->id])->with('success', __('Protection settings updated!'))->withFragment('protection');
    }
    
    function updateLobby(Request $request){
        if(!$request->server_id) return redirect()->back()->with(['error' => __('Missing data.')])->withFragment('lobby');
        $server = Server::where('identifier', $request->server_id)->first();
        if(!$server) return redirect()->back()->with(['error' => __('Missing data.')])->withFragment('lobby');
        $domains = Domain::where('server_id', $request->server_id)->get();
        if(!$domains||!isset($domains[0])||!$domains[0]->bungee_active) return redirect()->route('servers.show', ['server' => $server->id])->with(['error' => __('You must link a domain and enable proxy first.')])->withFragment('lobby');

        if($request->show_on_lobby&&!$domains[0]->show_on_lobby){ //toggle on
            foreach($domains as $domain){
                $domain->show_on_lobby = 1;
                $domain->save();
            }
        }
        else if(!$request->show_on_lobby&&$domains[0]->show_on_lobby){ //toggle off
            foreach($domains as $domain){
                $domain->show_on_lobby = 0;
                $domain->save();
            }
        }
        return redirect()->route('servers.show', ['server' => $server->id])->with(['success' => __('Lobby settings updated.')])->withFragment('lobby');
    }
    function replaceConfigValue($file, $key, $value){
        $key_pos = strpos($file, 'bungeecord');
        //dd($key_pos);
        $line_end_pos = strpos($file, '', $key_pos + strlen($key));
        dd($line_end_pos);
        dd(substr_replace($file, ': ' . $value, $key_pos + strlen($key), $line_end_pos));
    }

    
    public static function editYMLConfig($serverId, $file_path, $body){
        $configFile = Pterodactyl::getFileContent($serverId, $file_path);
        $config = Yaml::parse($configFile); // Load the YAML config file
        //dd($config);

        // Modify config
        foreach($body as $key1 => $value1){
            if(is_array($value1)) foreach($value1 as $key2 => $value2){
                if(is_array($value2)) foreach($value2 as $key3 => $value3){
                    if (isset($config[$key1][$key2][$key3])) {
                        $config[$key1][$key2][$key3] = $value3;
                    }
                }
                else if (isset($config[$key1][$key2])) {
                    $config[$key1][$key2] = $value2;
                }
            }
            else if (isset($config[$key1])) {
                $config[$key1] = $value1;
            }
        }
        $comment_end_pos = strpos($configFile, array_key_first($config));

        // Convert the modified config back to YAML format
        $modifiedConfig = substr($configFile, 0, $comment_end_pos) . Yaml::dump($config, 4, 2);

        return Pterodactyl::writeFileContent($serverId, $file_path, $modifiedConfig)==204;
    }

    public static function editServerProperties($serverId, $body){
        $configFile = Pterodactyl::getFileContent($serverId, ['server.properties']);
        $configLines = explode("\n", $configFile); // split the string into an array of lines
        $config = array(); // create an empty array to hold the config values

        foreach ($configLines as $line) {
            if (strpos($line, '#') !== 0 && strpos($line, '=') !== false) { // skip commented and empty lines
                $keyValue = explode('=', $line, 2); // split each line into key and value
                $config[$keyValue[0]] = trim($keyValue[1]); // add the key and value to the config array
            }
        }
        //dd($config);
        //modify config
        foreach($body as $key => $value){
            if (isset($config[$key])) {
                $config[$key] = $value;
            }
        }

        $modifiedConfig = '';
        foreach ($config as $key => $value) {
            $modifiedConfig .= $key . '=' . $value . PHP_EOL;
        }
        //if (isset($config['settings']['bungeecord'])) {
        //    $config['settings']['bungeecord'] = 'vemeno obecné';
        //}
        //add comments, that were in the config
        $comment_end_pos = strpos($configFile, array_key_first($config));
        $modifiedConfig = substr($configFile, 0, $comment_end_pos) . $modifiedConfig;

        return Pterodactyl::writeFileContent($serverId, ['server.properties'], $modifiedConfig)==204;
    }*/
}
