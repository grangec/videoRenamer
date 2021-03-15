#!/usr/bin/php
<?php
// paramètres important
$cdeLineParams = [];
$maxLen = 2;
$excludeWords = [];
$iniParams=[];

CONST LLOG_BASE  = 0;
CONST LLOG_INFO  = 1;
CONST LLOG_DEBUG = 2;

// gestion du niveau de log
$logLevel=LLOG_BASE; // niveau général de log

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
    $cdeLineParams=getopt("d:b::h::R::v::g::");
    
    if(key_exists("h", $cdeLineParams)) {
        llog("==============");
        llog(" videoRenamer");
        llog("==============");
        llog();
        llog("-d <directory> : The Directory containing video files to rename.");
        llog("-R             : Recursivly.");
        llog("-g             : Go (renomage effectif)");
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
        setGlobalLogLevel(LLOG_DEBUG);
    }
    
    return $cdeLineParams;
}

function getIniParams() {
    global $argv;

    // Lit le fichier .ini du script et renvoi le tableau associatif resultant.
    llog("Ini File : ". $argv[0]. ".ini",LLOG_DEBUG);
    $params=parse_ini_file($argv[0] . ".ini");
    if (getGlobalLogLevel()==LLOG_DEBUG) {var_dump($params);}
    return $params;
}

function keyWords($filename,$wordsMinLen=9999) {
    // retourne les mots cles extrait du nom de fichier dans un tableau
    // Par defaut, prend dans le .ini
    global $iniParams;

    // retourne le nom du fichier "nettoyé".
    llog("recupTmdb:" . $filename,LLOG_DEBUG);
    if($wordsMinLen==9999) {
        $wordsMinLen=$iniParams["wordsMinLen"];
    }
    $excludeWords=$iniParams["excludeWords"];
    
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

function recupTmdbResults($filename) {
    global $iniParams;
    llog("recupTmdbResults:",LLOG_DEBUG);
    if(getGlobalLogLevel()==LLOG_DEBUG) {
        var_dump($filename);
    }
    
    // Récupération des mot cles
    $keyWords=keyWords($filename);
    
    $annee=extraitAnnee($filename);
    
    while(count($keyWords)>0) {
        //Chaine du parametre query de l'API
        $keyWordsStr=implode("+",$keyWords);
        //l'URL
        $elemUrl= [
            $iniParams["tmdbBaseUrl"], 
            "search/movie?api_key=" , 
            $iniParams["apiKey"], 
            "&query=".$keyWordsStr, 
            "&language=",
            $iniParams["language"]
            ];
        if($annee) {
            $elemUrl[] = "&year=".$annee;
        }
        $url = implode($elemUrl);
        llog("URL:".$url,LLOG_DEBUG);
        
        // Requeter
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); //timeout in seconds
        $response = curl_exec($ch);
        curl_close($ch);
        llog("response:".$response,LOG_DEBUG);
        $results = json_decode($response, true);
        
        llog("results:".json_encode($results,JSON_PRETTY_PRINT),LLOG_DEBUG);
        
        if(array_key_exists("total_results",$results) &&  $results["total_results"]==0) {
            array_pop($keyWords);       
        } else {
            break;
        }
    }

    if(count($keyWords)==0) {
        return false;
    }
    
    return $results["results"];
}

