@if($errors->any())<div role="alert" class="bg-red-50 text-red-900 p-4 rounded">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
@if(session('success'))<p role="status">{{ session('success') }}</p>@endif
