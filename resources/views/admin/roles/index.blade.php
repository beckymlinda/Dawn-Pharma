@extends('layouts.app')

@section('content')
<div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
        <header class="app-topbar" id="app-topbar"></header>
        <main class="app-content">
            <h1 class="h4 fw-bold mb-3">Role Management</h1>
            
            <div class="card border mb-3">
                <div class="card-body py-2">
                    <button class="btn btn-link text-primary fw-semibold text-decoration-none p-0" data-bs-toggle="collapse" data-bs-target="#filters">
                        🔽 Filters
                    </button>
                </div>
                <div class="collapse" id="filters">
                    <div class="card-body border-top">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <input type="text" class="form-control" placeholder="Search roles..." id="searchInput">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary" onclick="applyFilters()">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border">
                <div class="card-header d-flex align-items-center justify-content-between bg-white">
                    <h2 class="h6 fw-semibold mb-0">All Roles</h2>
                    <button class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                        <i class="bi bi-plus"></i> Add Role
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Display Name</th>
                                    <th>Description</th>
                                    <th>Users Count</th>
                                    <th>Permissions Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($roles as $role)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                {{ strtoupper(substr($role->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="fw-semibold">{{ $role->name }}</div>
                                                @if($role->is_default)
                                                <small class="badge bg-secondary">Default</small>
                                                @endif
                                                @if($role->is_superadmin)
                                                <small class="badge bg-warning">Super Admin</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $role->display_name }}</td>
                                    <td>{{ $role->description ?? '-' }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $role->users_count ?? $role->users->count() }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success">{{ $role->permissions_count ?? $role->permissions->count() }}</span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="{{ route('admin.roles.edit', $role) }}">Edit</a></li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteRole({{ $role->id }})">Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">No roles found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $roles->firstItem() }} to {{ $roles->lastItem() }} of {{ $roles->total() }} roles
                        </div>
                        {{ $roles->links() }}
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Role Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g., cashier">
                        <small class="text-muted">Use lowercase, no spaces</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Display Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="display_name" required placeholder="e.g., Cashier">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div class="border rounded p-2" style="max-height: 300px; overflow-y: auto;">
                            <div class="mb-2">
                                <input type="checkbox" id="selectAll" onchange="toggleAllPermissions()">
                                <label for="selectAll" class="fw-semibold">Select all permissions</label>
                            </div>
                            @foreach(App\Models\Permission::getGroupedPermissions() as $group => $permissions)
                            <div class="mb-3">
                                <h6 class="text-primary fw-bold">{{ $group }}</h6>
                                <div class="ms-2">
                                    @foreach($permissions as $permission)
                                    <div class="form-check">
                                        <input class="form-check-input permission-checkbox" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="permission_{{ $permission->id }}">
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            {{ $permission->display_name }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function applyFilters() {
    const search = document.getElementById('searchInput').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    
    window.location.href = '{{ route('admin.roles.index') }}?' + params.toString();
}

function toggleAllPermissions() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.permission-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
}

function deleteRole(roleId) {
    if (confirm('Are you sure you want to delete this role? This action cannot be undone.')) {
        fetch(`/admin/roles/${roleId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Error deleting role');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting role');
        });
    }
}
</script>
@endsection
