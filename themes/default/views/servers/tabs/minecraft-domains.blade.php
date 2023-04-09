<div class="tab-pane mt-3" id="minecraft-domains">
    <div class="row">
        <div class="col-md-6 p-3">
            <h6 class="card-title"><i class="fas fa-map-signs mr-2"></i>{{ __('Your Minecraft Vagonbrei.eu subdomain') }}:</h6><br>
            @if($minecraft_subdomain)
            <div class="form-group mb-3">
                @if($minecraft_subdomain->port!=$minecraft_port)<p class="mb-0" style="color: yellow">{{__("The primary port of your server changed since you linked your subdomain. Please click the refresh button below.")}}</p>@endif
                <div class="custom-control p-0">
                    <span class="btn badge badge-success mt-2 mr-1" style="font-size: 20px"><i class="fa fa-link mr-2"></i>
                        <span onclick="onClickCopy('minecraft_connected_subdomain')" style="cursor: pointer;"><span id="minecraft_connected_subdomain_prefix">{{$minecraft_subdomain->subdomain_prefix}}</span><span id="minecraft_connected_subdomain_suffix">{{$minecraft_subdomain->subdomain_suffix}}</span></span>
                    </span>
                    <button type="button" class="btn btn-primary badge mt-2 mr-1" style="font-size: 20px" onclick="minecraft_refreshSubdomain()"><i class="fa fa-sync-alt mr-2"></i>{{__('Refresh')}}</button>
                    <button type="button" class="btn btn-danger badge mt-2" style="font-size: 20px" onclick="minecraft_unlinkSubdomain()"><i class="fa fa-trash mr-2"></i>{{__('Unlink')}}</button>
                </div>
                <span>
                    <small><strong id="minecraft_subdomain_error" style="color: red" class="pt-3"></strong></small>
                </span>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2">
                {{__('This is your subdomain you have linked. In case you changed your server port or migrated your server to another node, please press the refresh button.')}}
            </div>
            @else
            <p class="mb-0" style="color: yellow">{{__("You haven't linked any subdomain")}}.</p>
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
                        <!---<p class="m-0 mt-2 ml-1" style="font-size: 16px">.mc.vagonbrei.eu</p>-->
                </div>
                <div style="margin-top: 4px; margin-bottom: -8px">
                    <small><strong id="minecraft_subdomain_prefix_error" style="color: red"></strong></small>
                    <small><strong id="minecraft_subdomain_availability"></strong></small>
                </div>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2 mt-2">
                {{__('Here you can create your own subdomain for free. The subdomain will automatically setup itself and will be ready to use within 10 minutes.')}}
            </div>
            <button type="button" class="btn btn-primary mt-3" style="margin-bottom: -20px; float: right" onclick="minecraft_linkSubdomain()"><i class="fa fa-link mr-2"></i>{{__('Link subdomain')}}</button>
            @endif
        </div>

        <div class="col-md-6 p-3">
            <h6 class="card-title"><i class="fas fa-map-signs mr-2"></i>{{ __('Your own Minecraft domain') }}:</h6><br>
            @if($minecraft_domain)
            <div class="form-group mb-3">
                @if($minecraft_domain->port!=$minecraft_port)<p class="mb-0" style="color: yellow">{{__("The primary port of your server changed since you linked your domain. Please click the refresh button below and update your DNS records.")}}</p>@endif
                <div class="custom-control p-0">
                    <span class="btn badge badge-success mt-2 mr-1" style="font-size: 20px"><i class="fa fa-link mr-2"></i>
                        <span onclick="minecraft_onClickCopy('minecraft_connected_domain')" style="cursor: pointer;" id="minecraft_connected_domain">{{$minecraft_domain->domain}}</span>
                    </span>
                    <button type="button" class="btn btn-primary badge mt-2 mr-1" style="font-size: 20px" onclick="minecraft_refreshDomain()"><i class="fa fa-sync-alt mr-2"></i>{{__('Refresh')}}</button>
                    <button type="button" class="btn btn-danger badge mt-2" style="font-size: 20px" onclick="minecraft_unlinkDomain()"><i class="fa fa-trash mr-2"></i>{{__('Unlink')}}</button>
                </div>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2 mt-3">
                {{__('This is your domain you have linked. You will need to set these records at your domain registrar')}}:<br>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                SRV {{$minecraft_domain->domain}} _minecraft _tcp 1 1 1 {{$minecraft_port}} {{$minecraft_domain->domain}}<br>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                CNAME {{$minecraft_domain->domain}} {{$address}}

            </div>
            @else
            <p class="mb-0" style="color: yellow">{{__("You haven't linked any domain")}}.</p>
            <div class="form-group mb-3">
                <label for="minecraft_domain">{{ __('Link a new domain') }}:</label>
                <div class="custom-control p-0" style="display:flex; flex-direction:row;">
                    <input x-model="minecraft_domain" id="minecraft_domain" name="minecraft_domain" type="text" required placeholder="{{__('play.example.com')}}" onchange="minecraft_checkAvailability('domain');" oninput="document.getElementById('minecraft_your_domain').innerText = this.value; document.getElementById('minecraft_your_domain2').innerText = this.value; document.getElementById('minecraft_your_domain3').innerText = this.value;"
                        class="form-control @error('minecraft_domain') is-invalid @enderror">
                </div>
                <div style="margin-top: 4px; margin-bottom: -8px">
                    <small><strong id="minecraft_domain_field_error" style="color: red"></strong></small>
                    <small><strong id="minecraft_domain_availability"></strong></small>
                </div>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2 mt-2">
                {{__('Here you can link your own domain (if you have one). You will need to set these records at your domain registrar')}}:<br>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                SRV <span id="minecraft_your_domain">{{__('play.example.com')}}</span> _minecraft _tcp 1 1 1 {{($minecraft_domain&&$domain->bungee_active)?env('BUNGEECORD_PORT'):$minecraft_port}} <span id="minecraft_your_domain2">{{__('play.example.com')}}</span><br>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                CNAME <span id="minecraft_your_domain3">{{__('play.example.com')}}</span> {{($minecraft_domain&&$minecraft_domain->bungee_active)?env('BUNGEECORD_ADDRESS'):$address}}

            </div>
            <span>
                <small><strong id="minecraft_domain_error" style="color: red"></strong></small>
            </span>
            <button type="button" class="btn btn-primary mt-3" style="margin-bottom: -20px; float: right" onclick="minecraft_linkDomain()"><i class="fa fa-link mr-2"></i>{{__('Link domain')}}</button>
            @endif
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
        function minecraft_unlinkSubdomain(){
            minecraft_sendPost(`{{route('subdomain.minecraft.unlink')}}`, {server_id: `{{$server->identifier}}`});
        }
        function minecraft_refreshSubdomain(){
            minecraft_sendPost(`{{route('subdomain.minecraft.refresh')}}`, {server_id: `{{$server->identifier}}`});
        }
        function minecraft_linkDomain(){
            minecraft_sendPost(`{{route('domain.minecraft.link')}}`, {domain: document.getElementById('minecraft_domain').value, server_id: `{{$server->identifier}}`});
        }
        function minecraft_unlinkDomain(){
            minecraft_sendPost(`{{route('domain.minecraft.unlink')}}`, {server_id: `{{$server->identifier}}`});
        }
        function minecraft_refreshDomain(){
            minecraft_sendPost(`{{route('domain.minecraft.refresh')}}`, {server_id: `{{$server->identifier}}`});
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
            if(!'{{$minecraft_subdomain?1:0}}')document.getElementById('minecraft_subdomain_availability').innerHTML = "";
            if(!'{{$minecraft_domain?1:0}}') document.getElementById('minecraft_domain_availability').innerHTML = "";
            if(show_info==true){
                showStatus(res.status);
                if(res.status==200/*&&!url.includes('refresh')*/) location.reload();
                if(document.getElementById('minecraft_subdomain_prefix_error')&&json.errors&&json.errors.minecraft_subdomain){
                    document.getElementById('minecraft_subdomain_prefix_error').innerText = json.errors.minecraft_subdomain;
                    document.getElementById('minecraft_subdomain_prefix').classList.add('is-invalid');
                    setTimeout(() => {
                        document.getElementById('minecraft_subdomain_prefix').classList.remove('is-invalid');
                        document.getElementById('minecraft_subdomain_prefix_error').innerText = "";
                    }, 3000);
                }
                else if(document.getElementById('minecraft_domain_field_error')&&json.errors&&json.errors.minecraft_domain){
                    document.getElementById('minecraft_domain_field_error').innerText = json.errors.minecraft_domain;
                    setTimeout(() => {
                        document.getElementById('minecraft_domain_field_error').innerText = "";
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
                else if(body.type=='domain'){
                    document.getElementById('minecraft_domain_availability').innerHTML = ((json.available==true)?('<i class="fas fa-check-circle mr-2"></i>' + `{{__('Domain is available')}}`):('<i class="fas fa-exclamation-triangle mr-2"></i>' + `{{__('Domain is already taken')}}`));
                    document.getElementById('minecraft_domain_availability').style.color = ((json.available==true)?'green':'red');
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
        function minecraft_onClickCopy(element) {
            if(navigator.clipboard) {
                navigator.clipboard.writeText(document.getElementById('minecraft_' + element + '_prefix').innerText + document.getElementById('minecraft_' + element + '_suffix').innerText).then(() => {
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

