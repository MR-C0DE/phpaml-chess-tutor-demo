# Changelog

## 0.4.0-beta.5 — 2026-08-19

- retire le dossier `database/` de la racine du starter ;
- les migrations et seeders sont désormais créés à la demande sous
  `runtime/database/` par PHPAML Data.

## 0.4.0-beta.4 — 2026-08-18

- Framework `0.2.1-beta.1` requis pour le pipeline AML View ;
- correction de l’initialisation sans vues MVC et du rendu déclaratif via le
  pipeline HTTP principal ;
- suppression des avertissements `views_path` et de la fausse réponse 404 à
  l’ouverture d’un nouveau projet AML View.

## 0.4.0-beta.3 — 2026-08-17

- nouvelle page d’accueil moderne inspirée des grandes plateformes web ;
- anglais par défaut et français secondaire avec préférence mémorisée ;
- nouveau logo PHPAML transparent sans symbole frontal ;
- serveur local présenté sur `127.0.0.1:8910` ;
- interface responsive simplifiée et tests de rendu actualisés.

## 0.4.0-beta.2 — 2026-08-14

- structure `runtime/` et manifeste `phpaml.json` ;
- page de démarrage PHPAML et actualisation automatique.
