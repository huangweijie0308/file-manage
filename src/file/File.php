<?php
/**
 * Created by PhpStorm.
 * User: hwj
 * Date: 2020/4/27 0027
 * Time: 16:03
 */
namespace huangweijie\file;

class File
{

    /**
     * 文档根目录
     * @var string
     */
    private $rootPath;

    /**
     * 当前路径 file || folder
     * @var string
     */
    private $path;

    /**
     * 当前动作
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $viewPath;

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        $this->viewPath = basename(__FILE__) . DIRECTORY_SEPARATOR . 'manage' . DIRECTORY_SEPARATOR . 'view.html';
    }

    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;

        return $this;
    }

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    protected function catalogList($path)
    {
        $objects = is_readable($path) ? scandir($path) : array();
        $folders = array();
        $files = array();
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $new_path = $path . '/' . $file;
                if (is_file($new_path)) {
                    $files[] = $file;
                } elseif (is_dir($new_path) && $file != '.' && $file != '..') {
                    $folders[] = $file;
                }
            }
        }

        if (!empty($files)) {
            natcasesort($files);
        }
        if (!empty($folders)) {
            natcasesort($folders);
        }

        return array('files' => $files, 'folders' => $folders);
    }

    public function handle()
    {
        if (!isset($this->rootPath)) {
            throw new LogicException("The root directory is not set.");
        }

        $path = $this->rootPath;
        if (isset($this->path)) {
            $path .= $this->path;
        }

        $catalogList = $this->catalogList($path);
        require_once $this->viewPath;
    }

    public function getContents()
    {

    }
}