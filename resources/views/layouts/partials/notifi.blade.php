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
