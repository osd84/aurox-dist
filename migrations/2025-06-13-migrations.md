# Aurox

Remplacer : 

```
created_at = :created_at,
updated_at = :updated_at,
created_by = :created_by,
updated_by = :updated_by,
```

Migrer les getRules dans le nouveau format

Exemple :

```php
$rules = [
    'email' => ['type' => 'mail'],
    'username' => ['type' => 'string', 'minLength' => 3, 'maxLength' => 255, 'required' => true],
];
```