function in_arrayi($needle, $haystack) {
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

function allValuesInArray($values,$tab) {
    llog("allValuesInArray:",LLOG_DEBUG);
    if(getGlobalLogLevel()==2) {
        llog("values:",LLOG_DEBUG); var_dump($values);
        llog("tab:",LLOG_DEBUG); var_dump($tab);
    }
    foreach($values as $val) {
        if(!in_arrayi($val,$tab)) {
            return false;
        }
    }
    return true;
}

function allValuesOrderedInArray($values,$tab) {
    llog("allValuesInArray:",LLOG_DEBUG);
    if(getGlobalLogLevel()==2) {
        llog("values:",LLOG_DEBUG); var_dump($values);
        llog("tab:",LLOG_DEBUG); var_dump($tab);
    }
    $keyCur=-1;
    foreach($values as $val) {
        if(!in_arrayi($val,$tab)) {
            return false;
        } else {
            $key = array_search(strtolower($val), array_map('strtolower', $tab));
            if($key<$keyCur) {
               return false;
            } else {
               $keyCur=$key;
            }           
        }
    }
    return true;
}

function isYear($annee){
    //Année de 1901 à 2155
    $regex = "/^19[0-9]{1}[1-9]{1}|20[0-9]{2}|21[0-4][0-9]|215[0-5]$/";
    return preg_match($regex,$annee);
}

function extraitAnnee($filename) {
    //recherche la présence d'un année dans le titre.
    $words=keyWords($filename);
    
    foreach($words as $word) {
        if(isYear($word)) {
            return $word;
        }
    }
    
    return false;
}

function choisiTmdbResult($filename,$allResults){
    global $iniParams;
    // Choisit un resultats tmdb parmi tous
    llog("choisitTmdbResult:",LLOG_DEBUG);
    if(getGlobalLogLevel()==2) {
        var_dump($filename);
        //var_dump($allResults);
    }

    // Récupération des mot cles
    $keyWords=keyWords($filename,LLOG_INFO);
    
    //recherche du meilleur
    $trouve=false;
    $indice=0;
    while($indice<count($allResults)) {
        if($allResults[$indice]) {
            foreach(["title","original_title"] as $nomChamp) {                
                if($allResults[$indice][$nomChamp]) {
                    $title=$allResults[$indice][$nomChamp];
                    if(allValuesOrderedInArray(keywords($title,LLOG_INFO),$keyWords)) {
                        $trouve=true;
                        break;
                    }
                }
            }
        }
        if($trouve) {break;}
        $indice++;
    }
    
    if($trouve) {
        llog("Trouvé, indice ". $indice,LLOG_DEBUG);
        return $allResults[$indice];
    } 
    
    return false;
}

function videoRenameFile($dirname,$filename) {
    // retourne le triplet :
    // dir/name/newName soit repertoire/fichier/nouveau nom 
    // le fichier doit exister
    global $iniParams;
    llog("videoRenameFile : " . $dirname . "-".$filename,LLOG_DEBUG);
    
    // commence par nettoyer par expression reguliere
    $excludeRegex=$iniParams["exludeRegex"];
    $regexName=$filename;
    foreach($excludeRegex as $regex) {
        $regexName=preg_replace("$regex","",$regexName);        
    }
    llog("filename nettoye regex :".$regexName,LLOG_DEBUG);
    
    $tmdbAllDatas=recupTmdbResults($regexName);
    if($tmdbAllDatas===false || count($tmdbAllDatas)==0) {
        return false;
    }
    
    $tmdbDatas=choisiTmdbResult($regexName,$tmdbAllDatas);
    
    if($tmdbDatas!==false) {
        $annee=$tmdbDatas["release_date"]?substr($tmdbDatas["release_date"],0,4):"1900";
        $newName[]=$tmdbDatas["title"];
        $newName[]=$annee;
        $newName[]=pathinfo($regexName)["extension"];
    }
    
    $fileRenamed = [
        "dir" => $dirname,
        "name" => $filename,
        "newName" => $tmdbDatas!==false?implode(".",$newName):$regexName,
    ];
    
    return $fileRenamed;
}

function videoRenameDir($dirname,$recurs,$go) {
    // retourne l'array contenant les triplets :
    // repertoire / fichier / fichier renommé 
    llog("videoRenameDir:" . $dirname,LLOG_DEBUG);
    $d = dir($dirname);
    $filesRenamed=[];
    $fini=false;
    while ( (false !== ($entry = $d->read())) && !$fini) {
        if($entry!="." && $entry!="..") {
            if(is_dir($dirname .$entry) && $recurs) {
                //llog("TAITEMENT REP : ".$dirname . $entry);
                $filesRenamed=array_merge($filesRenamed,videoRenameDir($dirname . $entry . "/",$recurs,$go));
            } elseif (is_file($dirname .$entry)) {
                //llog("TAITEMENT FILE : ".$dirname . $entry);
                $newDatas=videoRenameFile($dirname,$entry);
                if($newDatas!==false) {
                    $filesRenamed[]=$newDatas;
                    llog(json_encode($newDatas,JSON_PRETTY_PRINT),LLOG_INFO);
                }
            }
        }
    }
    $d->close();
    
    if($go) {
        llog("Renomage effectif en cours ...",LLOG_INFO);
        foreach($filesRenamed as $file) {
            $oldName=$file['dir'].$file['name'];
            $newName=$file['dir'].$file['newName'];
            if($oldName!=$newName && is_file($oldName)) {
                llog("Renomage : " . $newName,LLOG_INFO);
                rename($oldName,$newName);
            }
        }
    } else {
        llog("Renomage desactivé, utilisez l'option -g");
    }
    return $filesRenamed;
}

function fRealPath($dirname) {
    // retourne le chemin absolu correspondant.
    llog("fRealPath : " . $dirname,LLOG_DEBUG);
    $rpath="";
    if(substr($dirname, 0, 1)=="~") {
        $rpath=getenv("HOME").substr($dirname, 1);        
    } else {
        $rpath=$dirname;
    }
    return realpath($rpath);
}

// Main
$cdeLineParams=init();
$iniParams=getIniParams();

// Traitement Principal
if(key_exists("d", $cdeLineParams) && $cdeLineParams["d"]) {
    $filesRenamedTab=[];
    
    $dirname=fRealPath($cdeLineParams["d"]);
    if(is_dir($dirname)) {
        llog("Traitement repertoire : " . $dirname,LLOG_DEBUG);
        $dirname=$dirname.(substr($dirname,-1)=="/"?"":"/");
        $recurs=key_exists("R",$cdeLineParams);
        $filesRenamedTab=videoRenameDir($dirname,$recurs,key_exists("g", $cdeLineParams));
    } elseif (is_file($dirname)) {
        llog("Traitement fichier : " . $dirname,LLOG_DEBUG);
        $filesRenamedTab[$dirname]=videoRenameFile($dirname);
    } else {
        llog("'".$dirname . "' n'est pas un dossier");
        exit(2);
    }
    llog("Tableau des fichier renommés :",LLOG_DEBUG);
    llog(json_encode($filesRenamedTab,JSON_PRETTY_PRINT),LLOG_INFO);
} else {
    llog("Vous devez préciser un dossier (option -d)");
    exit(1);
}

exit(0);