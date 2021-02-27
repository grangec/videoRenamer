# videoRenamer
Renomme des fichiers vidéo selon tmdb pour intégration dans Kodi.
-----------------------------------------------------------------

Exemple d'utilisation :

```
$ chmod +x ./videoRename.php
$ ./videoRename.php -d~/Src/videoRenamer/videoTest/ -v
[
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.Cpasbien.pw ] Vice.2015.FRENCH.BRRip.XviD.AC3-DesTroY.avi",
        "newName": "www Cpasbien Vice 2015 FRENCH BRRip XviD AC3 DesTroY avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.OMGTORRENT.com ] La.French.2014.FRENCH.SUBFORCED.BRRip.XviD-VENUM.avi",
        "newName": "www OMGTORRENT com French 2014 FRENCH SUBFORCED BRRip XviD VENUM avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.OMGTORRENT.com ] Black.Sea.2014.FRENCH.BDRip.XviD-GLUPS.avi",
        "newName": "www OMGTORRENT com Black Sea 2014 FRENCH BDRip XviD GLUPS avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "x-men.apocalypse.2016.french.720p.bluray.x264-pinkpanters.mkv",
        "newName": "men apocalypse 2016 french 720p bluray x264 pinkpanters mkv"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.CpasBien.pw ] X-Men.Days.of.Future.Past.2014.ROGUE.CUT.FRENCH.BDRiP.XviD-ZT.avi",
        "newName": "www CpasBien Men Days Future Past 2014 ROGUE CUT FRENCH BDRiP XviD avi"
    },
    {
        "dir": "\/home\/cyrille\/Src\/videoRenamer\/videoTest\/",
        "name": "[ www.OMGTORRENT.com ] Hackers.2015.FRENCH.WEBRip.MD.XViD-KR4K3N.avi",
        "newName": "www OMGTORRENT com Hackers 2015 FRENCH WEBRip XViD KR4K3N avi"
    }
]
```
