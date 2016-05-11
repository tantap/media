<?php namespace Modules\Media\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Modules\Media\Events\FileWasLinked;
use Modules\Media\Events\FileWasUnlinked;
use Modules\Media\Events\FileWasUploaded;
use Modules\Media\Http\Requests\UploadMediaRequest;
use Modules\Media\Image\Imagy;
use Modules\Media\Repositories\FileRepository;
use Modules\Media\Services\FileService;

class MediaController extends Controller
{
    /**
     * @var FileService
     */
    private $fileService;
    /**
     * @var FileRepository
     */
    private $file;
    /**
     * @var Imagy
     */
    private $imagy;

    public function __construct(FileService $fileService, FileRepository $file, Imagy $imagy)
    {
        $this->fileService = $fileService;
        $this->file = $file;
        $this->imagy = $imagy;
    }

    public function all()
    {
        $files = $this->file->all();

        return [
            'count' => $files->count(),
            'data' => $files,
        ];
    }

    /**
     * Store a newly created resource in storage.
     * @param  UploadMediaRequest $request
     * @return Response
     */
    public function store(UploadMediaRequest $request)
    {
        $savedFile = $this->fileService->store($request->file('file'));

        if (is_string($savedFile)) {
            return Response::json(['error' => $savedFile], 409);
        }

        event(new FileWasUploaded($savedFile));

        return Response::json($savedFile->toArray());
    }

    /**
     * Link the given entity with a media file
     * @param Request $request
     */
    public function linkMedia(Request $request, FileRepository $fileRepository)
    {
        $mediaId = $request->get('mediaId');
        $entityClass = $request->get('entityClass');
        $entityId = $request->get('entityId');
        $file = $this->file->find($mediaId);
        if($entityId <= 0){
            $thumbnailPath = media_url_file($this->imagy->getThumbnail($file->path, 'mediumThumb'));
            return Response::json([
                'error' => false,
                'message' => 'Information of file.',
                'result' => ['path' => $thumbnailPath, 'imageId'=>$mediaId]
            ]);
        }
        $entity = $entityClass::find($entityId);
        $zone = $request->get('zone');
        if($request->get('single') == 1){
            $last_files        = $fileRepository->findMultipleFilesByZoneForEntity($zone, $entity);
            if($last_files){
                $array_detach = [];
                foreach($last_files as $file){
                    $array_detach[$file->id] =  ['imageable_type' => $entityClass, 'zone' => $zone];
                }
                Imageable::where(['imageable_type' => $entityClass,'imageable_id' => $entityId, 'zone' => $zone])->delete();
            }
            $entity->files()->attach([$mediaId=> ['imageable_type' => $entityClass, 'zone' => $zone]]);
        }else{
            $entity->files()->attach($mediaId, ['imageable_type' => $entityClass, 'zone' => $zone]);
        }
        $imageable = DB::table('media__imageables')->whereFileId($mediaId)->whereZone($zone)->whereImageableType($entityClass)->first();
        $file = $this->file->find($imageable->file_id);

        $thumbnailPath = $this->imagy->getThumbnail($file->path, 'mediumThumb');

        event(new FileWasLinked($file, $entity));

        return Response::json([
            'error' => false,
            'message' => 'The link has been added.',
            'result' => ['path' => $thumbnailPath, 'imageableId' => $imageable->id,'imageId'=>$mediaId]
        ]);
    }

    /**
     * Remove the record in the media__imageables table for the given id
     * @param Request $request
     */
    public function unlinkMedia(Request $request)
    {
        $imageableId = $request->get('imageableId');
        $deleted = DB::table('media__imageables')->whereId($imageableId)->delete();
        if (! $deleted) {
            return Response::json(['error' => true, 'message' => 'The file was not found.']);
        }

        event(new FileWasUnlinked($imageableId));

        return Response::json(['error' => false, 'message' => 'The link has been removed.']);
    }

    /**
     * Sort the record in the media__imageables table for the given array
     * @param Request $request
     */
    public function sortMedia(Request $request)
    {
        $imageableIdArray = $request->get('sortable');

        $order = 1;

        foreach ($imageableIdArray as $id) {
            DB::table('media__imageables')->whereId($id)->update(['order' => $order]);
            $order++;
        }

        return Response::json(['error' => false, 'message' => 'The items have been reorder.']);
    }
}
