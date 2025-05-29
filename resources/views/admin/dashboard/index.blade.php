@extends('admin.layout.layout')

@section('content')
    <div class="content-body">
        <!-- row -->
        <div class="container-fluid">

            <div class="row">
                <h3>Users</h3>
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="card bg-primary text-white shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1 text-uppercase">Total Users</h5>
                                    <h3 class="mb-0 fw-bold">{{ $totalUsers }}</h3>
                                </div>
                                <div class="icon fs-2"><i class="fas fa-users"></i></div>
                            </div>
                            <div id="sparkline-total-users" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Pending Users -->
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="card bg-warning text-white shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1 text-uppercase">Pending Users</h5>
                                    <h3 class="mb-0 fw-bold">{{ $pendingUsers }}</h3>
                                </div>
                                <div class="icon fs-2"><i class="fas fa-user-clock"></i></div>
                            </div>
                            <div id="sparkline-pending-users" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="card bg-success text-white shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1 text-uppercase">Active Users</h5>
                                    <h3 class="mb-0 fw-bold">{{ $activeUsers }}</h3>
                                </div>
                                <div class="icon fs-2"><i class="fas fa-user-check"></i></div>
                            </div>
                            <div id="sparkline-active-users" class="mt-3"></div>
                        </div>
                    </div>
                </div>

                <!-- Blocked Users -->
                <div class="col-xl-3 col-xxl-3 col-sm-6">
                    <div class="card bg-danger text-white shadow-lg border-0 rounded-4 overflow-hidden">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h5 class="mb-1 text-uppercase">Blocked Users</h5>
                                    <h3 class="mb-0 fw-bold">{{ $blockedUsers }}</h3>
                                </div>
                                <div class="icon fs-2"><i class="fas fa-user-slash"></i></div>
                            </div>
                            <div id="sparkline-blocked-users" class="mt-3"></div>
                        </div>
                    </div>
                </div>
                <h3>Transactions</h3>
                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card bg-primary text-white shadow rounded-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Total Transactions</h5>
                                    <h3>{{ $totalTransactions }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-exchange-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card bg-warning text-white shadow rounded-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Pending</h5>
                                    <h3>{{ $pendingTransactions }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-clock fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card bg-success text-white shadow rounded-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Completed</h5>
                                    <h3>{{ $completedTransactions }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-sm-6 mb-4">
                    <div class="card bg-danger text-white shadow rounded-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h5 class="card-title">Failed</h5>
                                    <h3>{{ $failedTransactions }}</h3>
                                </div>
                                <div class="align-self-center">
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Latest Users</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 table-striped">
                                    <thead>
                                        <tr>
                                            <th class="px-5 py-3">#</th>
                                            <th class="px-5 py-3">First Name</th>
                                            <th class="py-3">Last Name</th>
                                            <th class="py-3">Email</th>
                                            <th class="py-3">Paynest Id</th>
                                            <th class="py-3">Phone</th>
                                            <th class="py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="customers">
                                        @php
                                            $i = 1;
                                        @endphp
                                        @foreach ($latestUsers as $latestUser)
                                            <tr class="btn-reveal-trigger">
                                                <td class="py-2 ps-5">{{ $i }}</td>
                                                <td class="py-2 ps-5">{{ $latestUser->first_name }}</td>
                                                <td class="py-2">{{ $latestUser->last_name }}</td>
                                                <td class="py-2">{{ $latestUser->email }}</td>
                                                <td class="py-2">{{ $latestUser->account->paynest_id ?? '' }}</td>
                                                <td class="py-2">{{ $latestUser->account->phone ?? '' }}</td>
                                                <td>
                                                    @if ($latestUser->status === 'active')
                                                        <span class="badge badge-rounded badge-primary">Active</span>
                                                    @elseif($latestUser->status === 'pending')
                                                        <span class="badge badge-rounded badge-warning">Pending</span>
                                                    @elseif($latestUser->status === 'blocked')
                                                        <span class="badge badge-rounded badge-danger">Blocked</span>
                                                    @endif
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
        </div>
    </div>
@endsection
