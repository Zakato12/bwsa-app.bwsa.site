@if(session('success') || session('warning') || session('error'))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
        <div id="notificationToast" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="3000">
            <div class="toast-body d-flex align-items-start gap-3">
                @if(session('success'))
                    <i class="fas fa-check-circle text-success" style="font-size: 1.5rem;"></i>
                    <div>
                        <div class="fw-bold">Success</div>
                        <div class="text-muted">{{ session('success') }}</div>
                    </div>
                @elseif(session('warning'))
                    <i class="fas fa-exclamation-triangle text-warning" style="font-size: 1.5rem;"></i>
                    <div>
                        <div class="fw-bold">Attention</div>
                        <div class="text-muted">{{ session('warning') }}</div>
                    </div>
                @elseif(session('error'))
                    <i class="fas fa-times-circle text-danger" style="font-size: 1.5rem;"></i>
                    <div>
                        <div class="fw-bold">Error</div>
                        <div class="text-muted">{{ session('error') }}</div>
                    </div>
                @endif
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var toastEl = document.getElementById('notificationToast');
            if (toastEl) {
                var toast = new bootstrap.Toast(toastEl);
                toast.show();
                setTimeout(function () {
                    toast.hide();
                }, 3000);
            }
        });
    </script>
@endif


<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form method="POST" action="{{ url('/change-password') }}">
                @csrf

                <div class="modal-body">

                    <!-- Current Password -->
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" id="currpassword" name="currpassword" class="form-control" required>
                        @error('currpassword')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" id="newpassword" name="newpassword" class="form-control" required>
                        @error('newpassword')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" id="confirmpassword" name="confirmpassword" class="form-control" required>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Update Password
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
