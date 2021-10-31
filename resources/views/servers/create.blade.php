@extends('layouts.main')

@section('content')
    <!-- CONTENT HEADER -->
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Servers</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('home') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('servers.index') }}">Servers</a>
                        <li class="breadcrumb-item"><a class="text-muted"
                                href="{{ route('servers.create') }}">Create</a>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
    <!-- END CONTENT HEADER -->

    <!-- MAIN CONTENT -->
    <section class="content">
        <div class="container-fluid">

            <!-- CUSTOM CONTENT -->
            <div class="row justify-content-center">
                <div class="card col-lg-8 col-md-12 mb-5">
                    <div class="card-header">
                        <h5 class="card-title"><i class="fa fa-server mr-2"></i>Create Server</h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="{{ route('servers.store') }}">
                            @csrf
                            <div class="form-group">
                                <label for="name">* Name</label>
                                <input id="name" name="name" type="text" required="required"
                                    class="form-control @error('name') is-invalid @enderror">

                                @error('name')
                                    <div class="invalid-feedback">
                                        Please fill out this field.
                                    </div>
                                @enderror

                                Název serveru slouží pouze pro vaši orientaci v případě, že budete mít serverů víc.

                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <input id="description" name="description" type="text"
                                    class="form-control @error('description') is-invalid @enderror">

                                @error('description')
                                    <div class="invalid-feedback">
                                        Please fill out this field.
                                    </div>
                                @enderror

                            </div>
                            <div class="form-group">
                                <label for="location_id">* Server location</label>
                                <div>

                                    <select id="node_id" name="node_id" required="required"
                                        class="custom-select @error('node_id') is-invalid @enderror">
                                        <option selected disabled hidden value="">Please Select ...</option>    
                                        @foreach ($locations as $location)
                                            <optgroup label="{{ $location->name }}">
                                                @foreach ($location->nodes as $node)
                                                    @if (!$node->disabled)
                                                        <option value="{{ $node->id }}">{{ $node->name }}</option>
                                                    @endif
                                                @endforeach
                                            </optgroup>
                                        @endforeach

                                    </select>
                                </div>

                                @error('node_id')
                                    <div class="invalid-feedback">
                                        Please fill out this field.
                                    </div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="egg_id">* Server configuration</label>
                                <div>
                                    <select id="egg_id" name="egg_id" required="required"
                                        class="custom-select @error('egg_id') is-invalid @enderror">
                                       <option selected disabled hidden value="">Please Select ...</option>    
                                        @foreach ($nests as $nest)
                                            <optgroup label="{{ $nest->name }}">
                                                @foreach ($nest->eggs as $egg)
                                                    <option value="{{ $egg->id }}">{{ $egg->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>

                                @error('egg_id')
                                    <div class="invalid-feedback">
                                        Please fill out this field.
                                    </div>
                                @enderror
                                
                                Seznam her a minimální/doporučené konfigurace k nim najdete <a href="http://home.vagonbrei.eu/seznam-her">zde</a>.
                                V případě Minecraftu se nainstaluje nejnovější verze, jak ji změnit včetně Javy naleznete <a href="http://home.vagonbrei.eu/uprava-serveru">zde</a>.
                                
                            </div>
                            <div class="form-group">
                                <label for="product_id">* Resource Configuration</label>
                                <div>
                                    <select id="product_id" name="product_id" required="required"
                                        class="custom-select @error('product_id') is-invalid @enderror">
                                        <option selected disabled hidden value="">Please Select...</option>    
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @if ($product->minimum_credits == -1 && Auth::user()->credits >= $minimum_credits)
                                            @elseif ($product->minimum_credits != -1 && Auth::user()->credits >=
                                                $product->minimum_credits)
                                            @else
                                                disabled
                                        @endif
                                        >{{ $product->name }}
                                        ({{ $product->description }})
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                @error('product_id')
                                    <div class="invalid-feedback">
                                        Please fill out this field.
                                    </div>
                                @enderror
                                
                                Bližší informace ohledně balíčku naleznete <a href="http://home.vagonbrei.eu/cenik">zde</a>.
                                Pokud zvolíte příliš malý balíček, některé druhy serveru se nemusí ani nainstalovat. Prozatím bohužel nelze balíček změnit, je nutné server smazat a znova vytvořit. Vybírejte proto prosím s rozumem.
                            </div>
                            <div class="form-group text-right">
                                <input type="submit" class="btn btn-primary mt-3" value="Submit"
                                    onclick="this.disabled=true;this.value='Creating, please wait...';this.form.submit();">
                            </div>
                        </form>

                    </div>
                </div>
            </div>
            <!-- END CUSTOM CONTENT -->


        </div>
    </section>
    <!-- END CONTENT -->

@endsection
