<?php

namespace UniSharp\LaravelFilemanager\Controllers;

use UniSharp\LaravelFilemanager\Traits\LfmHelpers;
use Illuminate\Support\Facades\File;

/**
 * Class LfmController.
 */
class LfmController extends Controller
{
    use LfmHelpers;

    protected static $success_response = 'OK';

    public function __construct()
    {
        date_default_timezone_set("Asia/Jakarta");
        $this->applyIniOverrides();
    }

    /**
     * Show the filemanager.
     *
     * @return mixed
     */
    public function show()
    {
        $this->makeStaticPath(); /* auto create needed folder */

        return view('laravel-filemanager::index',['redirect'=>'inews_new\/'.date('Y').'\/'.date('m').'\/'.date('d')]);
    }

    public function makeStaticPath()
    {
        $path_inside_profile = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
        $path['path'][] = $this->getCurrentPath(date('Y').DIRECTORY_SEPARATOR.date('m').DIRECTORY_SEPARATOR.date('d'));
        $path['path_ilustrasi'][] = $this->getCurrentPath(date('Y').'/Ilustrasi');
        $path['path_profil'][] = $this->getCurrentPath(date('Y').'/Profil');
        $path['path_profil'][] = $path_inside_profile;
        $path['path_multi'][] = $this->getCurrentPath('Multimedia/'.date('Y').DIRECTORY_SEPARATOR.date('m').DIRECTORY_SEPARATOR.date('d'));

        foreach($path as $key => $value)
        {
            File::makeDirectory($value[0], 0777, true, true);
            if(count($value) > 1)
            {
                foreach($value[1] as $key2 => $value2)
                {
                    File::makeDirectory($value[0].DIRECTORY_SEPARATOR.$value2, 0777, true, true);
                }
            }
        }
        return $path;
    }

    public function getErrors()
    {
        $arr_errors = [];

        if (! extension_loaded('gd') && ! extension_loaded('imagick')) {
            array_push($arr_errors, trans('laravel-filemanager::lfm.message-extension_not_found'));
        }

        $type_key = $this->currentLfmType();
        $mine_config = 'lfm.valid_' . $type_key . '_mimetypes';
        $config_error = null;

        if (! is_array(config($mine_config))) {
            array_push($arr_errors, 'Config : ' . $mine_config . ' is not a valid array.');
        }

        return $arr_errors;
    }
}
