<?php

namespace App\Console\Commands;

use App\Classes\Convoy;
use App\Classes\Pterodactyl;
use App\Models\User;
use App\Models\VirtualPrivateServer;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ChargeForVPS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'VPS:charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge users for their VPS.';

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
        foreach(VirtualPrivateServer::where('last_payment', '<=', Carbon::now()->subDays(30))->orwhere('last_payment', null)->get() as $vps){
            //dd($vps);
            $user = User::where('id', $vps->user_id)->first();
            if($user->credits>$vps->price/100){
                $user->decrement('credits', $vps->price/100);
                Convoy::unsuspendServer($vps->uuid);
                $vps->last_payment = $timeNow;
                $vps->save();
            }
            else Convoy::suspendServer($vps->uuid);
        }
        return "ok";
    }
}
