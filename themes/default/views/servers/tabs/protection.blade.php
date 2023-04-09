<style>
.protect_container{
    display: grid;
    grid-template-areas: "switch status";
    grid-template-columns: 1fr 2fr;
    min-height: 4rem;
    padding: 1rem;
    padding-left: 0;
    gap: 1rem;
}

.protect_switch{
    grid-area: switch;
    text-align: center
}

.protect_status{
    grid-area: status;
    
}

.protect_inner_container{
    border: solid gray 1px;
    border-radius: 1rem;
    padding: 1rem;
    display: grid;
    grid-template-areas: "icon features"
                         "text features";
    grid-template-columns: 1fr 1fr;
}

.protect_status div svg{
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

.protect_status div h3{
    grid-area: text;
    margin: auto;
    font-size: 1.2rem;
}

.protection_features{
    grid-area: features;
    margin-left: 1rem;
    display: grid;
    grid-auto-columns: "title"
                        "list";
    grid-template-rows: min-content auto;
    text-align: center;
}

@keyframes green_icon{
    from{
        scale: 10%;
        rotate: 50deg;
        fill: white;
    }

    to{
        scale: 100%;
        rotate: 0deg;
        fill: lime;
    }
}

.protection_features *{
    padding: 0;
    margin: 0;
    text-align: unset;
}

.protection_features ul{
    text-align: left;
    list-style: none;
    margin-top: 5px;
}


.protection_features ul li span{
    float: right;
}

.protection_features h5{
    font-weight: 800;
}

.switch_center{
    display: grid;
    align-self: center;
    justify-self: center;
    align-content: center;
    justify-items: center;
}

#change_protection_button{
    margin: 0 !important;
    margin-top: auto;
    display: inline-block;
}

#protection_info_button{
    margin: 0 !important;
}

#but-contain{
    width: 100% !important;
    display: grid;
    grid-template-areas: "one two";
    grid-template-columns: 1fr 2fr;
    height: 3rem;
    gap:1rem;
    position: relative;
    top: 32%;
}


#but-contain *{
    display: inline-block !important;
}
.compact_paragraph{
    margin: 0 !important;
    color: rgb(182, 182, 182);
    font-size: 1rem;
}


[protected="false"]{
    z-index: 2;

}

@keyframes red_icon{
    from{
        scale: 10%;
        rotate: 50deg;
        fill: white;
    }

    to{
        scale: 100%;
        rotate: 0deg;
        fill: red;
    }
}

@media only screen and (max-width: 782px) {
    .protect_container{
        grid-template-areas: "status"
                              "switch" !important;
        grid-template-columns: 1fr !important;
        grid-auto-rows: auto min-content !important;
    } /* H */

    .protect_inner_container{
        grid-template-areas: "icon"
                            "text"
                            "features";
        grid-template-columns: 1fr;
        grid-auto-rows: 1fr min-content 1fr
    }

    .protection_features{
        margin: 0 !important;
        display: grid;
        grid-auto-columns: "title"
                            "list" !important;
        grid-template-rows: min-content min-content !important;
    }
}

[protected="false"] svg{
    fill: red !important;
    animation-name: red_icon !important;
}

</style>

<script>
    function stikTest(){
        /*Swal.fire({
            icon: 'info',
            title: 'Info',
            position: 'middle',
            showConfirmButton: false,
            background: '#343a40',
            toast: true,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });*/
        Swal.fire({
                icon: 'info',
                title: "{{__('Important information')}}",
                html: `<p class="compact_paragraph">`+`{{__("Connecting to your server through our proxy grants you powerful protection agains't all kinds of bot & other malicious attacks.")}}
                    {{__('Also it allows you to enter our Vagonbrei.eu Network, which you can configure in the next tab.')}}<br><br>
                    {{__('If you change this value, the system will automatically enable bungeecord in the spigot.yml config, disable online mode in server.properties and restart your server.')}}<br><br>
                    {{__('If you have your own linked domain, please update your DNS records according the domains tab.')}}<br><br>
                    {{__('Also, if you use a Spigot server (not Paper or Purpur), please download ProtocolLib plugin on your server.')}}
                    </p class="compact_paragraph">`,
            })
    }
