@if(session()->has('message'))
    <div class="flashMessage">
        <p class="text">{{ session('message') }}</p>
    </div>
@endif