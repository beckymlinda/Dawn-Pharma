@extends('layouts.app')

@section('content')
<div class="app-wrapper">
    <aside class="app-sidebar" id="app-sidebar"></aside>
    <div class="app-main">
        <header class="app-topbar" id="app-topbar"></header>
        <main class="app-content">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 fw-bold mb-0">Edit Role</h1>
                <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Roles
                </a>
            </div>

            <div class="card border">
                <div class="card-header bg-white">
                    <h2 class="h6 fw-semibold mb-0">Role Information</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" value="{{ $role->name }}" required>
                                    <small class="text-muted">Use lowercase, no spaces</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Display Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="display_name" value="{{ $role->display_name }}" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2">{{ $role->description ?? '' }}</textarea>
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">Permissions:</h5>
                            
                            @foreach($permissions as $group => $groupPermissions)
                            <div class="mb-4">
                                <div class="d-flex align-items-center mb-2">
                                    <input type="checkbox" id="select_{{ Str::slug($group) }}" onchange="toggleGroup('{{ Str::slug($group) }}')">
                                    <label for="select_{{ Str::slug($group) }}" class="fw-semibold ms-2">{{ $group }}</label>
                                </div>
                                <div class="ms-4 border rounded p-3 bg-light">
                                    @foreach($groupPermissions as $permission)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input group-{{ Str::slug($group) }}" 
                                               type="checkbox" 
                                               name="permissions[]" 
                                               value="{{ $permission->id }}" 
                                               id="permission_{{ $permission->id }}"
                                               {{ $role->permissions->contains($permission) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="permission_{{ $permission->id }}">
                                            {{ $permission->display_name }}
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-between">
                            <div>
                                <small class="text-muted">
                                    Created: {{ $role->created_at->format('M d, Y H:i') }} | 
                                    Updated: {{ $role->updated_at->format('M d, Y H:i') }}
                                </small>
                            </div>
                            <div>
                                <a href="{{ route('admin.roles.index') }}" class="btn btn-secondary me-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Update Role</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function toggleGroup(groupName) {
    const groupCheckbox = document.getElementById('select_' + groupName);
    const checkboxes = document.querySelectorAll('.group-' + groupName);
    checkboxes.forEach(checkbox => {
        checkbox.checked = groupCheckbox.checked;
    });
}

// Update group checkboxes when individual permissions change
document.addEventListener('DOMContentLoaded', function() {
    @foreach($permissions as $group => $groupPermissions)
    const group{{ Str::slug($group) }}Checkboxes = document.querySelectorAll('.group-{{ Str::slug($group) }}');
    const group{{ Str::slug($group) }}Checkbox = document.getElementById('select_{{ Str::slug($group) }}');
    
    group{{ Str::slug($group) }}Checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = Array.from(group{{ Str::slug($group) }}Checkboxes).every(cb => cb.checked);
            const someChecked = Array.from(group{{ Str::slug($group) }}Checkboxes).some(cb => cb.checked);
            
            group{{ Str::slug($group) }}Checkbox.checked = allChecked;
            group{{ Str::slug($group) }}Checkbox.indeterminate = someChecked && !allChecked;
        });
    });
    
    // Set initial state
    const allChecked = Array.from(group{{ Str::slug($group) }}Checkboxes).every(cb => cb.checked);
    const someChecked = Array.from(group{{ Str::slug($group) }}Checkboxes).some(cb => cb.checked);
    
    group{{ Str::slug($group) }}Checkbox.checked = allChecked;
    group{{ Str::slug($group) }}Checkbox.indeterminate = someChecked && !allChecked;
    @endforeach
});
</script>
@endsection
