<?php

namespace App\Console\Commands;

use App\Models\DiscordUser;
use App\Models\Server;
use App\Models\User;
use App\Models\VirtualPrivateServer;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncClientDiscordRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discord:client-role-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks if users still have servers and credits and handles the discord client role';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public static function handle()
    {
        $users = User::select('id', 'credits', 'role')->where('role', 'client')->whereNotIn("role", ["admin", "moderator"])->get();
        $discordUsers = DiscordUser::get();
        $servers = Server::select('id', 'user_id', 'suspended')->get();
        $vpses = VirtualPrivateServer::select('id', 'user_id')->get();
        $clientRoleId = config('SETTINGS::DISCORD:CLIENT_ROLE_ID');
        $botToken = config('SETTINGS::DISCORD:BOT_TOKEN');
        $guildId = config('SETTINGS::DISCORD:GUILD_ID');
        $timeNow = Carbon::now()->unix();

        Log::info("Starting client discord role cleanup.");

        foreach($users as $user)
        {
            $server = $servers->where("user_id", $user->id)->first();
            $vps = $vpses->where("user_id", $user->id)->first();
            //was client, but is not anymore
            if($user->role=="client"&&$user->credits<1&&(!$server||($server->suspended&&$timeNow>Carbon::createFromTimeString($server->suspended)->addDays(14)->unix()))&&!$vps)
            {
                $user->update(['role'=>'member']);
                $discordUser = $discordUsers->where('user_id', $user->id)->first();
                if (! empty($clientRoleId)&&$discordUser) {
                    $response = Http::withHeaders(
                        [
                            'Authorization' => 'Bot '.$botToken,
                            'Content-Type' => 'application/json',
                        ]
                    )->delete(
                        "https://discord.com/api/guilds/{$guildId}/members/{$discordUser->id}/roles/{$clientRoleId}"
                        //['access_token' => $discord->token]
                    );
                }
                Log::info("User " . $user->id . "had their client role removed");
            }
        }
        Log::info("Client discord role cleanup done");

        return Command::SUCCESS;
    }
}
