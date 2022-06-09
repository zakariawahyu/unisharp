<?php

namespace UniSharp\LaravelFilemanager\Controllers;
use App\Model\Images;

/**
 * Class ItemsController.
 */
class ItemsController extends LfmController
{
    /**
     * Get the images to load for a selected folder.
     *
     * @return mixed
     */

    public function __construct()
    {
        $this->image = new Images;
        date_default_timezone_set("Asia/Jakarta");
    }

    public function getItems()
    {
        $path = parent::getCurrentPath();
        $sort_type = request('sort_type');
        $search = request('search');

        $directories = parent::sortFilesAndDirectories(parent::getDirectories($path), $sort_type);

        if ($directories == null) {
            $curentDir= parent::getInternalPath($path);
            $replace = str_replace('/inews_new/', '', $curentDir);
            $date = str_replace('/', '-', $replace);

            $start = $date.' 00:00:00';
            $end = $date.' 23:59:59';
            $data = $this->image;
            $data = $data->select('name', 'path', 'caption', 'created_at');
            if ($search == '') {
                $data = $data->where('created_at', '>=', $start);
                $data = $data->where('created_at', '<=', $end);
            }
            $data = $data->where('name', 'like', '%' . $search . '%');
            $data = $data->orderBy('id', $sort_type);
            $data = $data->get();
    
            foreach ($data as $key => $value) {
                $data[$key]['icon'] = 'fa-image';
                $data[$key]['is_file'] = 'true';
                $data[$key]['thumb'] = config('api.asset_url').'/media/300'.$value['path'];
                $data[$key]['time'] = $value['created_at'];
                $data[$key]['type'] = 'image/png';
                $data[$key]['url'] = $value['path'];
            }

            $page = request('page');
            $pages = isset($page) ? $page : 0;
            $count = count($data);
            $perPage = 60;
            $totalOfPages = ceil($count / $perPage);
            $offset = $pages * $perPage;
            $files = array_slice(json_decode($data), $offset, $perPage);

            $pagination = [
                'curentPage' => $pages,
                'totalOfPages' => $totalOfPages,
                'offset' => $offset
            ];

            $items = array_merge($files, $directories);
        } else{
            $files = null;
            $items = $directories;
            $pagination = null;
        }

        return [
            'html' => (string) view($this->getView())->with([
                'files'       => $files, 
                'items'       => $items,
                'directories' => $directories,
                'paginations' => $pagination,
                'working_dir' => parent::getInternalPath($path)
            ]),
            'working_dir' => parent::getInternalPath($path),
        ];
    }

    private function getView()
    {
        $view_type = request('show_list');

        if (null === $view_type) {
            return $this->composeViewName($this->getStartupViewFromConfig());
        }

        $view_mapping = [
            '0' => 'grid',
            '1' => 'list'
        ];

        return $this->composeViewName($view_mapping[$view_type]);
    }

    private function composeViewName($view_type = 'grid')
    {
        return "laravel-filemanager::$view_type-view";
    }

    private function getStartupViewFromConfig($default = 'grid')
    {
        $type_key = parent::currentLfmType();
        $startup_view = config('lfm.' . $type_key . 's_startup_view', $default);
        return $startup_view;
    }
}