</script>

<div class="tab-pane mt-3" id="protection">
    <form method="POST" enctype="multipart/form-data" class="mb-3"
        action="{{ route('servers.settings.update.protection') }}">
        @csrf
        @method('PATCH')

        @if(!$minecraft_subdomain&&!$minecraft_domain)<p class="mb-0" style="color: yellow; text-align: center;">{{__("You don't have any linked Minecraft domain/subdomain. Please visit the Minecraft-domains tab first.")}}</p>@endif

        <div class="protect_container">
            <div class="protect_switch">
                <h5>{{__('Connect via our proxy')}}:</h5>
                <div class="switch_wrapper switch_center">   
                    <label class="switch">
                        <input type="checkbox" name="bungee_active" id="bungee_active" @if(($minecraft_domain&&$minecraft_domain->bungee_active)||($minecraft_subdomain&&$minecraft_subdomain->bungee_active))checked @endif>
                        <span class="slider round"></span>
                    </label>
                </div>
                <div id="but-contain">
                    <button class="btn btn-secondary mt-3 ml-3" id="protection_info_button" onclick="stikTest()" type="button">{{__('Information')}}</button>
                    <button id="change_protection_button" class="btn btn-primary mt-3 ml-3">{{ __('Submit') }}</button>
                </div>
            </div>
            <div class="protect_status">
                @if(($minecraft_domain&&$minecraft_domain->bungee_active)||($minecraft_subdomain&&$minecraft_subdomain->bungee_active))
                    <div protected="true" class="protect_inner_container card-footer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd" d="M12.516 2.17a.75.75 0 00-1.032 0 11.209 11.209 0 01-7.877 3.08.75.75 0 00-.722.515A12.74 12.74 0 002.25 9.75c0 5.942 4.064 10.933 9.563 12.348a.749.749 0 00.374 0c5.499-1.415 9.563-6.406 9.563-12.348 0-1.39-.223-2.73-.635-3.985a.75.75 0 00-.722-.516l-.143.001c-2.996 0-5.717-1.17-7.734-3.08zm3.094 8.016a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                        </svg>
                        <h3>{{__('Your server is protected!')}}</h3>
                        <div class="protection_features ">
                            <h5>{{__('You are protected against')}}:</h5>
                            <ul>
                                <li>{{__('Bot attacks')}}: <span>✅</span></li>
                                <li>{{__('DDOS attacks')}}: <span>✅</span></li>
                                <li>{{__('VPN connections')}}: <span>✅</span></li>
                                <li>{{__('Blacklisted IP adresses')}}: <span>✅</span></li>
                                <li>{{__('People who mix their porridge')}}: <span>❌</span></li>
                            </ul>
                        </div>
                    </div>
                @else
                    <div protected="false" class="protect_inner_container card-footer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                            <path fill-rule="evenodd" d="M11.484 2.17a.75.75 0 011.032 0 11.209 11.209 0 007.877 3.08.75.75 0 01.722.515 12.74 12.74 0 01.635 3.985c0 5.942-4.064 10.933-9.563 12.348a.749.749 0 01-.374 0C6.314 20.683 2.25 15.692 2.25 9.75c0-1.39.223-2.73.635-3.985a.75.75 0 01.722-.516l.143.001c2.996 0 5.718-1.17 7.734-3.08zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zM12 15a.75.75 0 00-.75.75v.008c0 .414.336.75.75.75h.008a.75.75 0 00.75-.75v-.008a.75.75 0 00-.75-.75H12z" clip-rule="evenodd" />
                        </svg>


                        <h3>{{__("Your server isn't protected!")}}</h3>
                        <div class="protection_features ">
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
                @endif
            </div>
            <input type="hidden" name="server_id" value="{{$server->identifier}}">
        </div>
    </form>
</div>
