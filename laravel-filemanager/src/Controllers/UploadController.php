<?php

namespace UniSharp\LaravelFilemanager\Controllers;

use App\Model\Images;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use UniSharp\LaravelFilemanager\Events\ImageIsUploading;
use UniSharp\LaravelFilemanager\Events\ImageWasUploaded;

/**
 * Class UploadController.
 */
class UploadController extends LfmController
{
    protected $errors;

    public function __construct()
    {
        parent::__construct();
        $this->errors = [];
    }

    /**
     * Upload files
     *
     * @param void
     * @return string
     */
    function check_upload($current_path)
    {
        $date_path = date('Y').DIRECTORY_SEPARATOR.date('m').DIRECTORY_SEPARATOR.date('d');
        $path_inside_profile = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];

        if(strpos($current_path,$date_path) != false){
            return true;
        }
        if(strpos($current_path,"Multimedia".DIRECTORY_SEPARATOR.$date_path) != false){
            return true;
        }
        if(strpos($current_path,date('Y').DIRECTORY_SEPARATOR."Ilustrasi") != false){
            return true;
        }
        foreach($path_inside_profile as $value){
            if(strpos($current_path,date('Y').DIRECTORY_SEPARATOR."Profil".DIRECTORY_SEPARATOR.$value) != false){
                return true;
            }
        }
        return false;
    }

    public function upload()
    {
        $files = request()->file('upload');

        if ($this->check_upload(parent::getCurrentPath())!= true){
            array_push($this->errors, parent::error('not-folder'));
            return $this->errors;
        }

        // single file
        if (!is_array($files)) {
            $file = $files;
            if (!$this->fileIsValid($file)) {
                return $this->errors;
            }

            $filename = $this->proceedSingleUpload($file);
            if ($filename === false) {
                return $this->errors;
            }

            // upload via ckeditor 'Upload' tab
            return $this->useFile($filename);
        }


        // Multiple files
        foreach ($files as $file) {
            if (!$this->fileIsValid($file)) {
                continue;
            }
            $this->proceedSingleUpload($file);
        }

        return count($this->errors) > 0 ? $this->errors : parent::$success_response;
    }

    private function proceedSingleUpload($file)
    {
        $new_filename = $this->getNewName($file);

        if(strlen($new_filename) > config('lfm.max_len_filename'))
        {
            return array_push($this->errors, parent::error('max-len').', Max:'.config('lfm.max_len_filename').' characters');
        }

        $new_file_path = parent::getCurrentPath($new_filename);

        event(new ImageIsUploading($new_file_path));
        try {
            if (parent::fileIsImage($file) && !in_array($file->getMimeType(), ['image/gif', 'image/svg+xml'])) {
                // Handle image rotation
                Image::make($file->getRealPath())
                    ->orientate() //Apply orientation from exif data
                    ->save($new_file_path);

                // Generate a thumbnail
                if (parent::imageShouldHaveThumb($file)) {
                    $this->makeThumb($new_filename);
                }
            } else {
                // Create (move) the file
                File::move($file->getRealPath(), $new_file_path);
            }
            if (config('lfm.should_change_file_mode', true)) {
                chmod($new_file_path, config('lfm.create_file_mode', 0644));
            }

            /* SIMPAN IMAGE KE DATABASE */
            $model_image = new Images();
            $name = ucwords(
                preg_replace('!\s+!', ' ',
                    preg_replace("/[^A-Za-z0-9[:space:]]/", " ",
                        explode('.', collect(explode('/', $new_filename))->pop())[0]
                    )
                )
            );
            $filepath_name = '/'.str_replace(DIRECTORY_SEPARATOR,'/',
                substr($new_file_path, strpos($new_file_path, DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR) + 1)
            );
            $insert[] = [
                'name' => $name,
                'path' => $filepath_name,
                'created_at' => now()->toDateTimeString()
            ];
            $model_image->insert($insert);
            /* SIMPAN IMAGE KE DATABASE */

        } catch (\Exception $e) {
            array_push($this->errors, parent::error('invalid'));

            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }

        // TODO should be "FileWasUploaded"
        event(new ImageWasUploaded(realpath($new_file_path)));

        return $new_filename;
    }

    private function fileIsValid($file)
    {
        if (empty($file)) {
            array_push($this->errors, parent::error('file-empty'));
            return false;
        }

        if (! $file instanceof UploadedFile) {
            array_push($this->errors, parent::error('instance'));
            return false;
        }

        if ($file->getError() == UPLOAD_ERR_INI_SIZE) {
            $max_size = ini_get('upload_max_filesize');
            array_push($this->errors, parent::error('file-size', ['max' => $max_size]));
            return false;
        }

        if ($file->getError() != UPLOAD_ERR_OK) {
            $msg = 'File failed to upload. Error code: ' . $file->getError();
            array_push($this->errors, $msg);
            return false;
        }

        $new_filename = $this->getNewName($file);

        if(strlen($new_filename) > config('lfm.max_len_filename'))
        {
            return array_push($this->errors, parent::error('max-len').', Max:'.config('lfm.max_len_filename').' characters');
        }

        if (File::exists(parent::getCurrentPath($new_filename))) {
            array_push($this->errors, parent::error('file-exist'));
            return false;
        }

        $mimetype = $file->getMimeType();

        // Bytes to KB
        $file_size = $file->getSize() / 1024;
        $type_key = parent::currentLfmType();

        if (config('lfm.should_validate_mime', false)) {
            $mine_config = 'lfm.valid_' . $type_key . '_mimetypes';
            $valid_mimetypes = config($mine_config, []);
            if (false === in_array($mimetype, $valid_mimetypes)) {
                array_push($this->errors, parent::error('mime') . $mimetype);
                return false;
            }
        }

        if (config('lfm.should_validate_size', false)) {
            $max_size = config('lfm.max_' . $type_key . '_size', 0);
            if ($file_size > $max_size) {
                array_push($this->errors, parent::error('size'));
                return false;
            }
        }

        return true;
    }

    protected function replaceInsecureSuffix($name)
    {
        return preg_replace("/\.php$/i", '', $name);
    }

    private function getNewName($file)
    {
        $new_filename = parent::translateFromUtf8(trim($this->pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)));
        if (config('lfm.rename_file') === true) {
            $new_filename = uniqid();
        } elseif (config('lfm.alphanumeric_filename') === true) {
//            $new_filename = preg_replace('/[^A-Za-z0-9\-\']/', '_', $new_filename);
            $new_filename = preg_replace("/[^A-Za-z0-9]/","_",$new_filename);
        }

        return $new_filename . $this->replaceInsecureSuffix('.' . $file->getClientOriginalExtension());
    }

    private function makeThumb($new_filename)
    {
        // create thumb folder
        parent::createFolderByPath(parent::getThumbPath());

        // create thumb image
        Image::make(parent::getCurrentPath($new_filename))
            ->fit(config('lfm.thumb_img_width', 200), config('lfm.thumb_img_height', 200))
            ->save(parent::getThumbPath($new_filename));
    }

    private function useFile($new_filename)
    {
        $file = parent::getFileUrl($new_filename);

        $responseType = request()->input('responseType');
        if ($responseType && $responseType == 'json') {
            return [
                "uploaded" => 1,
                "fileName" => $new_filename,
                "url" => $file,
            ];
        }

        return "<script type='text/javascript'>

        function getUrlParam(paramName) {
            var reParam = new RegExp('(?:[\?&]|&)' + paramName + '=([^&]+)', 'i');
            var match = window.location.search.match(reParam);
            return ( match && match.length > 1 ) ? match[1] : null;
        }

        var funcNum = getUrlParam('CKEditorFuncNum');

        var par = window.parent,
            op = window.opener,
            o = (par && par.CKEDITOR) ? par : ((op && op.CKEDITOR) ? op : false);

        if (op) window.close();
        if (o !== false) o.CKEDITOR.tools.callFunction(funcNum, '$file');
        </script>";
    }

    private function pathinfo($path, $options = null)
    {
        $path = urlencode($path);
        $parts = is_null($options) ? pathinfo($path) : pathinfo($path, $options);
        if (is_array($parts)) {
            foreach ($parts as $field => $value) {
                $parts[$field] = urldecode($value);
            }
        } else {
            $parts = urldecode($parts);
        }

        return $parts;
    }
}
