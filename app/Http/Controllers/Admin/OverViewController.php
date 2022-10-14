<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Egg;
use App\Models\Location;
use App\Models\Nest;
use App\Models\Node;
use App\Models\Payment;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use App\Classes\Pterodactyl;
use App\Models\Product;
use App\Models\Ticket;
use Carbon\Carbon;

class OverViewController extends Controller
{
    public const TTL = 86400;

    public function index()
    {
        //Get counters
        $counters = collect();
        //Set basic variables in the collection
        $counters->put('users', User::query()->count());
        $counters->put('credits', number_format(User::query()->where("role","!=","admin")->sum('credits'), 2, '.', ''));
        $counters->put('payments', Payment::query()->count());
        $counters->put('eggs', Egg::query()->count());
        $counters->put('nests', Nest::query()->count());
        $counters->put('locations', Location::query()->count());

        //Prepare for counting
        $counters->put('servers', collect());
        $counters['servers']->active = 0;
        $counters['servers']->total = 0;
        $counters->put('earnings', collect());
        $counters['earnings']->active = 0;
        $counters['earnings']->total = 0;
        $counters->put('totalUsagePercent', 0);

        //Prepare subCollection 'payments'
        $counters->put('payments', collect());
        //Get and save payments from last 2 months for later filtering and looping
        $payments = Payment::query()->where('created_at', '>=', Carbon::today()->startOfMonth()->subMonth())->where('status', 'paid')->get();
        //Prepare collections and set a few variables
        $counters['payments']->put('thisMonth', collect());
        $counters['payments']->put('lastMonth', collect());
        $counters['payments']['thisMonth']->timeStart = Carbon::today()->startOfMonth()->toDateString();
        $counters['payments']['thisMonth']->timeEnd = Carbon::today()->toDateString();
        $counters['payments']['lastMonth']->timeStart = Carbon::today()->startOfMonth()->subMonth()->toDateString();
        $counters['payments']['lastMonth']->timeEnd = Carbon::today()->endOfMonth()->subMonth()->toDateString();


        //Prepare subCollection 'taxPayments'
        $counters->put('taxPayments', collect());
        //Get and save taxPayments from last 2 years for later filtering and looping
        $taxPayments = Payment::query()->where('created_at', '>=', Carbon::today()->startOfYear()->subYear())->where('status', 'paid')->get();
        //Prepare collections and set a few variables
        $counters['taxPayments']->put('thisYear', collect());
        $counters['taxPayments']->put('lastYear', collect());
        $counters['taxPayments']['thisYear']->timeStart = Carbon::today()->startOfYear()->toDateString();
        $counters['taxPayments']['thisYear']->timeEnd = Carbon::today()->toDateString();
        $counters['taxPayments']['lastYear']->timeStart = Carbon::today()->startOfYear()->subYear()->toDateString();
        $counters['taxPayments']['lastYear']->timeEnd = Carbon::today()->endOfYear()->subYear()->toDateString();
        
        //Fill out variables for each currency separately
        foreach($payments->where('created_at', '>=', Carbon::today()->startOfMonth()) as $payment){
            $paymentCurrency = $payment->currency_code;
            if(!isset($counters['payments']['thisMonth'][$paymentCurrency])){
                $counters['payments']['thisMonth']->put($paymentCurrency, collect());
                $counters['payments']['thisMonth'][$paymentCurrency]->total = 0;
                $counters['payments']['thisMonth'][$paymentCurrency]->count = 0;
            }
            $counters['payments']['thisMonth'][$paymentCurrency]->total += $payment->total_price;
            $counters['payments']['thisMonth'][$paymentCurrency]->count ++;
        }
        foreach($payments->where('created_at', '<', Carbon::today()->startOfMonth()) as $payment){
            $paymentCurrency = $payment->currency_code;
            if(!isset($counters['payments']['lastMonth'][$paymentCurrency])){
                $counters['payments']['lastMonth']->put($paymentCurrency, collect());
                $counters['payments']['lastMonth'][$paymentCurrency]->total = 0;
                $counters['payments']['lastMonth'][$paymentCurrency]->count = 0;
            }
            $counters['payments']['lastMonth'][$paymentCurrency]->total += $payment->total_price;
            $counters['payments']['lastMonth'][$paymentCurrency]->count ++;
        }
        $counters['payments']->total = Payment::query()->count();

        foreach($taxPayments->where('created_at', '>=', Carbon::today()->startOfYear()->subYear()) as $taxPayment){
            $paymentCurrency = $payment->currency_code;
            if(!isset($counters['taxPayments']['thisYear'][$paymentCurrency])){
                $counters['taxPayments']['thisYear']->put($paymentCurrency, collect());
                $counters['taxPayments']['thisYear'][$paymentCurrency]->total = 0;
                $counters['taxPayments']['thisYear'][$paymentCurrency]->count = 0;
                $counters['taxPayments']['thisYear'][$paymentCurrency]->price = 0;
                $counters['taxPayments']['thisYear'][$paymentCurrency]->taxes = 0;
            }
            $counters['taxPayments']['thisYear'][$paymentCurrency]->total += $taxPayment->total_price;
            $counters['taxPayments']['thisYear'][$paymentCurrency]->count ++;
            $counters['taxPayments']['thisYear'][$paymentCurrency]->price += $taxPayment->price;
            $counters['taxPayments']['thisYear'][$paymentCurrency]->taxes += $taxPayment->tax_value;
        }
        foreach($taxPayments->where('created_at', '<', Carbon::today()->startOfYear()) as $taxPayment){
            $paymentCurrency = $payment->currency_code;
            if(!isset($counters['taxPayments']['lastYear'][$paymentCurrency])){
                $counters['taxPayments']['lastYear']->put($paymentCurrency, collect());
                $counters['taxPayments']['lastYear'][$paymentCurrency]->total = 0;
                $counters['taxPayments']['lastYear'][$paymentCurrency]->count = 0;
                $counters['taxPayments']['lastYear'][$paymentCurrency]->price = 0;
                $counters['taxPayments']['lastYear'][$paymentCurrency]->taxes = 0;
            }
            $counters['taxPayments']['lastYear'][$paymentCurrency]->total += $taxPayment->total_price;
            $counters['taxPayments']['lastYear'][$paymentCurrency]->count ++;
            $counters['taxPayments']['lastYear'][$paymentCurrency]->price += $taxPayment->price;
            $counters['taxPayments']['lastYear'][$paymentCurrency]->taxes += $taxPayment->tax_value;
        }

        $lastEgg = Egg::query()->latest('updated_at')->first();
        $syncLastUpdate = $lastEgg ? $lastEgg->updated_at->isoFormat('LLL') : __('unknown');
        


        //Get node information
        $nodes = collect();
        foreach($DBnodes = Node::query()->get() as $DBnode){ //gets all node information and prepares the structure
            $nodeId = $DBnode['id'];
            $nodes->put($nodeId, collect());
            $nodes[$nodeId]->name = $DBnode['name'];
            $pteroNode = Pterodactyl::getNode($nodeId);
            $nodes[$nodeId]->usagePercent = round(max($pteroNode['allocated_resources']['memory']/($pteroNode['memory']*($pteroNode['memory_overallocate']+100)/100), $pteroNode['allocated_resources']['disk']/($pteroNode['disk']*($pteroNode['disk_overallocate']+100)/100))*100, 2);
            $counters['totalUsagePercent'] += $nodes[$nodeId]->usagePercent;

            $nodes[$nodeId]->totalServers = 0;
            $nodes[$nodeId]->activeServers = 0;
            $nodes[$nodeId]->totalEarnings = 0;
            $nodes[$nodeId]->activeEarnings = 0;
        }
        $counters['totalUsagePercent'] = ($DBnodes->count())?round($counters['totalUsagePercent']/$DBnodes->count(), 2):0;

        foreach(Pterodactyl::getServers() as $server){ //gets all servers from Pterodactyl and calculates total of credit usage for each node separately + total
            $nodeId = $server['attributes']['node'];
            
            if($CPServer = Server::query()->where('pterodactyl_id', $server['attributes']['id'])->first()){
                $prize = Product::query()->where('id', $CPServer->product_id)->first()->price;
                if (!$CPServer->suspended){
                    $counters['earnings']->active += $prize;
                    $counters['servers']->active ++;
                    $nodes[$nodeId]->activeEarnings += $prize;
                    $nodes[$nodeId]->activeServers ++;
                }
                $counters['earnings']->total += $prize;
                $counters['servers']->total ++;
                $nodes[$nodeId]->totalEarnings += $prize;
                $nodes[$nodeId]->totalServers ++;
            }
        }



        //Get latest tickets
        $tickets = collect();
        foreach(Ticket::query()->latest()->take(5)->get() as $ticket){
            $tickets->put($ticket->ticket_id, collect());
            $tickets[$ticket->ticket_id]->title = $ticket->title;
            $user = User::query()->where('id', $ticket->user_id)->first();
            $tickets[$ticket->ticket_id]->user_id = $user->id;
            $tickets[$ticket->ticket_id]->user = $user->name;
            $tickets[$ticket->ticket_id]->status = $ticket->status;
            $tickets[$ticket->ticket_id]->last_updated = $ticket->updated_at->diffForHumans();
            switch ($ticket->status) {
                case 'Open':
                    $tickets[$ticket->ticket_id]->statusBadgeColor = 'badge-success';
                    break;
                case 'Closed':
                    $tickets[$ticket->ticket_id]->statusBadgeColor = 'badge-danger';
                    break;
                case 'Answered':
                    $tickets[$ticket->ticket_id]->statusBadgeColor = 'badge-info';
                    break;
                default:
                    $tickets[$ticket->ticket_id]->statusBadgeColor = 'badge-warning';
                    break;
            }
        }

        return view('admin.overview.index', [
            'counters'       => $counters,
            'nodes'          => $nodes,
            'syncLastUpdate' => $syncLastUpdate,
            'perPageLimit'   => ($counters['servers']->total != Server::query()->count())?true:false,
            'tickets'        => $tickets
        ]);
    }   

    /**
     * @description Sync locations,nodes,nests,eggs with the linked pterodactyl panel
     */
    public function syncPterodactyl()
    {
        Node::syncNodes();
        Egg::syncEggs();

        return redirect()->back()->with('success', __('Pterodactyl synced'));
    }
}
