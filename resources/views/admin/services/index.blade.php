@extends('admin.layout.layout')

@section('content')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="row  mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="welcome-text">
                        <h4>Services </h4>
                    </div>
                </div>
                <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{route('dashboard.index')}}">Home</a></li>
                        <li class="breadcrumb-item active"><a href="{{route('services.index')}}">Services</a></li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card border-0">
                        <div class="card-header">
                            <h4 class="card-title">Services List</h4>
                            <a href="{{route('services.create')}}" class="btn btn-primary">+ Add new</a>
                        </div>
                        <table class="table table-hover border-0">
                            <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Logo</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $i = 1;
                                @endphp
                                @foreach ($services as $service)
                                    <tr>
                                        <td><strong>{{ $i }}</strong></td>
                                        <td>{{ $service->name }}</td>
                                        <td>
                                            <img src="{{ asset($service->logo) }}" width="80" height="80"
                                                class="rounded-4 border border-secondary shadow-sm" alt="Service Logo">
                                        </td>
                                        
                                        <td>{{ $service->status }}</td>

                                        <td>
                                           <div class="d-flex">
                                            <a href="{{route('services.edit',$service->id)}}" class="btn btn-sm btn-primary"><i
                                                class="la la-pencil"></i></a>
                                        <form action="{{route('services.destroy',$service->id)}}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button href="" class="btn mx-2 btn-sm btn-danger"><i
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
