<?php
$media__unix_name = str_random(6);
$functionMediaIncludeName = "includeMediaSingleFile_".$media__unix_name;
?>
<?php
if(isset($options['data'])){
    $entityClass = array_get($options['data'],'entityClass');
    $entityId = array_get($options['data'],'entityId');
    $zone = array_get($options['data'],'zone');
    if(isset($options['data'][$zone])){
        ${$zone} = $options['data'][$zone];
    }
}

$prefix_input_name = isset($prefix_input_name)?$prefix_input_name:'_except';

?>
<div id="{{$media__unix_name}}" class="form-group">
<style>
    figure.jsThumbnailImageWrapper {
        position: relative;
        display: inline-block;
        background-color: #fff;
        border: 1px solid #eee;
        padding: 3px;
        border-radius: 3px;
        margin-top: 20px;
    }
    figure.jsThumbnailImageWrapper i.removeIcon {
        position: absolute;
        top:-10px;
        right:-10px;
        color: #f56954;
        font-size: 2em;
        background: white;
        border-radius: 20px;
        height: 25px;
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
                    if(data.result.imageableId > 0) {
                        var html = '<figure data-id="' + data.result.imageableId + '"><img src="' + data.result.path + '" alt=""/>' +
                                '<a class="jsRemoveLink" href="#" data-id="' + data.result.imageableId + '">' +
                                '<i class="fa fa-times-circle removeIcon"></i>' +
                                '</a></figure>';
                    }else{
                        var html = '<figure data-id="' + data.result.imageableId + '"><img src="' + data.result.path + '" alt=""/>' +
                                '<a class="jsRemoveLink" href="#" data-id="' + data.result.imageableId + '">' +
                                '<input type="hidden" name="{{$zone}}[mediaId]" value="' + data.result.imageId + '" />'+
                                '<input  type="hidden" name="{{$zone}}[zone]" value="{{ str_replace("\\\\","\\",$zone) }}" />'+
                                '<input type="hidden"  name="{{$zone}}[entityClass]" value="{{ $entityClass }}" />'+
                                '<i class="fa fa-times-circle removeIcon"></i>' +
                                '</a></figure>';
                    }
                    window.zoneWrapper.append(html).fadeIn('slow', function() {
                        toggleButton($(this));
                    });
                }
            });
        };
    }
</script>
<div class="form-group">
    {!! Form::label($zone, ucwords(str_replace('_', ' ', $zone)) . ':') !!}
    <div class="clearfix"></div>

    <a class="btn btn-primary btn-browse" onclick="openMediaWindow(event, '{{ $zone }}');" <?php echo (isset(${$zone}->path))?'style="display:none;"':'' ?>><i class="fa fa-upload"></i>
        {{ trans('media::media.Browse') }}
    </a>

    <div class="clearfix"></div>

    <figure class="jsThumbnailImageWrapper">
        <?php if (isset(${$zone}->path)): ?>
            <?php if (${$zone}->isImage()): ?>
            <img src="{{ media_url_file(Imagy::getThumbnail(${$zone}->path, 'mediumThumb')) }}" alt=""/>
            <?php else: ?>
                <i class="fa fa-file" style="font-size: 50px;"></i>
            <?php endif; ?>
            <a class="jsRemoveLink" href="#" data-id="{{ ${$zone}->pivot->id }}">
                <i class="fa fa-times-circle removeIcon"></i>
            </a>
        <?php endif; ?>
    </figure>
</div>
    </div>
<script>
    $( document ).ready(function() {
        $('body #{!! $media__unix_name !!}').on('click','.jsRemoveLink', function(e) {
            e.preventDefault();
            var imageableId = $(this).data('id');
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
                            $('#{!! $media__unix_name !!} .jsThumbnailImageWrapper').fadeOut().html('');
                            $(".btn-browse").show();
                        } else {
                            $('#{!! $media__unix_name !!} .jsThumbnailImageWrapper').append(data.message);
                        }
                    }
                });
            }else{
                $('#{!! $media__unix_name !!} .jsThumbnailImageWrapper').fadeOut().html('');
                $(".btn-browse").show();
            }

        });
    });
</script>
