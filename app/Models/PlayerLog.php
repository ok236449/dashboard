<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlayerLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'online_players',
        'player_slots',
        'average_players',
    ];

    public static function index(Request $request)
    {
        if(!($request->token == env('HOME_API_KEY'))) return response()->json(['message' => 'Unauthorized - wrong token.'], 401);
        $stats = Cache::remember('stats', 60, function(){
            $data = array();
            $labels = array();
            $todayStart = Carbon::now()->startOfDay()->unix();
            $playSeconds = 0;
            $playHours = 0;
            $players = 0;

            $logs = PlayerLog::orderBy('created_at')->get();
            for($i = Carbon::now()->startOfHour()->subDay()->subDay()->unix(); $i <= Carbon::now()->addHour()->startOfHour()->unix(); $i += 3600)
            {
                $time = $i>Carbon::now()->unix()?Carbon::now()->unix() - Carbon::now()->unix()%(config('SETTINGS::SYSTEM:PLAYER_LOG_INTERVAL')*60):$i; //make the latest point rounded to X minute intervals.
                $index = ($i-Carbon::now()->startOfHour()->subDay()->subDay()->unix())/3600;
                $data[$index] = 0;
                $labels[$index] = $time<$todayStart?Carbon::createFromTimestamp($time)->format('d.m. H:i'):Carbon::createFromTimestamp($time)->format('H:i');
                foreach($logs->where('created_at', '>', date('Y-m-d H:i:s', $time-3600))->where('created_at', '<=', date('Y-m-d H:i:s', $time)) as $key => $log)
                {
                    if($log->online_players > $data[$index])$data[$index] = $log->online_players;

                    if(isset($logs[$key+1]))
                    {
                        $nextLog = $logs[$key+1];
                        $timeDifference = Carbon::createFromTimeString($nextLog->created_at)->unix()-Carbon::createFromTimeString($log->created_at)->unix();
                        $playSeconds += ($timeDifference);
                        $playHours += ($timeDifference) * $log['online_players'] / 3600;
                        $players += $log['online_players'];
                    }
                }
                if($data[$index]==0)
                {
                    unset($data[$index]);
                    unset($labels[$index]);
                }
            }
            return [
                'players' =>[
                    'data' => $data,
                    'labels' => $labels 
                ],
                'serverCount' => Server::count(),
                'userCount' => User::count(),
                'averagePlayerCount' => ($logs->count()-1)<=0?"?":round($players/($logs->count()-1), 2),
                'playHoursPerDay' => $playSeconds==0?"?":round(86400/($playSeconds)*$playHours, 2)
            ];
        });
        return $stats;
    }
}
