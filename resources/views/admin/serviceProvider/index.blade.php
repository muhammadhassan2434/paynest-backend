@extends('admin.layout.layout')

@section('content')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="row  mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="welcome-text">
                        <h4>Service Providers </h4>
                    </div>
                </div>
                <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}">Home</a></li>
                        <li class="breadcrumb-item active"><a href="{{route('provider.index')}}">Services Providers</a></li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0">
                        <div class="card-header">
                            <h4 class="card-title">Service Providers List</h4>
                            <a href="{{route('provider.create')}}" class="btn btn-primary">+ Add new</a>
                        </div>
                        <table class="table table-hover border-0">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Service</th>
                                    <th scope="col">Service Provider </th>
                                    <th scope="col">Logo</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;
                                @endphp
                                @foreach ($serviceProviders as $serviceProvider)
                                    <tr>
                                        <td><strong>{{ $i }}</strong></td>
                                        <td>{{ $serviceProvider->service->name }}</td>
                                        <td>{{ $serviceProvider->name }}</td>
                                        <td>
                                            <img src="{{ asset($serviceProvider->logo) }}" width="80" height="80"
                                                class="rounded-4 border border-secondary shadow-sm" alt="serviceProvider Logo">
                                        </td>
                                        
                                        <td>{{ $serviceProvider->status }}</td>

                                        <td>
                                            <div class="d-flex">
                                                <a href="{{route('provider.edit',$serviceProvider->id)}}" class="btn mx-2 btn-sm btn-primary"><i
                                                    class="la la-pencil"></i></a>
                                                    <form action="{{route('provider.destroy',$serviceProvider->id)}}" method="POST">
                                                        @csrf
                                                        @method('DELETE')

                                                        <button class="btn btn-sm btn-danger"><i
                                                                class="la la-trash-o"></i></button>
                                                    </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @php
                                        $i++;
                                    @endphp
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
