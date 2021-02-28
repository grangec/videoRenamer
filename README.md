# videoRenamer
Renomme des fichiers vidéo selon tmdb pour intégration dans Kodi.
-----------------------------------------------------------------
Prérequis :
- copier le videoRenamer.php.ini.dist en videoRenamer.php.ini et renseigner a minima la cle API.
- paquets : php-curl

Exemple d'utilisation :

```
$ chmod +x ./videoRename.php
$ ./videoRename.php -d~/Src/videoRenamer/videoTest/ -v
2021-02-28 12:14:45 : [
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.Cpasbien.pw ] Vice.2015.FRENCH.BRRip.XviD.AC3-DesTroY.avi",
        "newName": "Vice.2015.avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.OMGTORRENT.com ] La.French.2014.FRENCH.SUBFORCED.BRRip.XviD-VENUM.avi",
        "newName": "La French.2014.avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.OMGTORRENT.com ] Black.Sea.2014.FRENCH.BDRip.XviD-GLUPS.avi",
        "newName": "Black sea.2014.avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "x-men.apocalypse.2016.french.720p.bluray.x264-pinkpanters.mkv",
        "newName": "X\u2010Men\u00a0: Apocalypse.2016.mkv"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.CpasBien.pw ] X-Men.Days.of.Future.Past.2014.ROGUE.CUT.FRENCH.BDRiP.XviD-ZT.avi",
        "newName": "X\u2010Men : Days of Future Past.2014.avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.OMGTORRENT.com ] Hackers.2015.FRENCH.WEBRip.MD.XViD-KR4K3N.avi",
        "newName": "Hacker's Game.2015.avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.OMGTORRENT.com ] Black.Sea.FRENCH.BDRip.XviD-GLUPS.avi",
        "newName": "Black sea.2014.avi"
    }
]
```
