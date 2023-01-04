<?php

namespace App\Console\Commands;

use App\Classes\Pterodactyl;
use App\Models\PlayerLog;
use Carbon\Carbon;
use Illuminate\Console\Command;

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
    public function handle()
    {
        $timeNow = Carbon::now();
        if(!PlayerLog::first()||$timeNow->roundMinute()->format('i')%config('SETTINGS::SYSTEM:PLAYER_LOG_INTERVAL')==0)
        {
            $status = collect();
            foreach(Pterodactyl::getServers() as $server){
                if($server['attributes']['suspended']) continue;
                if($server['attributes']['nest']==1)
                {
                    $networking = Pterodactyl::getNetworking($server['attributes']['identifier']);
                    $ip = "";
                    $port = "";
                    foreach($networking['data'] as $network)
                    {
                        if($network['attributes']['is_default'])
                        {
                            $ip = $network['attributes']['ip'];
                            $port = $network['attributes']['port'];
                            break;
                        }
                    }
                    $socket = @fsockopen($ip, $port, $errorrr, $errorrr_message, 0.5);
                    if($socket == false) continue;
                    fwrite($socket, "\xfe");
                    $data = fread($socket, 256);
                    if(substr($data, 0, 1)!= "\xff") continue;
                    $data = explode('§', mb_convert_encoding(substr($data, 3), 'UTF8', 'UCS-2'));
                    $status[$server['attributes']['identifier']] = collect();
                    $status[$server['attributes']['identifier']]['players'] = intval($data[1]);
                    $status[$server['attributes']['identifier']]['slots'] = intval($data[2]);
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
            PlayerLog::where('created_at', '<', $timeNow->subDay()->subDay()->subMinutes(Round(config('SETTINGS::SYSTEM:PLAYER_LOG_INTERVAL')*1.5)))->delete();
            $playerLog ->save();
        }
        return 0;
    }
}
