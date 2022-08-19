<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;

use App\Models\Ticket;
use App\Models\Server;
use App\Models\TicketComment;
use App\Models\TicketCategory;
use App\Models\TicketBlacklist;
use App\Notifications\Ticket\User\CreateNotification;
use App\Notifications\Ticket\Admin\AdminCreateNotification;
use App\Notifications\Ticket\Admin\AdminReplyNotification;


class TicketsController extends Controller
{
    public function index()
    {
        $tickets = Ticket::where("user_id", Auth::user()->id)->paginate(10); 
        $ticketcategories = TicketCategory::all();
        
        return view("ticket.index", compact("tickets", "ticketcategories"));
    }
    public function create() {
        #check in blacklist
        $check = TicketBlacklist::where('user_id', Auth::user()->id)->first();
        if($check && $check->status == "True"){
            return redirect()->route('ticket.index')->with('error', __("You can't make a ticket because you're on the blacklist for a reason: '" . $check->reason . "', please contact the administrator"));
        }
        $ticketcategories = TicketCategory::all();
        $servers = Auth::user()->servers;
        return view("ticket.create", compact("ticketcategories", "servers"));
    }
    public function store(Request $request) {
        $this->validate($request, array(
        	"title" => "required", 
        	"ticketcategory" => "required", 
        	"priority" => "required", 
        	"message" => "required")
    	);
        $ticket = new Ticket(array(
        	"title" => $request->input("title"), 
        	"user_id" => Auth::user()->id, 
        	"ticket_id" => strtoupper(Str::random(5)), 
        	"ticketcategory_id" => $request->input("ticketcategory"), 
        	"priority" => $request->input("priority"), 
        	"message" => $request->input("message"), 
        	"status" => "Open",
            "server" => $request->input("server"))
   		);
        $ticket->save();
        $user = Auth::user();
        $admin = User::where('role', 'admin')->orWhere('role', 'mod')->get();
        $user->notify(new CreateNotification($ticket));
        Notification::send($admin, new AdminCreateNotification($ticket, $user));
        
        return redirect()->route('ticket.index')->with('success', __('A ticket has been opened, ID: #') . $ticket->ticket_id);
    }
    public function show($ticket_id) {
        $ticket = Ticket::where("ticket_id", $ticket_id)->firstOrFail();
        $ticketcomments = $ticket->ticketcomments;
        $ticketcategory = $ticket->ticketcategory;
        $server = Server::where('id', $ticket->server)->first();
        return view("ticket.show", compact("ticket", "ticketcategory", "ticketcomments", "server"));
    }
    public function reply(Request $request) {
        #check in blacklist
        $check = TicketBlacklist::where('user_id', Auth::user()->id)->first();
        if($check && $check->status == "True"){
            return redirect()->route('ticket.index')->with('error', __("You can't reply a ticket because you're on the blacklist for a reason: '" . $check->reason . "', please contact the administrator"));
        }
        $this->validate($request, array("ticketcomment" => "required"));
        $ticket = Ticket::where('id', $request->input("ticket_id"))->firstOrFail();
        $ticket->status = "Client Reply";
        $ticket->update();
        $ticketcomment = TicketComment::create(array(
        	"ticket_id" => $request->input("ticket_id"), 
        	"user_id" => Auth::user()->id, 
        	"ticketcomment" => $request->input("ticketcomment"), 
        	"message" => $request->input("message")
        ));
        $user = Auth::user();
        $admin = User::where('role', 'admin')->orWhere('role', 'mod')->get();
        $newmessage = $request->input("ticketcomment");
        Notification::send($admin, new AdminReplyNotification($ticket, $user, $newmessage));
        return redirect()->back()->with('success', __('Your comment has been submitted'));
    }

    public function dataTable()
    {
        $query = Ticket::where("user_id", Auth::user()->id)->get();

        return datatables($query)
            ->addColumn('category', function (Ticket $tickets) {
                return $tickets->ticketcategory->name;
            })
            ->editColumn('title', function (Ticket $tickets) {
                return '<a class="text-info"  href="' . route('ticket.show', ['ticket_id' => $tickets->ticket_id]) . '">' . "#" . $tickets->ticket_id . " - " . $tickets->title . '</a>';
            })
            ->editColumn('status', function (Ticket $tickets) {
                switch ($tickets->status) {
                    case 'Open':
                        $badgeColor = 'badge-success';
                        break; 
                    case 'Closed':
                        $badgeColor = 'badge-danger';
                        break;
                    case 'Answered':
                        $badgeColor = 'badge-info';
                        break;
                    default:
                        $badgeColor = 'badge-warning';
                        break;
                }

                return '<span class="badge ' . $badgeColor . '">' . $tickets->status . '</span>';
            })
            ->editColumn('updated_at', function (Ticket $tickets) {
                return $tickets->updated_at ? $tickets->updated_at->diffForHumans() : '';
            })
            ->rawColumns(['category', 'title', 'status', 'updated_at'])
            ->make(true);
    }
}
