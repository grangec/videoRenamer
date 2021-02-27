#!/usr/bin/php
<?php
// paramètres important
$cdeLineParams = [];
$maxLen = 2;
$excludeWords = [];
$iniParams=[];

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
    $cdeLineParams=getopt("b::h::R::v::d:");
    
    if(key_exists("h", $cdeLineParams)) {
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
    
    setGlobalLogLevel(key_exists("v", $cdeLineParams)?1:0);
    
    if(key_exists("b", $cdeLineParams)) {
        llog("Debug activé.");
        setGlobalLogLevel(2);
    }
    
    return $cdeLineParams;
}

function getIniParams() {
    global $argv;

    // Lit le fichier .ini du script et renvoi le tableau associatif resultant.
    llog("Ini File : ". $argv[0]. ".ini",2);
    $params=parse_ini_file($argv[0] . ".ini");
    if (getGlobalLogLevel()==2) {var_dump($params);}
    return $params;
}

function keyWords($filename) {
    // retourne les mot cles extrait du nom de fichier
    global $iniParams;

    // retourne le nom du fichier "nettoyé".
    llog("recupTmdb:" . $filename,2);
    $excludeWords=$iniParams["excludeWords"];
    $wordsMinLen=$iniParams["wordsMinLen"];
    
    //nettoyage du nom de fichier
    if(strlen($filename)>$wordsMinLen) {
        $excludeWords=explode(" ",strtoupper(implode(" ",$excludeWords)));
        $cleanFilename = strtoupper( trim(preg_replace('/[^[:alnum:]]/', " ", $filename)));
        $fnTab=explode(" ",$cleanFilename);
        //var_dump($fnTab);
        $resFilename="";
        foreach($fnTab as $mot) {
            if(strlen($mot)<($wordsMinLen+1) ||
                in_array($mot,$excludeWords)) {
                continue;
            }
            $resFilename=$resFilename . " " . $mot;
        }
        //Sécu pour pas tout enlever
        if(strlen($resFilename)<($wordsMinLen+1)) {
            $resFilename=strtoupper($filename);
        }
    }
    
    
    return trim($resFilename);
}

function recupTmdb($filename) {
    
    return keyWords($filename);
}

function videoRenameFile($dirname,$filename) {
    // retourne le triplet :
    // dir/name/keyWords soit repertoire/fichier/mots cles 
    // le fichier doit exister
    llog("videoRenameFile:" . $dirname . "-".$filename,2);

    $newFilename=recupTmdb($filename);
    
    $fileRenamed = [
        "dir" => $dirname,
        "name" => $filename,
        "keyWords" => $newFilename,
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
$cdeLineParams=init();
$iniParams=getIniParams();

// Traitement Principal
if(key_exists("d", $cdeLineParams) && $cdeLineParams["d"]) {
    $filesRenamedTab=[];
    
    $dirname=fRealPath($cdeLineParams["d"]);
    if(is_dir($dirname)) {
        llog("Traitement repertoire : " . $dirname,2);
        $dirname=$dirname.(substr($dirname,-1)=="/"?"":"/");
        $recurs=key_exists("R",$cdeLineParams);
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