#!/usr/bin/php
<?php

$logLevel=0; // niveau général de log

function setGlobalLogLevel($level) {
    llog("Log Level = " . $level);
    global $logLevel;
    $logLevel=$level;    
}

function getGlobalLogLevel() {
    global $logLevel;
    return $logLevel;
}

function llog($ch,$level=0) {
    if($level<=getGlobalLogLevel()) {
        echo $ch ."\n";
    }
}

function isDir ($file)
{
//    return ((fileperms("$file") & 0x4000) == 0x4000);
    return is_dir($file);
}

function videoRenameFile($dirname,$filename) {
    llog("videoRenameFile:" . $dirname . "-".$filename,1);
    $fileRenamed = [
        "dir" => $dirname,
        "name" => $filename,
        "newName" => "NEW_".$filename,
    ];
    
    return $fileRenamed;
}

function videoRenameDir($dirname,$recurs) {
    llog("videoRenameDir:" . $dirname,1);
    $d = dir($dirname);
    $filesRenamed=[];
    while (false !== ($entry = $d->read())) {
        llog("Parcours : " . $entry,1);
        if($entry!="." && $entry != "..") {
            if(isDir($dirname .$entry) && $recurs) {
                $filesRenamed=array_merge($filesRenamed,videoRenameDir($dirname . $entry,$recurs));
            } elseif (is_file($dirname .$entry)) {
                $filesRenamed[]=videoRenameFile($dirname,$entry);
            }
        }
    }
    $d->close();
    
    return $filesRenamed;
}

function fRealPath($dirname) {
    llog("fRealPath : " . $dirname,1);
    $rpath="";
    if(substr($dirname, 0, 1)=="~") {
        $rpath=getenv("HOME").substr($dirname, 1);        
    } else {
        $rpath=$dirname;
    }
    return $rpath;
}

$params=getopt("R::v::d:");
if(key_exists("d", $params) && $params["d"]) {
    $filesRenamedTab=[];
    
    $debug=key_exists("v", $params);
    setGlobalLogLevel($debug?1:0);
    
    $dirname=fRealPath($params["d"]);
    if(isDir($dirname)) {
        llog("Traitement repertoire : " . $dirname,1);
        $dirname=$dirname.(substr($dirname,-1)=="/"?"":"/");
        $recurs=key_exists("R",$params);
        $filesRenamedTab=videoRenameDir($dirname,$recurs);
    } elseif (is_file($dirname)) {
        llog("Traitement fichier : " . $dirname,1);
        $filesRenamedTab[$dirname]=videoRenameFile($dirname);
    } else {
        llog("'".$dirname . "' n'est pas un dossier");
        exit(2);
    }
    llog("Tableau des fichier renommés :");
    llog(json_encode($filesRenamedTab,JSON_PRETTY_PRINT));
} else {
    llog("Vous devez préciser un dossier (option -d)");
    exit(1);
}
exit(0);