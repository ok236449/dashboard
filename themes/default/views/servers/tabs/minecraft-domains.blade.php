<div class="tab-pane mt-3" id="minecraft-domains">
    <div class="row">
        <div class="col-md-6 p-3">
            <h6 class="card-title"><i class="fas fa-map-signs mr-2"></i>{{ __('Your Minecraft Vagonbrei.eu subdomain') }}:</h6><br>
            <div class="p-0">
                @foreach($minecraft_subdomains as $minecraft_subdomain)
                    <div class="form-group mb-1">
                        <div class="custom-control p-0">
                            <span class="btn badge @if($minecraft_subdomain->port == $main_port)badge-success @else badge-secondary @endif mt-2" style="font-size: 20px"><i class="fa fa-link mr-2"></i>
                                <span onclick="minecraft_onClickCopy('{{$minecraft_subdomain->subdomain_prefix . $minecraft_subdomain->subdomain_suffix}}')" style="cursor: pointer;" id="web_connected_domain">{{$minecraft_subdomain->subdomain_prefix . $minecraft_subdomain->subdomain_suffix}}</span>
                            </span>
                            <i class="fas fa-arrow-right mt-2" style="font-size: 20px"></i>
                            <span class="btn badge @if($minecraft_subdomain->port == $main_port)badge-success @else badge-secondary @endif mt-2 mr-2" style="font-size: 21px">
                                <span onclick="minecraft_onClickCopy('{{$minecraft_subdomain->node_domain . ':' . $minecraft_subdomain->port}}')" style="cursor: pointer;" >{{explode('.', $minecraft_subdomain->node_domain)[0] . ':' . $minecraft_subdomain->port}}</span>
                            </span>
                            <button type="button" class="btn btn-primary badge mt-2 mr-1" style="font-size: 20px" onclick="minecraft_refreshSubdomain('{{$minecraft_subdomain->subdomain_prefix}}', '{{$minecraft_subdomain->subdomain_suffix}}')"><i class="fa fa-sync-alt"></i></button>
                            <button type="button" class="btn btn-danger badge mt-2" style="font-size: 20px" onclick="minecraft_unlinkSubdomain('{{$minecraft_subdomain->subdomain_prefix}}', '{{$minecraft_subdomain->subdomain_suffix}}')"><i class="fa fa-trash"></i></button>
                            <i data-toggle="popover" data-trigger="hover" data-html="true"
                                data-content="
                                {{__('This is your subdomain you have linked. It will be setup automatically.')}}"
                                style="font-size: 20px" class="fas fa-info-circle m-1">
                            </i>
                        </div>
                        @if($minecraft_subdomain->port != $main_port)<p class="mb-0" style="color: yellow">{{__("The primary port of your server changed since you linked your subdomain. Please click the refresh button below to update the DNS record.")}}</p>@endif
                    </div>
                @endforeach
            </div>
            @if(!$minecraft_subdomains->count()) <p class="mb-2" style="color: yellow">{{__("You haven't linked any subdomain")}}.</p> @endif
            <div class="form-group mb-3">
                <label for="minecraft_subdomain_prefix">{{ __('Link a new subdomain') }}:</label>
                <div class="custom-control p-0" style="display:flex; flex-direction:row;">
                    <input x-model="minecraft_subdomain_prefix" id="minecraft_subdomain_prefix" name="minecraft_subdomain_prefix" type="text" required placeholder="{{__('something')}}" onchange="minecraft_checkAvailability('subdomain');"
                        class="form-control @error('minecraft_subdomain_prefix') is-invalid @enderror">

                    <select id="minecraft_subdomain_suffix" style="width:auto" class="custom-select ml-2" name="minecraft_subdomain_suffix" onchange="minecraft_checkAvailability('subdomain');" required autocomplete="off">
                        @php $i = 0;@endphp
                        @foreach($availableSubdomains as $key => $as)
                            @if(in_array('minecraft', $as))<option value="{{$key}}" @if($i == 0) selected @endif>{{$key}}</option>@endif
                            @php $i++; @endphp
                        @endforeach
                    </select>
                    <select id="subdomain_minecraft_port" style="width:auto" class="custom-select ml-2" name="web_port" required disabled autocomplete="off">
                        <option selected value="{{$main_port}}">{{$main_port}}</option>
                    </select>
                </div>
                <div style="margin-top: 4px; margin-bottom: -8px">
                    <small><strong id="minecraft_subdomain_error" style="color: red"></strong></small>
                    <small><strong id="minecraft_subdomain_availability"></strong></small>
                </div>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2 mt-2">
                {{__('Here you can create your own subdomain for free. The subdomain will automatically setup itself and will be ready to use in just a moment.')}}
            </div>
            <button type="button" class="btn btn-primary mt-3" style="margin-bottom: -20px; float: right" onclick="minecraft_linkSubdomain()"><i class="fa fa-link mr-2"></i>{{__('Link subdomain')}}</button>
            
        </div>

        <div class="col-md-6 p-3">
            <h6 class="card-title"><i class="fas fa-map-signs mr-2"></i>{{ __('Linking your own Minecraft domain') }}:</h6><br>
            
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; font-size:14px" class="p-2 mt-2">
                {{__('If you happen to have your own domain, you can link it to your minecraft server. All you need to do is set a domain record like this at your domain registrar')}}:<br>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                <span style="margin-bottom: 0px">{{__('Type')}}: <b>SRV</b><br>{{__('Name')}}: <b>(mc.){{__('example.com')}}</b><br>{{__('Service')}}: <b>_minecraft</b><br>{{__('Protocol')}}: <b>TCP</b><br>{{__('Priority')}}: <b>5</b><br>{{__('Weight')}}: <b>5</b><br>{{__('Port')}}: <b>{{$main_port}}</b><br>{{__('Target')}}: <b>{{$address}}</b></span>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                <span style="margin-bottom: 0px">{{__('Depending on your domain registrar, the newly created record may take effect instantly (cloudflare) or it might take up to 24 hours (others). Please be patient.')}}</span>
            </div>
        </div>
    </div>
    <script>
        async function minecraft_checkAvailability(type){
            //if(document.getElementById('subdomain_prefix').value.length>=3&&document.getElementById('subdomain_prefix').value<=100)
            document.getElementById('minecraft_' + type + '_availability').innerText = "";
            if(type == 'subdomain' && document.getElementById('minecraft_subdomain_prefix').value.length) minecraft_sendPost(`{{route('domain.checkAvailability')}}`, {type: type, target:'minecraft', subdomain_prefix: document.getElementById('minecraft_subdomain_prefix').value, subdomain_suffix: document.getElementById('minecraft_subdomain_suffix').value}, false);
            else if(type == 'domain' && document.getElementById('minecraft_domain').value.length) minecraft_sendPost(`{{route('domain.checkAvailability')}}`, {type: type, target: 'minecraft', domain: document.getElementById('minecraft_domain').value}, false);
        }
        function minecraft_linkSubdomain(){
            minecraft_sendPost(`{{route('subdomain.minecraft.link')}}`, {subdomain_prefix: document.getElementById('minecraft_subdomain_prefix').value, subdomain_suffix: document.getElementById('minecraft_subdomain_suffix').value, server_id: `{{$server->identifier}}`});
        }
        function minecraft_unlinkSubdomain(subdomain_prefix, subdomain_suffix){
            minecraft_sendPost(`{{route('subdomain.minecraft.unlink')}}`, {subdomain_prefix: subdomain_prefix, subdomain_suffix: subdomain_suffix, server_id: `{{$server->identifier}}`});
        }
        function minecraft_refreshSubdomain(subdomain_prefix, subdomain_suffix){
            minecraft_sendPost(`{{route('subdomain.minecraft.refresh')}}`, {subdomain_prefix: subdomain_prefix, subdomain_suffix: subdomain_suffix, server_id: `{{$server->identifier}}`});
        }
        
        async function minecraft_sendPost(url, body, show_info=true){
            if(show_info==true) showInfo();
            let res = await fetch(url, {
                method: "POST",
                body: JSON.stringify(body),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    "Content-Type": "application/json",
                    'Accept': 'application/json'
                }
            });
            let json = await res.json();
            
            if(show_info==true){
                showStatus(res.status);
                if(res.status==200/*&&!url.includes('refresh')*/) location.reload();
                if(document.getElementById('minecraft_subdomain_error')&&json.errors&&json.errors.minecraft_subdomain){
                    document.getElementById('minecraft_subdomain_error').innerText = json.errors.minecraft_subdomain;
                    document.getElementById('minecraft_subdomain_prefix').classList.add('is-invalid');
                    setTimeout(() => {
                        document.getElementById('minecraft_subdomain_prefix').classList.remove('is-invalid');
                        document.getElementById('minecraft_subdomain_error').innerText = "";
                    }, 3000);
                }
            }
            else {
                if(body.type=='subdomain'){
                    document.getElementById('minecraft_subdomain_availability').innerHTML = ((json.available==true)?('<i class="fas fa-check-circle mr-2"></i>' + `{{__('Domain is available')}}`):('<i class="fas fa-exclamation-triangle mr-2"></i>' + `{{__('Domain is already taken')}}`));
                    document.getElementById('minecraft_subdomain_availability').style.color = ((json.available==true)?'green':'red');
                    //document.getElementById('subdomain_prefix').classList.add('is-invalid');
                    /*setTimeout(() => {
                        document.getElementById('subdomain_prefix').classList.remove('is-invalid');
                        document.getElementById('subdomain_prefix_error').innerText = "";
                    }, 2000);*/
                }
            }
        }
        function showInfo(){
            Swal.fire({
                icon: 'info',
                title: '{{__('Saving...')}}',
                position: 'top-end',
                showConfirmButton: false,
                background: '#343a40',
                toast: true,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }
        function showStatus(status){
            Swal.fire({
                icon: status == 200?'success':'error',
                title: status == 200?'{{__('Saved successfully!')}}':('{{__('An error ocured. Code:')}} ' + status),
                position: 'top-end',
                showConfirmButton: false,
                background: '#343a40',
                toast: true,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });
        }
        function minecraft_onClickCopy(text) {
            if(navigator.clipboard) {
                navigator.clipboard.writeText(text).then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: '{{ __("URL copied to clipboard")}}',
                        position: 'top-middle',
                        showConfirmButton: false,
                        background: '#343a40',
                        toast: false,
                        timer: 1000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    })
                })
            } else {
                console.log('Browser Not compatible')
            }
        }
        if(document.getElementById('minecraft_subdomain_prefix'))document.getElementById('minecraft_subdomain_prefix').value = "";
        if(document.getElementById('minecraft_domain'))document.getElementById('minecraft_domain').value = "";
    </script>
</div>

