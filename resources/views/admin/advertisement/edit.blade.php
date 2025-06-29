@extends('admin.layout.layout')
@section('content')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="row page-titles mx-0">
                <div class="col-sm-6 p-md-0">
                    <div class="welcome-text">
                        <h4>Edit Advertisement</h4>
                    </div>
                </div>
                <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Home</a></li>
                        <li class="breadcrumb-item active"><a href="{{ route('advertisements.index') }}">Advertisement</a>
                        </li>
                    </ol>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Edit Advertisement</h4>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('advertisements.update', $advertisement->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="form-group">
                                    <label>Advertiser Name</label>
                                    <input type="text" name="advertiser_name" class="form-control"
                                        value="{{ old('advertiser_name', $advertisement->advertiser_name) }}">
                                    @error('advertiser_name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Logo</label>
                                    <input type="file" name="logo" class="form-control">
                                    @if ($advertisement->logo)
                                        <img src="{{ asset($advertisement->logo) }}" width="100px" class="mt-2">
                                    @endif
                                    @error('logo')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" {{ $advertisement->status == 'active' ? 'selected' : '' }}>
                                            Active</option>
                                        <option value="block" {{ $advertisement->status == 'block' ? 'selected' : '' }}>
                                            Block</option>
                                    </select>
                                    @error('status')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>

                                <button type="submit" class="btn btn-primary">Update</button>
                                <a href="{{ route('advertisements.index') }}" class="btn btn-light">Cancel</a>
                            </form>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
