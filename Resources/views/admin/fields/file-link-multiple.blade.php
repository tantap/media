<?php
$media__unix_name = str_random(6);
$functionMediaIncludeName = "includeMediaMultiFile_".$media__unix_name;
?>
<?php
$group_input_name = '_except';
$is_single = 1;

if(isset($options['data'])){
    $entityClass = array_get($options['data'],'entityClass');
    $entityId = array_get($options['data'],'entityId');
    $zone = array_get($options['data'],'zone');
    $group_input_name = array_get($options['data'],'group_input_name',$group_input_name);
    $is_single = array_get($options['data'],'is_single',$is_single);
    if(isset($options['data'][$zone])){
        ${$zone} = $options['data'][$zone];
    }
}
$prefix_input_name = isset($prefix_input_name)?$prefix_input_name:'_except';

?>
<div id="{{$media__unix_name}}" class="form-group">
<script src="{{ Module::asset('dashboard:vendor/jquery-ui/jquery-ui.min.js') }}"></script>
<script>
    $fileCount = $('.jsFileCount');
</script>
<style>
    .btn-upload {
        margin-bottom: 20px;
    }
    .jsThumbnailImageWrapper figure {
        position: relative;
        display: inline-block;
        margin-right: 20px;
        margin-bottom: 20px;
        background-color: #fff;
        border: 1px solid #eee;
        padding: 3px;
        border-radius: 3px;
        cursor: grab;
    }
    .jsThumbnailImageWrapper i.removeIcon {
        position: absolute;
        top:-10px;
        right:-10px;
        color: #f56954;
        font-size: 2em;
        background: white;
        border-radius: 20px;
        height: 25px;
    }

    figure.ui-state-highlight {
        border: none;
        width:100px;
        height: 0;
    }
</style>
<script>
    if (typeof window.openMediaWindow === 'undefined') {
        window.mediaZone = '';
        window.openMediaWindow = function (event, zone) {
            window.mediaZone = zone;
            window.zoneWrapper = $(event.currentTarget).siblings('.jsThumbnailImageWrapper');
            window.open('{!! route('media.grid.select') !!}', '_blank', 'menubar=no,status=no,toolbar=no,scrollbars=yes,height=500,width=1000');
        };
    }
    if (typeof window.includeMedia === 'undefined') {
        window.includeMedia = function (mediaId) {
            $.ajax({
                type: 'POST',
                url: '{{ route('api.media.link') }}',
                data: {
                    'mediaId': mediaId,
                    '_token': '{{ csrf_token() }}',
                    'entityClass': '{{ $entityClass }}',
                    'entityId': '{{ $entityId }}',
                    'zone': window.mediaZone
                },
                success: function (data) {
                    if(data.result.imageableId > 0){

                        var html = '<figure><img src="' + data.result.path + '" alt=""/>' +
                                '<a class="jsRemoveLink" href="#" data-id="' + data.result.imageableId + '">' +
                                '<i class="fa fa-times-circle removeIcon"></i>' +
                                '</a></figure>';
                    }else{

                        var html = '<figure><img src="' + data.result.path + '" alt=""/>' +
                                '<a class="jsRemoveLink" href="#" data-id="0">' +
                                '<input type="hidden" name="{{$zone}}[mediaId][]" value="' + data.result.imageId + '" />'+
                                '<input  type="hidden" name="{{$zone}}[zone]" value="{{ str_replace("\\\\","\\",$zone) }}" />'+
                                '<input type="hidden"  name="{{$zone}}[entityClass]" value="{{ $entityClass }}" />'+
                                '<i class="fa fa-times-circle removeIcon"></i>' +
                                '</a></figure>';
                    }

//                    var html = '<figure><img src="' + data.result.path + '" alt=""/>' +
//                            '<a class="jsRemoveLink" href="#" data-id="' + data.result.imageableId + '">' +
//                            '<i class="fa fa-times-circle"></i>' +
//                            '</a></figure>';

                    window.zoneWrapper.append(html).fadeIn();
                    if ($fileCount.length > 0) {
                        var count = parseInt($fileCount.text());
                        $fileCount.text(count + 1);
                    }
                }
            });
        };


    }
</script>
<div class="form-group">
    {!! Form::label($zone, ucwords(str_replace('_', ' ', $zone)) . ':') !!}
    <div class="clearfix"></div>
    <?php $url = route('media.grid.select') ?>
    <a class="btn btn-primary btn-upload" onclick="openMediaWindow(event, '{{ $zone }}')"><i class="fa fa-upload"></i>
        {{ trans('media::media.Browse') }}
    </a>
    <div class="clearfix"></div>
    <div class="jsThumbnailImageWrapper">
        <?php $zoneVar = "{$zone}"; ?>
        <?php if (isset($$zoneVar)): ?>
        <?php foreach ($$zoneVar as $file):

       if(isset($file->path)):
        ?>
        <figure>
            <img src="{{ media_url_file(Imagy::getThumbnail($file->path, 'mediumThumb')) }}" alt=""/>
            <a class="jsRemoveLink" href="#" data-id="{{ $file->pivot->id }}">
                <i class="fa fa-times-circle removeIcon"></i>
            </a>
        </figure>
            <?php endif; ?>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
    <script>
        $( document ).ready(function() {
            $('body #{!! $media__unix_name !!} ').on('click','.jsRemoveLink', function(e) {
                e.preventDefault();
                var imageableId = $(this).data('id'),
                        pictureWrapper = $(this).parent(),
                        $fileCount = $('#{!! $media__unix_name !!} .jsFileCount');

                if(imageableId >0){
                    $.ajax({
                        type: 'POST',
                        url: '{{ route('api.media.unlink') }}',
                        data: {
                            'imageableId': imageableId,
                            '_token': '{{ csrf_token() }}'
                        },
                        success: function(data) {
                            if (data.error === false) {
                                pictureWrapper.fadeOut().remove();
                                if ($fileCount.length > 0) {
                                    var count = parseInt($fileCount.text());
                                    $fileCount.text(count - 1);
                                }
                            } else {
                                pictureWrapper.append(data.message);
                            }
                        }
                    });
                }else{
                    pictureWrapper.remove();
                }

            });
        });
    </script>

</div>