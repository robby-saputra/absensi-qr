@if(session('success') || session('error') || $errors->any())
    <div class="app-alerts">
        @if(session('success'))
            <div class="app-alert success" data-auto-dismiss>
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="app-alert error" data-auto-dismiss>
                <i class="fa-solid fa-circle-exclamation"></i>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="app-alert error" data-auto-dismiss>
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif
    </div>
@endif
