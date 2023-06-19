<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\VirtualPrivateServer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class VirtualPrivateServerController extends Controller
{
    public function checkPayments(){
        //foreach(vps::get())
        //dd(VirtualPrivateServer::get());
    }

    public function index()
    {
        return view('admin.vps.index');
    }

    public function show()
    {
        return redirect()->route('admin.vps.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View
     */
    public function create()
    {
        return view('admin.vps.create', [
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|min:0',
            'price' => 'required|integer|min:0',
        ]);

        $vps = new VirtualPrivateServer();
        $vps->description = $request->description;
        $vps->user_id = $request->user_id;
        $vps->price = $request->price*100;
        $vps->uuid = $request->uuid;
        if($request->last_payment) $vps->last_payment = Carbon::createFromTimeString($request->last_payment);
        $vps->save();

        return redirect()->route('admin.vps.index')->with('success', __('VPS has been created!'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  vps  $vps
     * @return Application|Factory|View
     */
    public function edit(VirtualPrivateServer $vps)
    {
        $vps = $vps->first();
        if($vps->last_payment) $vps->last_payment = Carbon::createFromTimeString($vps->last_payment)->format("d-m-Y H:i:s");
        return view('admin.vps.edit', [
            'vps' => $vps,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  Partner  $partner
     * @return RedirectResponse
     */
    public function update(Request $request, VirtualPrivateServer $vps)
    {
        //dd($request);
        $request->validate([
            'user_id' => 'required|integer|min:0',
            'price' => 'required|integer|min:0',
        ]);

        $vps->first();
        dd($vps);
        $vps->description = $request->description;
        $vps->user_id = $request->user_id;
        $vps->price = $request->price*100;
        $vps->uuid = $request->uuid;
        if($request->last_payment)  $vps->last_payment = Carbon::createFromTimeString($request->last_payment);
        $vps->save();

        return redirect()->route('admin.vps.index')->with('success', __('VPS has been updated!'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Partner  $partner
     * @return RedirectResponse
     */
    public function destroy(VirtualPrivateServer $vps)
    {
        
        $vps->first()->delete();

        return redirect()->back()->with('success', __('VPS has been removed!'));
    }



    public function dataTable()
    {
        $query = VirtualPrivateServer::query();

        return datatables($query)
            ->addColumn('actions', function (VirtualPrivateServer $vps) {
                return '
                            <a data-content="'.__('Edit').'" data-toggle="popover" data-trigger="hover" data-placement="top" href="'.route('admin.vps.edit', $vps->id).'" class="btn btn-sm btn-info mr-1"><i class="fas fa-pen"></i></a>
                           <form class="d-inline" onsubmit="return submitResult();" method="post" action="'.route('admin.vps.destroy', $vps->id).'">
                            '.csrf_field().'
                            '.method_field('DELETE').'
                           <button data-content="'.__('Delete').'" data-toggle="popover" data-trigger="hover" data-placement="top" class="btn btn-sm btn-danger mr-1"><i class="fas fa-trash"></i></button>
                       </form>
                ';
            })
            ->addColumn('user', function (VirtualPrivateServer $vps) {
                return ($user = User::where('id', $vps->user_id)->first()) ? '<a href="'.route('admin.users.show', $vps->user_id).'">'.$user->name.'</a>' : __('Unknown user');
            })
            ->editColumn('last_payment', function (VirtualPrivateServer $vps) {
                return $vps->last_payment ? $vps->last_payment : 'never';
            })
            ->editColumn('created_at', function (VirtualPrivateServer $vps) {
                return $vps->created_at ? $vps->created_at->diffForHumans() : '';
            })
            ->editColumn('price', function (VirtualPrivateServer $vps) {
                return $vps->price ? $vps->price/100 : '';
            })
            ->rawColumns(['user', 'actions'])
            ->make();
    }
}
