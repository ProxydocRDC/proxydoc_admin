<div class="flex -space-x-2">
  @foreach ($getState() as $src)
    <img src="{{ $src }}" class="h-10 w-10 rounded-md object-cover ring-2 ring-white" alt="">
  @endforeach
</div>
