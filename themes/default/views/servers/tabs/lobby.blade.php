<style>
.lobby_container{
    display: grid;
    grid-template-areas: "switch status";
    grid-template-columns: 1fr 2fr;
    min-height: 4rem;
    padding: 1rem;
    padding-left: 0;
    gap: 1rem;
}

.lobby_switch{
    grid-area: switch;
    text-align: center
}

.lobby_status{
    grid-area: status;
    
}

.lobby_inner_container{
    border: solid gray 1px;
    border-radius: 1rem;
    padding: 1rem;
    display: grid;
    grid-template-areas: "icon features"
                        "text features";
    grid-template-columns: auto 1fr;
}

.lobby_status div svg{
    max-height: 8rem;
    margin: auto;
    grid-area: icon;
    
    animation-name: green_icon;
    animation-fill-mode: forwards;
    animation-duration: 300ms;
    animation-delay: 500ms;
    animation-timing-function: ease-in;

    scale: 10%;
    rotate: 50deg;
}

.lobby_status div h3{
    grid-area: text;
    margin: auto;
    font-size: 1.2rem;
}

.lobby_features{
    grid-area: features;
    margin-left: 1rem;
    display: grid;
    grid-auto-columns: "title"
                        "list";
    grid-template-rows: min-content auto;
    text-align: center;
}

.lobby_features *{
    padding: 0;
    margin: 0;
    text-align: unset;
}

.lobby_features ul{
    text-align: left;
    list-style: none;
    margin-top: 5px;
}


.lobby_features ul li span{
    float: right;
}

.lobby_features h5{
    font-weight: 800;
}

#change_lobby_button{
    margin: 0 !important;
    margin-top: auto;
    display: inline-block;
}

#lobby_info_button{
    margin: 0 !important;
}


@media only screen and (max-width: 782px) {
    .lobby_container{
        grid-template-areas: "status"
                            "switch" !important;
        grid-template-columns: 1fr !important;
        grid-auto-rows: auto min-content !important;
    } /* H */

    .lobby_inner_container{
        grid-template-areas: "icon"
                            "text"
                            "features";
        grid-template-columns: 1fr;
        grid-auto-rows: 1fr min-content 1fr
    }

    .lobby_features{
        margin: 0 !important;
        display: grid;
        grid-auto-columns: "title"
                            "list" !important;
        grid-template-rows: min-content min-content !important;
    }
}

</style>
<div class="tab-pane mt-3" id="lobby">
    <form method="POST" enctype="multipart/form-data" class="mb-3"
        action="{{ route('servers.settings.update.lobby') }}">
        @csrf
        @method('PATCH')

            

        
        <div class="lobby_container">
            <div class="lobby_switch">
                @if(!(($minecraft_subdomain&&$minecraft_subdomain->show_on_lobby)||($minecraft_domain&&$minecraft_domain->show_on_lobby)))<p class="mb-0" style="color: yellow; text-align: center;">{{__("You don't have our protection enabled. Please enable it in the Protection tab.")}}</p>
                @else <p class="mb-0"></p> @endif
                <h5>{{__('List your server on our lobby')}}:</h5>
                <div class="switch_wrapper switch_center">   
                    <label class="switch">
                        <input type="checkbox" name="show_on_lobby" id="show_on_lobby" @if(($minecraft_domain&&$minecraft_domain->show_on_lobby)||($minecraft_subdomain&&$minecraft_subdomain->show_on_lobby))checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div id="but-contain">
                    <button class="btn btn-secondary mt-3 ml-3" id="lobby_info_button" onclick="stikTest()" type="button">{{__('Information')}}</button>
                    <button id="change_lobby_button" class="btn btn-primary mt-3 ml-3">{{ __('Submit') }}</button>
                </div>
            </div>
            <div class="lobby_status">
                
                <div protected="false" class="lobby_inner_container card-footer">
                    <div style='display: grid; grid-template-areas: "faggot";'>
                        <img style="border-radius: 1rem; grid-area: faggot; height: 167px;" src="{{asset('images/minecraft/lobby.png')}}" alt="minecraft_lobby">
                        <h3 style="color: black; padding: 10px; text-decoration: underline; grid-area: faggot; z-index: 5; float: left; width: 100%; height: 100%; display: flex; flex-direction: column-reverse;">{{__("Vagonbrei.eu lobby")}}</h3>
                    </div>
                    
                    <div class="lobby_features ">
                        <h5>{{__('You are protected against')}}:</h5>
                        <ul>
                            <li>{{__('Bot attacks')}}: <span>❌</span></li>
                            <li>{{__('DDOS attacks')}}: <span>✅</span></li>
                            <li>{{__('VPN connections')}}: <span>❌</span></li>
                            <li>{{__('Blacklisted IP adresses')}}: <span>❌</span></li>
                            <li>{{__('People who mix their porridge')}}: <span>❌</span></li>
                        </ul>
                    </div>
                </div>
            </div>
            <input type="hidden" name="server_id" value="{{$server->identifier}}">
        </div>
    </form>
</div>
