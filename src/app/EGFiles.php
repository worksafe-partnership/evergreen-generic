<?php

namespace Evergreen\Generic\App;

use DB;
use File;
use Bhash;
use Carbon;
use Storage;
use Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/* @TODO
    - php artisan storage:link - run as part of EGL setup script?
    - redo migration
*/
class EGFiles extends Model
{
    use SoftDeletes;
    protected $table = "files";
    protected $fillable = [
        'title',
        'filename',
        'location',
        'mime_type',
        'entity',
        'entity_id'
    ];

    public static function store($request, $column, $original = null, $options = [])
    {
        if (is_array($column)) {
            $return = [];
            foreach ($column as $col) {
                $file = EGFiles::store($request, $col, $original, $options);
                if ($file) {
                    $return[$col] = $file;
                }
            }

            return $return;
        }

        if (isset($request[$column])) {
            if (is_array($request[$column])) {
                $return = [];
                foreach ($request[$column] as $f) {
                    $r = [
                        $column => $f
                    ];
                    $file = EGFiles::store($r, $column, $original, $options);
                    if ($file) {
                        $return[] = $file;
                    }
                }

                return $return;
            }
            if ($request[$column]->isValid()) {
                $file = $request[$column];
                $data = ['mime_type' => $file->getClientMimeType()];
                
                $now = Carbon::now();
                $storagePath = "/files/";
                
                if (isset($options['entity'])) {
                    $data['entity'] = $options['entity'];
                    $storagePath.= $options['entity']."/";
                }

                $storagePath.= $now->year."/".$now->month."/".$now->day;
                
                if (isset($options['filename'])) {
                    $store = $file->storePubliclyAs($storagePath, $options['filename'], $options);
                    $data['filename'] = $filename;
                } else {
                    $store = $file->storePublicly($storagePath, $options);
                    $data['filename'] = $file->getClientOriginalName();
                }

                if (isset($options['title'])) {
                    $data['title'] = $options['title'];
                } else {
                    $data['title'] = $data['filename'];
                }

                if ($store) {
                    $data['location'] = $store;
                    $file = EGFiles::create($data);

                    if (!is_null($original) && isset($original[$column])) {
                        $old = EGFiles::find($original[$column]);
                        if (!is_null($old)) {
                            $old->delete();
                        }
                    }
                    return $file->id;
                }
            }
        }
        return false;
    }

    public static function download($id)
    {
        $file = EGFiles::find($id);
        if (!is_null($file) && Storage::exists($file->location)) {
            return Response::download(storage_path()."/app/".$file->location, $file->title);
        }

        abort(404);
    }

    public static function image($id)
    {
        $file = EGFiles::find($id);
        if (!is_null($file) && Storage::exists($file->location)) {
            $path = storage_path()."/app/".$file->location;
            $file = File::get($path);
            $type = File::mimeType($path);

            $response = Response::make($file, 200);
            $response->header("Content-Type", $type);

            return $response;
        }

        abort(404);
    }
}
