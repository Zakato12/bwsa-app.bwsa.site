@extends('layouts.main')

@section('title', 'User List')

@section('content')
    <div>
        <h1>User List</h1>

        <form method="GET" action="{{ route('users.list') }}" class="row g-2 mb-3">
            <div class="col-sm-8 col-md-5">
                <input type="text" name="q" class="form-control" placeholder="Search users" value="{{ $search ?? '' }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('users.list') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Role</th>
                    <th>Barangay</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                <tr>
                    <td>{{ $user->id }}</td>
                    <td>{{ $user->username }}</td>
                    <td>{{ $user->full_name }}</td>
                    <td>{{ ucfirst($user->role) }}</td>
                    <td>{{ $user->barangay_name ?? '-' }}</td>
                    <td>{{ ucfirst($user->status) }}</td>
                    <td>{{ $user->created_at }}</td>
                    <td>
                        <button
                            class="btn btn-sm btn-warning"
                            data-bs-toggle="modal"
                            data-bs-target="#editUserModal"
                            data-id="{{ $user->id }}"
                            data-username="{{ $user->username }}"
                            data-full_name="{{ $user->full_name }}"
                            data-role="{{ $user->role }}"
                            data-status="{{ $user->status }}"
                            data-barangay_id="{{ $user->barangay_id }}"
                        >Edit</button>
                        <button
                            class="btn btn-sm btn-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteUserModal"
                            data-id="{{ $user->id }}"
                            data-username="{{ $user->username }}"
                        >Delete</button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-2">
            {{ $users->links() }}
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editUserForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" id="editUsername" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role_id" id="editRole" class="form-control" required>
                                <option value="1">Admin</option>
                                <option value="2">Official</option>
                                <option value="3">Treasurer</option>
                                <option value="4">Resident</option>
                            </select>
                        </div>
                        <div class="mb-3" id="editBarangayGroup">
                            <label class="form-label">Barangay</label>
                            <select name="barangay_id" id="editBarangay" class="form-control">
                                <option value="">Select Barangay</option>
                                @foreach($barangays as $barangay)
                                    <option value="{{ $barangay->id }}">{{ $barangay->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="editStatus" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteUserForm" method="POST">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">Delete User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const editModal = document.getElementById('editUserModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            const fullName = button.getAttribute('data-full_name');
            const role = button.getAttribute('data-role');
            const status = button.getAttribute('data-status');
            const barangayId = button.getAttribute('data-barangay_id');

            document.getElementById('editUserForm').action = `/users/${id}`;
            document.getElementById('editUsername').value = username;
            document.getElementById('editFullName').value = fullName;
            document.getElementById('editStatus').value = status;

            const roleMap = { admin: '1', official: '2', treasurer: '3', resident: '4' };
            document.getElementById('editRole').value = roleMap[role] || '4';
            document.getElementById('editBarangay').value = barangayId || '';

            const barangayGroup = document.getElementById('editBarangayGroup');
            if (['2', '3', '4'].includes(document.getElementById('editRole').value)) {
                barangayGroup.style.display = 'block';
            } else {
                barangayGroup.style.display = 'none';
            }
        });

        document.getElementById('editRole').addEventListener('change', function () {
            const barangayGroup = document.getElementById('editBarangayGroup');
            if (['2', '3', '4'].includes(this.value)) {
                barangayGroup.style.display = 'block';
            } else {
                barangayGroup.style.display = 'none';
                document.getElementById('editBarangay').value = '';
            }
        });

        const deleteModal = document.getElementById('deleteUserModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const username = button.getAttribute('data-username');
            document.getElementById('deleteUserForm').action = `/users/${id}`;
            document.getElementById('deleteUserName').textContent = username;
        });
    </script>
@endsection
