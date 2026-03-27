<?php

namespace Tualo\Office\SystemFiles;

use Tualo\Office\Basic\TualoApplication as App;
use Tualo\Office\Basic\Route as BasicRoute;
use Tualo\Office\Basic\Path;
use MatthiasMullie\Minify\CSS;

class SystemFileCallbackResult
{
    public bool $success;
    public string $content;
    public string $mimetype;

    public function __construct(bool $success, string $content = '', string $mimetype = 'application/octet-stream')
    {
        $this->success = $success;
        $this->content = $content;
        $this->mimetype = $mimetype;
    }

    public static function success(string $content, string $mimetype = 'application/octet-stream'): self
    {
        return new self(true, $content, $mimetype);
    }

    public static function failure(): self
    {
        return new self(false);
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getMimetype(): string
    {
        return $this->mimetype;
    }
}

class SystemFile
{

    public static function executeCallbackAndStore(string $filename, callable $fileCallback): bool
    {
        $callbackResult = $fileCallback();
        if (!$callbackResult->success) {
            return false;
        }
        $fileContent = $callbackResult->getContent();
        $db = App::get('session')->getDB();
        $etag = md5($fileContent);
        $mimetype = $callbackResult->getMimetype();
        $db->direct('insert into system_file (filename, mimetype, etag) values ({filename}, {mimetype}, {etag}) on duplicate key update updated_at=CURRENT_TIMESTAMP, mimetype={mimetype}, etag={etag}', [
            'filename' => $filename,
            'mimetype' => $mimetype,
            'etag' => $etag
        ]);
        $db->direct('replace into system_file_data (filename, data) values ({filename}, {data})', [
            'filename' => $filename,
            'data' => base64_encode($fileContent)
        ]);
        return true;
    }

    /**
     * function for sending a file from database if the db file is newer
     * otherwise send not modified.
     * 
     * if not stored in database try to store the file from given 
     * callback function,
     * if the file is not found in database and callback function returns false send 404
     */
    public static function deliverFile(string $filename, callable $fileCallback)
    {
        $db = App::get('session')->getDB();
        $fileinfo = $db->singleRow('select * from system_file where filename={filename}', ['filename' => $filename]);
        if (!$fileinfo) {
            if (!self::executeCallbackAndStore($filename, $fileCallback)) {
                http_response_code(404);
                exit;
            }
            $fileinfo = $db->singleRow('select * from system_file where filename={filename}', ['filename' => $filename]);
            if (!$fileinfo) {
                http_response_code(404);
                exit;
            }
        }
        $etag = $fileinfo['etag'];
        $last_modified_time = strtotime($fileinfo['updated_at']);

        if (
            (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && (strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $last_modified_time))
            ||
            (isset($_SERVER['HTTP_IF_NONE_MATCH']) && (trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag))
        ) {
            header("HTTP/1.1 304 Not Modified");
            exit;
        }

        $filedata = $db->singleValue('select data from system_file_data where filename={filename}', ['filename' => $filename], 'data');

        if (!$filedata) {
            http_response_code(404);
            exit;
        }

        header("Last-Modified: " . gmdate("D, d M Y H:i:s", $last_modified_time) . " GMT");
        header("Etag: $etag");
        header('Cache-Control: public');
        App::contenttype($fileinfo['mimetype']);
        App::body(base64_decode($filedata));
        BasicRoute::$finished = true;
        http_response_code(200);
    }
}
