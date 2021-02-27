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
        echo date("Y-m-d H:i:s") . " : ". trim($ch) ."\n";
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
    // retourne les mots cles extrait du nom de fichier dans un tableau
    global $iniParams;

    // retourne le nom du fichier "nettoyé".
    llog("recupTmdb:" . $filename,2);
    $excludeWords=$iniParams["excludeWords"];
    $wordsMinLen=$iniParams["wordsMinLen"];
    
    //nettoyage du nom de fichier
    $resKeyWords=[];
    if(strlen($filename)>$wordsMinLen) {
        $excludeWords=implode(" ",$excludeWords);
        $cleanFilename = trim(preg_replace('/[^[:alnum:]]/', " ", $filename));
        $fnTab=explode(" ",$cleanFilename);
        foreach($fnTab as $mot) {
            if(strlen($mot)<($wordsMinLen+1) ||
                stripos($excludeWords,$mot)!==false) {
                continue;
            }
            $resKeyWords[]=$mot;
        }
        //Sécu pour pas tout enlever
        if(count($resKeyWords)==0) {
            $resKeyWords=$fnTab;
        }
    }
    
    return $resKeyWords;
}

function recupTmdb($filename) {
    global $iniParams;
    // Récupération des mot cles
    $keyWords=keyWords($filename);
    
    while(count($keyWords)>0) {
        //Chaine du parametre query de l'API
        $keyWordsStr=implode("+",$keyWords);
        //l'URL
        $url= $iniParams["tmdbBaseUrl"] . "search/movie?api_key=" . $iniParams["apiKey"] . "&query=".$keyWordsStr . "&language=".$iniParams["language"];
        llog("URL:".$url,2);
        
        // Requeter
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        $response = curl_exec($ch);
        curl_close($ch);
        $results = json_decode($response, true);
        
        llog("results:".json_encode($results,JSON_PRETTY_PRINT),2);
        
        if(array_key_exists("total_results",$results) &&  $results["total_results"]==0) {
            array_pop($keyWords);       
        } else {
            break;
        }
    }

    if(count($keyWords)==0) {
        return false;
    }
    
    return $results["results"][0];
}

function videoRenameFile($dirname,$filename) {
    // retourne le triplet :
    // dir/name/keyWords soit repertoire/fichier/mots cles 
    // le fichier doit exister
    llog("videoRenameFile:" . $dirname . "-".$filename,2);

    $tmdbDatas=recupTmdb($filename);
    
    $newName[]=$tmdbDatas["title"];
    $newName[]=$tmdbDatas["release_date"];
    $newName[]=pathinfo($filename)["extension"];
    
    $fileRenamed = [
        "dir" => $dirname,
        "name" => $filename,
        "newName" => implode(".",$newName),
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