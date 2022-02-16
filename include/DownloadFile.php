<?php

/*

Represents a file for downloading
Will sensibly populate fields either from JSON file 
or by calling stat on the file itself.

*/
class DownloadFile{

    private ?string $path = null; // path is not exposed

    public ?string $id = null;
    public ?string $fileName = null;
    public ?string $uri = null;
    public ?string $title = null;
    public ?string $description = null;
    public ?int $sizeBytes = null;
    public ?string $sizeHuman = null;
    public ?string $created = null;

    function __construct($filePath){

        $this->path = $filePath;
        $this->id = md5($filePath);

        // file name
        $path_parts = pathinfo($filePath);
        $this->fileName = $path_parts['basename'];



        // URI to the file - this is making assumptions but could be more intelligent in future
        // assuming we are runing as /gql.php and the downloads are in /downloads/*
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') $link = "https";
        else $link = "http";
        $link .= "://" . $_SERVER['HTTP_HOST'];
        $request_path_parts = pathinfo($_SERVER['REQUEST_URI']);
        $link .= $request_path_parts['dirname'];
        $this->uri = $link . '/' . $filePath;

        // if the file doesn't exist just null it all out
        if(!file_exists($filePath)){
            $meta = json_decode( file_get_contents($filePath . ".json") );
            $this->title = $meta->title;
            $this->description = "File does not exist.";
        }else{

            // If it has a json sidecar then we get the data from there
            if(file_exists($filePath . ".json")){
                $meta = json_decode( file_get_contents($filePath . ".json") );
                $this->title = $meta->title;
                $this->description = $meta->description;
                $this->sizeBytes = $meta->size_bytes;
                $this->sizeHuman = $meta->size_human;
                $this->created = $meta->created;
            }else{

                // no sidecar so we generate data from file
                $this->title = str_replace(array('_', '.'), ' ', $path_parts['filename']);
                $this->sizeBytes = filesize($filePath);
                $this->sizeHuman = DownloadFile::humanFileSize($this->sizeBytes);
                $this->created = date(DATE_ATOM, filectime($filePath));

            }

        }

    }

    public static function humanFileSize($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

}