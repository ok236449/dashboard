<?php

namespace App\Console\Commands;

use App\Classes\Pterodactyl;
use App\Models\PlayerLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LogPLayersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'playerLogger:log';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Log online player count to database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */

    // H
    public static function handle()
    {
        $timeNow = Carbon::now();
        if(!PlayerLog::first()||$timeNow->roundMinute()->format('i')%config('SETTINGS::SYSTEM:PLAYER_LOG_INTERVAL')==0)
        {
            $lastPlayersRaw = ($lastPlayerLog = PlayerLog::latest()->first())?json_decode($lastPlayerLog->raw_servers, true):null;
            $appIDs = [ //NestIDs and Steam AppIDs
                64 => 304930 //Unturned
            ];
            $status = collect();
            foreach(Pterodactyl::getServers() as $server){
                if($server['attributes']['suspended']) continue;
                if($server['attributes']['nest']==1) //Minecraft
                {
                    $networking = Pterodactyl::getNetworking($server['attributes']['identifier']);
                    $ip = "";
                    $ip_alias = "";
                    $port = "";
                    foreach($networking['data'] as $network)
                    {
                        if($network['attributes']['is_default'])
                        {
                            $ip = $network['attributes']['ip'];
                            $ip_alias = $network['attributes']['ip_alias'];
                            $port = $network['attributes']['port'];
                            break;
                        }
                    }
                    if($ip_alias == env('BUNGEECORD_ADDRESS') && $port == env('BUNGEECORD_PORT')) continue;
                    $socket = @fsockopen($ip, $port, $errorrr, $errorrr_message, 0.5);
                    if($socket == false) continue;

                    $data = pack("c*", 0xFE, 0x01);
                    fwrite($socket, $data);

                    // read the server response
                    $response = fread($socket, 2048);
                    fclose($socket);

                    if (!$response || substr($response, 0, 1) != "\xFF") {
                        return false; // unable to read server response
                    }

                    $response = substr($response, 3);
                    $response = iconv("UTF-16BE", "UTF-8", $response);

                    $parts = explode("\x00", $response);
                    
                    $status[$server['attributes']['identifier']] = collect();
                    $status[$server['attributes']['identifier']]['server_name'] = "kokot";
                    $status[$server['attributes']['identifier']]['versions'] = $parts[2];
                    $status[$server['attributes']['identifier']]['motd'] = $parts[3];
                    $status[$server['attributes']['identifier']]['players'] = $parts[4];
                    $status[$server['attributes']['identifier']]['slots'] = $parts[5];
                }
                elseif($server['attributes']['nest']==15&&isset($appIDs[$server['attributes']['egg']])&&env('STEAM_WEB_API_KEY')) //SteamGames
                {
                    $networking = Pterodactyl::getNetworking($server['attributes']['identifier']);
                    $ip = "";
                    foreach($networking['data'] as $network)
                    {
                        if($network['attributes']['is_default'])
                        {
                            $ip = $network['attributes']['ip'] . ':' . $network['attributes']['port'];
                            break;
                        }
                    }
                    $request = Http::baseUrl('https://api.steampowered.com/IGameServersService/GetServerList/v1');
                    try {
                        $response = $request->get('/?filter=\appid\\' . $appIDs[$server['attributes']['egg']] . '\addr\\' . $ip . '&key=' . env('STEAM_WEB_API_KEY'));
                    } catch (Exception $e) { continue; }
                    if ($response->failed()||!isset($response->json()['response']['servers'][0])) continue;
                    $response =  $response->json()['response']['servers'][0];
                    $status[$server['attributes']['identifier']] = collect();
                    $status[$server['attributes']['identifier']]['players'] = intval($response['players']);
                    $status[$server['attributes']['identifier']]['slots'] = intval($response['max_players']);
                }
                elseif($server['attributes']['nest']==15&&$server['attributes']['egg']==80) //FiveM
                {
                    $networking = Pterodactyl::getNetworking($server['attributes']['identifier']);
                    $ip = "";
                    foreach($networking['data'] as $network)
                    {
                        if($network['attributes']['is_default'])
                        {
                            $ip = $network['attributes']['ip'] . ':' . $network['attributes']['port'];
                            break;
                        }
                    }
                    try {
                        $response1 = Http::get($ip . '/players.json');
                        $response2 = Http::get($ip . '/info.json');
                    } catch (Exception $e) { continue; }
                    if ($response1->failed()||$response2->failed()||!isset($response2->json()['vars']['sv_maxClients'])) continue;
                    $status[$server['attributes']['identifier']] = collect();
                    $status[$server['attributes']['identifier']]['players'] = count($response1->json());
                    $status[$server['attributes']['identifier']]['slots'] = intval($response2->json()['vars']['sv_maxClients']);
                }
            }
            
            $playerLog = new PlayerLog();
            foreach($status as $stat)
            {
                $playerLog -> online_players+= $stat['players'];
                $playerLog -> player_slots+= $stat['slots'];
            }
            $playerLog -> total_servers = $status->count();
            $playerLog -> created_at = $timeNow;
            if($playerLog -> total_servers!=0) $playerLog -> average_players = $playerLog -> online_players/$playerLog -> total_servers;
            $playerLog->raw_servers = json_encode($status);
            PlayerLog::where('created_at', '<', $timeNow->subDay()->subDay()->subMinutes(Round(config('SETTINGS::SYSTEM:PLAYER_LOG_INTERVAL')*0.5)))->delete();
            $playerLog ->save();
        }
        return "ok";
    }
}
