# Aurox

## Migrer Csrf::protect();

Maintenant `Csrf::protect();` est en mode SESSION par défaut. <br>
Pour les opérations sensibles chercher les `Csrf::protect();` et remplacer par `Csrf::protect(forcePerRequest: true)`;

Exemple : Login

```php
// avant
Csrf::protect(); 
// a mettre
Csrf::protect(forcePerRequest: true)
```