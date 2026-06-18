<?php
namespace App\Modules\Base\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class FileHelper {

    function upload_file($file, $config = []){

        ini_set('memory_limit', '512M');

        try{

            $originalName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file->getClientOriginalName());

            $extension = strtolower($file->getClientOriginalExtension());

            $filename = md5(now() . '-' . $originalName . '-' . random_string_generator(20)) . '.' . $extension;

            $directory_path = strtolower(random_string_generator(3)) . "/" . strtolower(random_string_generator(3));

            $upload_directory = '';

            if(array_key_exists('upload_directory', $config)){

                $upload_directory = rtrim(ltrim($config['upload_directory'], "/"), "/") . "/";
            }

            $storage_path = "uploads/" . $upload_directory . $directory_path . "/" . $filename;

            if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])){

                $image = Image::read($file->getRealPath());

                /*
                Need to implement resize logic
                */

                // if($request->has($fixSizeField) && $request->{$fixSizeField} != ''){

                //     $image = encodeToTargetSize($image, 'jpg', $request->{$fixSizeField} * 1024);

                //     Storage::put($storage_path, $image);

                // }else{

                    Storage::put($storage_path, (string) $image->encodeByExtension($extension, 80));
                // }

            }else{

                Storage::put($storage_path, file_get_contents($file->getRealPath()));
            }

            return (object)[
                'status' => true,
                'storage_path' => $storage_path,
                'upload_path' => $directory_path . "/" . $filename,
                'upload_directory' => $upload_directory,
                'directory_path' => $directory_path,
                'file_name' => $filename,
                'extension' => $extension,
                'original_name' => $originalName
            ];
        }catch(\Exception $e){

            return (object)['status' => false, 'message' => $e->getMessage()];
        }
    }

    function file_category(string $pathOrFilename): string {

        $extension = strtolower(pathinfo($pathOrFilename, PATHINFO_EXTENSION));

        return match ($extension){
        
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp', 'tiff', 'ico' => 'Image',

            'pdf' => 'PDF',
            'doc', 'docx' => 'Word',
            'xls', 'xlsx', 'csv' => 'Spreadsheet',
            'ppt', 'pptx' => 'Presentation',
            'rtf', 'odt' => 'Document',

            'mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac' => 'Audio',

            'mp4', 'mov', 'avi', 'wmv', 'mkv', 'webm', 'flv' => 'Video',

            'zip', 'rar', '7z', 'tar', 'gz' => 'Archive',

            'php', 'js', 'css', 'html', 'py', 'rb', 'go' => 'Code',
            'json', 'xml', 'sql' => 'Data',

            'txt', 'md', 'log' => 'Text',

            default => 'File',
        };
    }
}