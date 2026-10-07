# Aurox

## Sécuriser la Dépendance PHPmailer

```
composer remove phpmailer/phpmailer
```

Si besoin

```
composer install osd84/phpmailer  
```


## Changement comportement de Sec::getAction()

Sec::getAction() n'ajoute plus "Post" ou "Json" en fin d'action détectée.  <br>
Par défaut, la méthode cherche maintenant dans GET + POST (source=3) au lieu de GET uniquement. <br>
Il est recommandé de vérifier tous les usages existants de cette fonction et de les modifier en conséquence.
