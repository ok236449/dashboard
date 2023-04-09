<div class="tab-pane mt-3" id="web-domains">
    @if(!count($web_ports))<p class="mb-0" style="color: red; text-align: center">{{__("This server does not have any available web ports. Please allocate some on pterodactyl first.")}}</p>@endif
    <div class="row">
        <div class="col-md-6 col-12 p-3">
            <h6 class="card-title"><i class="fas fa-map-signs mr-2"></i>{{ __('Your Vagonbrei.eu web subdomain') }}:</h6><br>
            @if($web_subdomain)
            <div class="form-group mb-3">
                <div class="custom-control p-0">
                    <span class="btn badge badge-success mt-2 mr-1" style="font-size: 20px"><i class="fa fa-link mr-2"></i>
                        <span onclick="onClickCopy('web_connected_subdomain')" style="cursor: pointer;"><span id="web_connected_subdomain_prefix">{{$web_subdomain->subdomain_prefix}}</span><span id="web_connected_subdomain_suffix">{{$web_subdomain->subdomain_suffix}}</span></span>
                    </span>
                    <button type="button" class="btn btn-danger badge mt-2" style="font-size: 20px" onclick="web_unlinkSubdomain()"><i class="fa fa-trash mr-2"></i>{{__('Unlink')}}</button>
                </div>
                <span>
                    <small><strong id="web_subdomain_error" style="color: red" class="pt-3"></strong></small>
                </span>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2">
                {{__('This is your subdomain you have linked. In case you changed your server port or migrated your server to another node, please press the refresh button.')}}
            </div>
            @else
            <p class="mb-2" style="color: yellow">{{__("You haven't linked any subdomain")}}.</p>
            <div class="form-group mb-3">
                <label for="web_subdomain_prefix">{{ __('Link a new subdomain') }}:</label>
                <div class="custom-control p-0" style="display:flex; flex-direction:row;">
                    <input x-model="web_subdomain_prefix" id="web_subdomain_prefix" name="web_subdomain_prefix" type="text" required placeholder="{{__('something')}}" onchange="web_checkAvailability('subdomain');"
                        class="form-control @error('web_subdomain_prefix') is-invalid @enderror">

                    <select id="web_subdomain_suffix" style="width:auto" class="custom-select ml-2" name="web_subdomain_suffix" onchange="web_checkAvailability('subdomain');" required autocomplete="off">
                        @php $i = 0;@endphp
                        @foreach($availableSubdomains as $key => $as)
                            @if(in_array('web', $as))<option value="{{$key}}" @if($i == 0) selected @endif>{{$key}}</option>@endif
                            @php $i++; @endphp
                        @endforeach
                    </select>
                    <select id="subdomain_web_port" style="width:auto" class="custom-select ml-2" name="web_port" required autocomplete="off">
                        <option value="" selected disabled style="color: #999;">{{__('Pick port')}}</option>
                        @foreach($web_ports as $key => $op)
                            <option value="{{$op}}">{{$op}}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-top: 4px; margin-bottom: -8px">
                    <small><strong id="web_subdomain_prefix_error" style="color: red"></strong></small>
                    <small><strong id="web_subdomain_availability"></strong></small>
                </div>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2 mt-2">
                {{__('Here you can create your own subdomain for free. The subdomain will automatically setup itself and will be ready to use within 10 minutes.')}}
            </div>
            <button type="button" class="btn btn-primary mt-3" style="margin-bottom: -16px; float: right" onclick="web_linkSubdomain()"><i class="fa fa-link mr-2"></i>{{__('Link subdomain')}}</button>
            @endif
        </div>

        <div class="col-md-6 col-12 p-3">
            <h6 class="card-title"><i class="fas fa-map-signs mr-2"></i>{{ __('Your own web domain') }}:</h6><br>
            @if($web_domain)
            <div class="form-group mb-3">
                <div class="custom-control p-0">
                    <span class="btn badge badge-success mt-2 mr-1" style="font-size: 20px"><i class="fa fa-link mr-2"></i>
                        <span onclick="web_onClickCopy('web_connected_domain')" style="cursor: pointer;" id="web_connected_domain">{{$web_domain->domain}}</span>
                    </span>
                    <button type="button" class="btn btn-danger badge mt-2" style="font-size: 20px" onclick="web_unlinkDomain()"><i class="fa fa-trash mr-2"></i>{{__('Unlink')}}</button>
                </div>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2 mt-3">
                {{__('This is your domain you have linked. You will need to set these records at your domain registrar')}}:<br>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                CNAME {{$web_domain->domain}} {{$address}}

            </div>
            @else
            <p class="mb-2" style="color: yellow">{{__("You haven't linked any domain")}}.</p>
            <div class="form-group mb-3">
                <label for="web_domain">{{ __('Link a new domain') }}:</label>
                <div class="custom-control p-0" style="display:flex; flex-direction:row;">
                    <input x-model="web_domain" id="web_domain" name="web_domain" type="text" required placeholder="{{__('play.example.com')}}" onchange="web_checkAvailability('domain');" oninput="document.getElementById('web_your_domain').innerText = this.value; document.getElementById('web_your_domain2').innerText = this.value; document.getElementById('web_your_domain3').innerText = this.value;"
                        class="form-control @error('web_domain') is-invalid @enderror">
                    <select id="domain_web_port" style="width:auto" class="custom-select ml-2" name="web_port" required autocomplete="off">
                        <option value="" selected disabled style="color: #999;">{{__('Pick port')}}</option>
                        @foreach($web_ports as $key => $op)
                            <option value="{{$op}}">{{$op}}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-top: 4px; margin-bottom: -8px">
                    <small><strong id="web_domain_field_error" style="color: red"></strong></small>
                    <small><strong id="web_domain_availability"></strong></small>
                </div>
            </div>
            <div style="border: 1px; border-style: solid; border-color:dimgrey; border-radius: 5px; min-height:100px; font-size:14px" class="p-2 mt-2">
                {{__('Here you can link your own domain (if you have one). You will need to set these records at your domain registrar')}}:<br>
                <hr style="margin: 1px; padding: 0px; background-color:#696969">
                CNAME <span id="web_your_domain3">{{__('play.example.com')}}</span> {{($web_domain&&$web_domain->bungee_active)?env('BUNGEECORD_ADDRESS'):$address}}

            </div>
            <span>
                <small><strong id="web_domain_error" style="color: red"></strong></small>
            </span>
            <!-- H !-->
            <button type="button" class="btn btn-primary mt-3" style="margin-bottom: -16px; float: right" onclick="web_linkDomain()"><i class="fa fa-link mr-2"></i>{{__('Link domain')}}</button>
            @endif
        </div>
        <div class="col-xl-5 col-md-7 col-12 p-3" style="margin:0 auto; padding-bottom: 0px !important;">
            <div style="border: solid gray 1px; border-radius: 1rem; padding: 1rem; padding-bottom: 0px;">
                <h5 style="text-align: center">{{__('Linking a web domain to your server will grant you')}}:</h5>
                <ul style="list-style: none; padding: 0px">
                    <li>{{__('Protection against attacks on your web')}}: <span style="float:right">✅</span></li>
                    <li>{{__('Automatic SSL certificate (HTTPS)')}}: <span style="float:right">✅</span></li>
                </ul>
            </div>
        </div>
    </div>
    <script>
        async function web_checkAvailability(type){
            //if(document.getElementById('subdomain_prefix').value.length>=3&&document.getElementById('subdomain_prefix').value<=100)
            document.getElementById('web_' + type + '_availability').innerText = "";
            if(type == 'subdomain' && document.getElementById('web_subdomain_prefix').value.length) web_sendPost(`{{route('domain.checkAvailability')}}`, {type: type, target:'web', subdomain_prefix: document.getElementById('web_subdomain_prefix').value, subdomain_suffix: document.getElementById('web_subdomain_suffix').value}, false);
            else if(type == 'domain' && document.getElementById('web_domain').value.length) web_sendPost(`{{route('domain.checkAvailability')}}`, {type: type, target: 'web', domain: document.getElementById('web_domain').value}, false);
        }
        function web_linkSubdomain(){
            web_sendPost(`{{route('subdomain.web.link')}}`, {subdomain_prefix: document.getElementById('web_subdomain_prefix').value, subdomain_suffix: document.getElementById('web_subdomain_suffix').value, web_port: document.getElementById('subdomain_web_port').value, server_id: `{{$server->identifier}}`});
        }
        function web_unlinkSubdomain(){
            web_sendPost(`{{route('subdomain.web.unlink')}}`, {subdomain_prefix: document.getElementById('web_connected_subdomain_prefix').innerText, subdomain_suffix: document.getElementById('web_connected_subdomain_suffix').innerText, server_id: `{{$server->identifier}}`});
        }
        function web_linkDomain(){
            web_sendPost(`{{route('domain.web.link')}}`, {domain: document.getElementById('web_domain').value, web_port: document.getElementById('domain_web_port').value, server_id: `{{$server->identifier}}`});
        }
        function web_unlinkDomain(){
            web_sendPost(`{{route('domain.web.unlink')}}`, {domain: document.getElementById('web_connected_domain').innerText, server_id: `{{$server->identifier}}`});
        }
        
        async function web_sendPost(url, body, show_info=true){
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
            if(!'{{$web_subdomain?1:0}}')document.getElementById('web_subdomain_availability').innerHTML = "";
            if(!'{{$web_domain?1:0}}') document.getElementById('web_domain_availability').innerHTML = "";
            if(show_info==true){
                showStatus(res.status);
                /*if(document.getElementById('web_subdomain_prefix_error')&&json.errors&&json.errors.subdomain_prefix){
                    document.getElementById('web_subdomain_prefix_error').innerText = json.errors.subdomain_prefix;
                    document.getElementById('web_subdomain_prefix').classList.add('is-invalid');
                    setTimeout(() => {
                        document.getElementById('web_subdomain_prefix').classList.remove('is-invalid');
                        document.getElementById('web_subdomain_prefix_error').innerText = "";
                    }, 3000);
                }*/
                if(document.getElementById('web_subdomain_error')&&json.errors&&json.errors.web_subdomain){
                    document.getElementById('web_subdomain_error').innerText = json.errors.web_subdomain;
                    setTimeout(() => {
                        document.getElementById('web_subdomain_error').innerText = "";
                    }, 3000);
                }
                else if(document.getElementById('web_domain_error')&&json.errors&&json.errors.web_domain){
                    document.getElementById('web_domain_error').innerText = json.errors.web_domain;
                    setTimeout(() => {
                        document.getElementById('web_domain_error').innerText = "";
                    }, 3000);
                }
            }
            else {
                if(body.type=='subdomain'){
                    document.getElementById('web_subdomain_availability').innerHTML = ((json.available==true)?('<i class="fas fa-check-circle mr-2"></i>' + `{{__('Domain is available')}}`):('<i class="fas fa-exclamation-triangle mr-2"></i>' + `{{__('Domain is already taken')}}`));
                    document.getElementById('web_subdomain_availability').style.color = ((json.available==true)?'green':'red');
                    //document.getElementById('subdomain_prefix').classList.add('is-invalid');
                    /*setTimeout(() => {
                        document.getElementById('subdomain_prefix').classList.remove('is-invalid');
                        document.getElementById('subdomain_prefix_error').innerText = "";
                    }, 2000);*/
                }
                else if(body.type=='domain'){
                    document.getElementById('web_domain_availability').innerHTML = ((json.available==true)?('<i class="fas fa-check-circle mr-2"></i>' + `{{__('Domain is available')}}`):('<i class="fas fa-exclamation-triangle mr-2"></i>' + `{{__('Domain is already taken')}}`));
                    document.getElementById('web_domain_availability').style.color = ((json.available==true)?'green':'red');
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
        function web_onClickCopy(element) {
            if(navigator.clipboard) {
                navigator.clipboard.writeText(document.getElementById('web_' + element + '_prefix').innerText + document.getElementById('web_' + element + '_suffix').innerText).then(() => {
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
        if(document.getElementById('web_subdomain_prefix'))document.getElementById('web_subdomain_prefix').value = "";
        if(document.getElementById('web_domain'))document.getElementById('web_domain').value = "";
    </script>
</div>

