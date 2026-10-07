# Aurox

## Ajouter champ obligatoire dans Tables

Créer dans chaque table : 

```sql
created_at    datetime    default CURRENT_TIMESTAMP null,
updated_at    datetime    default CURRENT_TIMESTAMP null on update CURRENT_TIMESTAMP,
created_by    int         null,
updated_by    int         null,
doli_id       int         null, // Si intégration avec Dolibarr
```

## Ajouter les Routes ajax & cron

Ajouter dans public/

```
ajax/
ajax/index.php

cron/
cron/index.php
```