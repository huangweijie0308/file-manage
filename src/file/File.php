<?php
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: huangweijie <1539369355@qq.com>
// +----------------------------------------------------------------------

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
    private $path = '';

    /**
     * 当前动作
     * @var string
     */
    private $action;

    /**
     * @var string
     */
    private $templatePath;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $output;

    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $allowFileOperation = ['down'];

    public function __construct($allowFileOperation = [])
    {
        empty($allowFileOperation) || $this->allowFileOperation = $allowFileOperation;
        $this->init();
    }

    private function init()
    {
        $this->templatePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'template' . DIRECTORY_SEPARATOR;
        $this->path = empty($_GET['p'])? '': urldecode($_GET['p']);
        $this->action = 'ls';
        if (!empty($_GET['file'])) {
            $this->file = trim($_GET['file']);
            $this->action = empty($_GET['a'])? 'cat': trim($_GET['a']);
        }
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

    protected function catalog($path)
    {
        $objects = is_readable($path) ? scandir($path) : [];
        $folders = [];
        $files = [];
        if (is_array($objects)) {
            foreach ($objects as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $newPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_file($newPath)) {
                    $files[] = $file;
                } elseif (is_dir($newPath) && $file != '.' && $file != '..') {
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

        return ['files' => $files, 'folders' => $folders];
    }

    protected function catalogInfo($path)
    {
        $catalogInfo = [
            'list' => [],
            'statistics' => '',
        ];

        $number = 1;
        $totalFileSize = 0;
        $totalFileNunber = $totalFolderNunber = 0;
        foreach ($this->catalog($path) as $type => $catalog) {
            if (!in_array($type, ['files', 'folders'])) {
                continue;
            }

            foreach ($catalog as $typeItem) {
                $currentPath = $path . DIRECTORY_SEPARATOR . $typeItem;
                $fileSize = 'Folder';
                if ($type != 'folders') {
                    $totalFileSize += $fileSize = filesize($currentPath);
                    $totalFileNunber++;
                } else {
                    $totalFolderNunber++;
                }

                $catalogInfo['list'][] = [
                    'number' => $number,
                    'name' => $typeItem,
                    'parent' => '',
                    'type' => $type,
                    'size' => $type == 'folders'? 'Folder': $this->filesize($fileSize),
                    'modifiedTime' => date('Y-m-d H:i:s', filemtime($path . DIRECTORY_SEPARATOR . $typeItem))
                ];

                $number++;
            }
        }

        $catalogInfo['statistics'] = '总文件数：' . $totalFileNunber . '&nbsp&nbsp总文件大小：' . $this->filesize($totalFileSize) . '&nbsp&nbsp总文件夹数：' . $totalFolderNunber;

        return $catalogInfo;
    }

    protected function filesize($size)
    {
        if ($size < 1000) {
            return sprintf('%s B', $size);
        } elseif (($size / 1024) < 1000) {
            return sprintf('%s K', round(($size / 1024), 2));
        } elseif (($size / 1024 / 1024) < 1000) {
            return sprintf('%s M', round(($size / 1024 / 1024), 2));
        } elseif (($size / 1024 / 1024 / 1024) < 1000) {
            return sprintf('%s G', round(($size / 1024 / 1024 / 1024), 2));
        } else {
            return sprintf('%s T', round(($size / 1024 / 1024 / 1024 / 1024), 2));
        }
    }

    protected function view($template, $data = [])
    {
        $file = $this->templatePath . $template;

        if (file_exists($file)) {
            extract($data);

            ob_start();

            require($file);

            $output = ob_get_contents();

            ob_end_clean();
        } else {
            throw new LogicException('Error: Could not load template ' . $file . '!');
        }

        return $output;
    }

    protected function output()
    {
        if ($this->output) {
            if (!headers_sent()) {
                foreach ($this->headers as $header) {
                    header($header, true);
                }
            }

            echo $this->output;
        }
    }

    protected function show($data = [])
    {
        $this->output = $this->view('show.html', $data);
        $this->output();
    }

    protected function del($path, $data = [])
    {
        $file = $this->rootPath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR . $this->file;
        @unlink($file);
        return $this->ls($path, $data);
    }

    protected function down()
    {
        $file = $this->rootPath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR . $this->file;

        if (!is_file($file)) {
            header('HTTP/1.1 404 NOT FOUND');
        }

        $fileHandle = fopen ($file, "rb");

        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length: " . filesize($file));
        Header("Content-Disposition: attachment; filename=" . $this->file);

        $stream = fopen('php://output', 'w');
        @fwrite($stream, fread ($fileHandle, filesize($file)));
    }

    protected function cat($path, $data = [])
    {
        $file = $this->rootPath . DIRECTORY_SEPARATOR . $this->path . DIRECTORY_SEPARATOR . $this->file;
        if (!is_file($file)) {
            return;
        }

        $catData = [];
        $catData['content'] = str_replace("\r\n","<br/>", file_get_contents($file));
        $catData['breadcrumb'] = $this->view('breadcrumb.html', ['path' => empty($this->path)? $this->file: urlencode($this->path . '/' . $this->file)]);

        $data = array_merge([
            'header'  => '',
            'content' => '',
            'footer'  => '',
        ], $data);

        $data['content'] =  $this->view('cat.html', $catData);
        $this->show($data);
    }

    protected function ls($path, $data = [])
    {
        $lsData = [];
        $lsData['catalogInfo'] = $this->catalogInfo($path);
        $lsData['path'] = empty($this->path)? '': urlencode($this->path);
        $lsData['breadcrumb'] = $this->view('breadcrumb.html', ['path' => $lsData['path']]);
        $lsData['authority'] = $this->allowFileOperation;

        $data = array_merge([
            'header'  => '',
            'content' => '',
            'footer'  => '',
        ], $data);

        $data['content'] =  $this->view('ls.html', $lsData);
        $this->show($data);
    }

    public function handle()
    {
        if (!isset($this->rootPath)) {
            throw new \LogicException("The root directory is not set.");
        }

        $path = $this->rootPath;
        if (!empty($this->path)) {
            $path .= DIRECTORY_SEPARATOR . $this->path;
        }

        $data = [];
        $data['header']  =  $this->view('header.html');
        $data['footer']  =  $this->view('footer.html');

        if (!is_callable([$this, $this->action])) {
            throw new \InvalidArgumentException("invalid operation : " . $this->action);
        }

        $this->{$this->action}($path, $data);
    }

}