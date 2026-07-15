@extends('admin.layouts.app')

@section('title', 'Subscription Plans')

@section('content')
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
        <div>
            <h1 class="display-font h3 mb-1">Subscription Plans</h1>
            <p class="text-secondary mb-0">Create and modify provider subscription plans and pricing</p>
        </div>
        <button class="btn btn-success" style="background:#2bb673;border:0" data-bs-toggle="modal" data-bs-target="#createPlanModal">Add plan</button>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Cycle</th>
                        <th>Price</th>
                        <th>Discount</th>
                        <th>Promo</th>
                        <th>Active</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($plans as $plan)
                        <tr>
                            <td>
                                <strong>{{ $plan->name }}</strong>
                                <div class="small text-secondary">{{ $plan->description ?: '—' }}</div>
                            </td>
                            <td class="text-capitalize">{{ $plan->billing_cycle }}</td>
                            <td>${{ $plan->price }}</td>
                            <td>{{ $plan->discount_percent }}%</td>
                            <td>{{ $plan->is_promotional ? 'Yes' : 'No' }}</td>
                            <td>{{ $plan->is_active ? 'Yes' : 'No' }}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editPlan{{ $plan->id }}">Edit</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-secondary py-4">No subscription plans yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($plans as $plan)
        <div class="modal fade" id="editPlan{{ $plan->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('admin.plans.update', $plan->id) }}" method="POST" class="modal-content js-action-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input class="form-control mb-2" name="name" value="{{ $plan->name }}" required>
                        <select class="form-select mb-2" name="billing_cycle">
                            @foreach(['monthly','quarterly','yearly'] as $cycle)
                                <option value="{{ $cycle }}" @selected($plan->billing_cycle === $cycle)>{{ ucfirst($cycle) }}</option>
                            @endforeach
                        </select>
                        <input class="form-control mb-2" type="number" step="0.01" name="price" value="{{ $plan->price }}" required>
                        <input class="form-control mb-2" type="number" step="0.01" name="discount_percent" value="{{ $plan->discount_percent }}">
                        <label class="d-block mb-2"><input type="checkbox" name="is_promotional" value="1" @checked($plan->is_promotional)> Promotional</label>
                        <label class="d-block mb-2"><input type="checkbox" name="is_active" value="1" @checked($plan->is_active)> Active</label>
                        <textarea class="form-control" name="description" rows="3">{{ $plan->description }}</textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                            <span class="btn-label">Save</span>
                            <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="createPlanModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('admin.plans.store') }}" method="POST" class="modal-content js-action-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input class="form-control mb-2" name="name" placeholder="Plan name" required>
                    <select class="form-select mb-2" name="billing_cycle">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                    <input class="form-control mb-2" type="number" step="0.01" name="price" placeholder="Price" required>
                    <input class="form-control mb-2" type="number" step="0.01" name="discount_percent" placeholder="Discount %" value="0">
                    <label class="d-block mb-2"><input type="checkbox" name="is_promotional" value="1"> Promotional</label>
                    <textarea class="form-control" name="description" rows="3" placeholder="Description"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-success btn-action" style="background:#2bb673;border:0">
                        <span class="btn-label">Create</span>
                        <span class="btn-loader d-none"><span class="spinner-border spinner-border-sm"></span></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
