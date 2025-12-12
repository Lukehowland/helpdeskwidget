{{-- 
    Helpdesk Widget Component
    
    Este componente renderiza un iframe con el widget de Helpdesk.
    Maneja automáticamente la autenticación del usuario.
--}}

@if($error)
    {{-- Error State --}}
    <div class="helpdesk-widget-error alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ $error }}
    </div>
@elseif($isReady)
    {{-- Widget Iframe --}}
    <div class="helpdesk-widget-container" style="width: {{ $width }};">
        <iframe
            id="helpdesk-widget-iframe"
            src="{{ $iframeSrc }}"
            style="
                width: {{ $width }};
                height: {{ $height }};
                border: {{ $border ? '1px solid #dee2e6' : 'none' }};
                border-radius: 4px;
            "
            allowfullscreen
            loading="lazy"
        ></iframe>
    </div>

    {{-- Optional: Resize listener for responsive height --}}
    <script>
        (function() {
            // Listen for messages from the widget iframe
            window.addEventListener('message', function(event) {
                // Verify origin if needed
                // if (event.origin !== 'https://your-helpdesk-url.com') return;
                
                // El widget envía 'widget-resize' como tipo de mensaje
                if (event.data && event.data.type === 'widget-resize') {
                    const iframe = document.getElementById('helpdesk-widget-iframe');
                    if (iframe && event.data.height) {
                        iframe.style.height = event.data.height + 'px';
                        console.log('[HelpdeskWidget] Resized to:', event.data.height);
                    }
                }
            });
        })();
    </script>
@else
    {{-- Loading State --}}
    <div class="helpdesk-widget-loading text-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="sr-only">Cargando...</span>
        </div>
        <p class="mt-3 text-muted">Cargando Centro de Soporte...</p>
    </div>
@endif
