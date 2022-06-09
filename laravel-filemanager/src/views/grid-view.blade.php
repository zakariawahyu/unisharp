@if((sizeof($files) > 0) || (sizeof($directories) > 0))

    <div class="row">

        @foreach($items as $item)
            @php
                if($search && strpos(strtolower($item->name),strtolower($search)) !== false):
                    $view = 'display:inherit;';
                elseif(!$search):
                    $view = 'display:inherit;';
                else:
                    $view = 'display:none';
                endif;
            @endphp
            <div class="col-xs-6 col-sm-4 col-md-3 col-lg-2 img-row" style="{{$view}}">
                <?php $item_name = $item->name; ?>
                <?php $thumb_src = $item->thumb; ?>
                <?php $item_path = $item->is_file ? $item->url : $item->path; ?>

                <div class="square clickable {{ $item->is_file ? '' : 'folder-item' }}" data-id="{{ $item_path }}"
                     @if($item->is_file && $thumb_src) onclick="useFile('{{ $item_path }}', '{{ $item->updated }}')"
                     @elseif($item->is_file) onclick="download('{{ $item_name }}')" @endif >
                    @if($thumb_src)
                        <img class="lazy" src="{{ $thumb_src }}" data-original="{{ $thumb_src }}">
                    @else
                        <i class="fa {{ $item->icon }} fa-5x"></i>
                    @endif
                </div>

                <div class="caption text-center">
                    <div class="btn-group">
                        <button type="button" data-id="{{ $item_path }}"
                                class="item_name btn btn-default btn-xs {{ $item->is_file ? '' : 'folder-item'}}"
                                @if($item->is_file && $thumb_src) onclick="useFile('{{ $item_path }}', '{{ $item->updated }}')"
                                @elseif($item->is_file) onclick="download('{{ $item_name }}')" @endif >
                            {{ $item_name }}
                        </button>
                        <button type="button" class="btn btn-default dropdown-toggle btn-xs" data-toggle="dropdown"
                                aria-expanded="false">
                            <span class="caret"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <li><a href="#"><i
                                class="fa fa-calendar fa-fw"></i> {{ $item->created_at }}
                                </a></li>
                            <li class="divider"><a href="javascript:rename('{{ $item_name }}')"><i
                                            class="fa fa-edit fa-fw"></i> {{ Lang::get('laravel-filemanager::lfm.menu-rename') }}
                                </a></li>
                            @if($item->is_file)
                                <li><a href="javascript:download('{{ $item_name }}')"><i
                                                class="fa fa-download fa-fw"></i> {{ Lang::get('laravel-filemanager::lfm.menu-download') }}
                                    </a></li>
                                <li class="divider"></li>
                                @if($thumb_src)
                                    <li><a href="javascript:fileView('{{ $item_path }}', '{{ $item->updated }}')"><i
                                                    class="fa fa-image fa-fw"></i> {{ Lang::get('laravel-filemanager::lfm.menu-view') }}
                                        </a></li>
                                    {{-- <li><a href="javascript:resizeImage('{{ $item_name }}')"><i
                                                    class="fa fa-arrows fa-fw"></i> {{ Lang::get('laravel-filemanager::lfm.menu-resize') }}
                                        </a></li>
                                    <li><a href="javascript:cropImage('{{ $item_name }}')"><i
                                                    class="fa fa-crop fa-fw"></i> {{ Lang::get('laravel-filemanager::lfm.menu-crop') }}
                                        </a></li>
                                    <li class="divider"></li> --}}
                                @endif
                            @endif
                            <li><a href="javascript:trash('{{ $item_name }}')"><i
                                            class="fa fa-trash fa-fw"></i> {{ Lang::get('laravel-filemanager::lfm.menu-delete') }}
                                </a></li>
                        </ul>
                    </div>
                </div>
       
            </div>
        @endforeach
    </div>
    @if ($paginations['totalOfPages'] != 0)
    @php
        $curentPage = $paginations['curentPage'];
        $totalPage = $paginations['totalOfPages'];
        $nextPage = $paginations['curentPage'] + 1;
        $prevPage = $paginations['curentPage'] - 1;
    @endphp
    <div class="col-lg-12 text-center">
        <nav aria-label="Pagination Laravel File Manager">
            <ul class="pagination">
                
                <li class="page-item">
                    <button class="btn btn-secondary page-link" @if ($curentPage == 0) disabled @endif onclick="loadItems({{ $prevPage }});">Previous</button>
                </li>

                <li class="page-item"><button class="btn btn-secondary page-link @if ($curentPage <= 5) disabled @endif" onclick="loadItems({{ $curentPage - 5 }});" name="page"><<</button></li>

            @for ($i = $curentPage + 1; $i <= $curentPage + 5; $i++)
                    @if ($i <= $totalPage)
                        <li class="page-item"><button class="btn page-link @if ($curentPage + 1 == $i) ? btn-primary active : btn-secondary @endif" onclick="loadItems({{ $i - 1 }});" name="page">{{ $i }}</button></li>
                    @endif
            @endfor

                <li class="page-item"><button class="btn btn-secondary page-link @if ($curentPage + 5 >= $totalPage) disabled @endif" onclick="loadItems({{ $curentPage + 5 }});" name="page">>></button></li>
 
                <li class="page-item">
                    <button class="btn btn-secondary page-link"@if ($curentPage == $totalPage - 1) disabled @endif  onclick="loadItems({{ $nextPage }});">Next</button>
                </li>
            </ul>
        </nav>
    </div>
    @endif
@else
    <p>File Not Found</p>
@endif

@if ($items[0]->icon != 'fa-folder-o')
<script>
    $(function() {
      $("img.lazy").lazyload({
        event: "lazyload",
        effect : "fadeIn",
        effectspeed: 1500
      }).trigger("lazyload");
    });
</script>
@endif
<script>
    var file = {!! json_encode($items) !!};
    console.log(file);
</script>


