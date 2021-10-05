# ingamepost_search_profile
Fügt einen Link im Profil hinzu, mit dem die Ingameposts des Users gesucht/angezeigt werden

Variable fürs member_profile template:    
{$ingamesearchlink}   
    
Bei bedarf kann auch einfach der Link selber ins Profile geschrieben und entsprechend angepasst werden:   
HTML CODE:  
```<a href="{$mybb->settings['bburl']}/misc.php?action=findingameposts&uid={$uid}">Suche</a>;```
