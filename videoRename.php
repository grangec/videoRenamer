#!/usr/bin/php
<?php

// gestion du niveau de log
$logLevel=0; // niveau général de log
function setGlobalLogLevel($level) {
    global $logLevel;
    $logLevel=$level;
}

function getGlobalLogLevel() {
    global $logLevel;
    return $logLevel;
}

function llog($ch="",$level=0) {
    // fonction de log principale
    if($level<=getGlobalLogLevel()) {
        echo $ch ."\n";
    }
}

function init() {
    // divers initialisations
    // gestion de options de la ligne de commande
    // retourne le tableau des options (getOpt)
    $params=getopt("b::h::R::v::d:");
    
    if(key_exists("h", $params)) {
        llog("==============");
        llog(" videoRenamer");
        llog("==============");
        llog();
        llog("-d <directory> : The Directory containing video files to rename.");
        llog("-R             : Recursivly.");
        llog("-v             : Verbose");
        llog("-b             : Debug");
        llog("-h             : Help");
        llog();
        llog("02/2021 : https://github.com/grangec/videoRenamer");
        exit(0);
    }
    
    setGlobalLogLevel(key_exists("v", $params)?1:0);
    
    if(key_exists("b", $params)) {
        llog("Debug activé.");
        setGlobalLogLevel(2);
    }
    
    return $params;
}

function recupTmdb($filename) {
    // retourne le nom du fichier depuis tmdb.
    llog("recupTmdb:" . $filename,2);
    
    $cleanFilename = trim(preg_replace('/[^[:alnum:]]/', " ", $filename));
    $fnTab=explode(" ",$cleanFilename);
    //var_dump($fnTab);
    $resFilename="";
    foreach($fnTab as $mot) {
        if(strlen($mot)>2) {
            $resFilename=$resFilename . " " . $mot;    
        }
    }
    
    return trim($resFilename);
}

function videoRenameFile($dirname,$filename) {
    // retourne le triplet :
    // repertoire / fichier / fichier renommé
    // le fichier doit exister
    llog("videoRenameFile:" . $dirname . "-".$filename,2);

    $newFilename=recupTmdb($filename);
    
    $fileRenamed = [
        "dir" => $dirname,
        "name" => $filename,
        "newName" => $newFilename,
    ];
    
    return $fileRenamed;
}

function videoRenameDir($dirname,$recurs) {
    // retourne l'array contenant les triplets :
    // repertoire / fichier / fichier renommé 
    llog("videoRenameDir:" . $dirname,2);
    $d = dir($dirname);
    $filesRenamed=[];
    while (false !== ($entry = $d->read())) {
        llog("Parcours : " . $entry,2);
        if($entry!="." && $entry != "..") {
            if(is_dir($dirname .$entry) && $recurs) {
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
    // retourne le chemin absolu correspondant.
    llog("fRealPath : " . $dirname,2);
    $rpath="";
    if(substr($dirname, 0, 1)=="~") {
        $rpath=getenv("HOME").substr($dirname, 1);        
    } else {
        $rpath=$dirname;
    }
    return $rpath;
}

// Main
$params=init();

// Traitement Principal
if(key_exists("d", $params) && $params["d"]) {
    $filesRenamedTab=[];
    
    $dirname=fRealPath($params["d"]);
    if(is_dir($dirname)) {
        llog("Traitement repertoire : " . $dirname,2);
        $dirname=$dirname.(substr($dirname,-1)=="/"?"":"/");
        $recurs=key_exists("R",$params);
        $filesRenamedTab=videoRenameDir($dirname,$recurs);
    } elseif (is_file($dirname)) {
        llog("Traitement fichier : " . $dirname,2);
        $filesRenamedTab[$dirname]=videoRenameFile($dirname);
    } else {
        llog("'".$dirname . "' n'est pas un dossier");
        exit(2);
    }
    llog("Tableau des fichier renommés :",2);
    llog(json_encode($filesRenamedTab,JSON_PRETTY_PRINT),1);
} else {
    llog("Vous devez préciser un dossier (option -d)");
    exit(1);
}

exit(0);