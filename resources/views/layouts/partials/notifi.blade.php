@if(session('success') || session('warning') || session('error'))
<div class="modal fade" id="notificationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-body text-center p-4">
                
                @if(session('success'))
                    <i class="fas fa-check-circle text-success mb-3" style="font-size: 4rem;"></i>
                    <h4 class="fw-bold">Success!</h4>
                    <p class="text-muted">{{ session('success') }}</p>
                @elseif(session('warning'))
                    <i class="fas fa-exclamation-triangle text-warning mb-3" style="font-size: 4rem;"></i>
                    <h4 class="fw-bold">Attention</h4>
                    <p class="text-muted">{{ session('warning') }}</p>
                @elseif(session('error'))
                    <i class="fas fa-times-circle text-danger mb-3" style="font-size: 4rem;"></i>
                    <h4 class="fw-bold">Error</h4>
                    <p class="text-muted">{{ session('error') }}</p>
                @endif

                <button type="button" class="btn btn-dark w-100 mt-3" data-bs-dismiss="modal">Continue</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var myModal = new bootstrap.Modal(document.getElementById('notificationModal'));
        myModal.show();
    });
</script>
<style>
    #notificationModal .modal-content {
    border-radius: 15px;
    background: #ffffff;
    }

    #notificationModal h4 {
        letter-spacing: -0.5px;
    }

    /* Optional: Slight animation for the icon */
    #notificationModal .fas {
        animation: zoomIn 0.3s ease-out;
    }

    @keyframes zoomIn {
        from { transform: scale(0.5); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>
@endif