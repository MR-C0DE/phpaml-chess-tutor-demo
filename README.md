# PHPAML Application Template

Modèle officiel utilisé par :

```bash
aml create mon-projet
```

Le moteur et les dépendances sont installés dans `runtime/`. La racine contient
uniquement les fichiers utiles au développement de l’application.

Le serveur web doit utiliser `public/` comme racine documentaire. Ne publiez
jamais la racine complète du projet. Copiez `.env.example` vers `.env`, gardez
`APP_DEBUG=false` en production et servez exclusivement le site en HTTPS.
