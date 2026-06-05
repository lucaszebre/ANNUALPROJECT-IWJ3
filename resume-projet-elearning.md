# Résumé du projet - Plateforme e-learning SaaS

## Concept

Le projet consiste à créer une plateforme SaaS d'e-learning permettant aux étudiants d'apprendre avec des cours structurés combinant vidéos, contenus écrits et commentaires.

## Problème métier

Les ressources d'apprentissage sont souvent dispersées entre plusieurs outils. Les étudiants manquent d'un espace centralisé pour suivre les cours, poser des questions et visualiser leur progression.

## Solution proposée

La plateforme centralise les cours en ligne. Les enseignants peuvent créer des cours et des leçons, ajouter des vidéos et du texte, puis répondre aux commentaires des étudiants. Les étudiants peuvent suivre les leçons, commenter et suivre leur progression.

## Acteurs

- Étudiant : consulte les cours, suit les leçons, commente et suit sa progression.
- Enseignant : crée et gère les cours, les vidéos, les contenus écrits et les réponses.
- Administrateur : gère les utilisateurs, les cours et la modération des commentaires.

## Fonctionnalités principales

- Authentification et gestion des rôles.
- Catalogue de cours avec catégories.
- Leçons avec vidéo et contenu texte.
- Commentaires liés aux leçons.
- Réponses des enseignants.
- Suivi de progression des étudiants.
- Interface responsive desktop et mobile.

## Règles importantes

- Un utilisateur doit être connecté pour commenter ou accéder aux contenus privés.
- Un cours doit contenir au moins une leçon avant publication.
- Une leçon peut contenir une vidéo, du texte, ou les deux.
- Les commentaires doivent respecter des règles de modération.
- Les enseignants ne peuvent modifier que leurs propres cours, sauf administrateur.

## Contraintes du syllabus

- Gestion du projet en Agile avec un outil de suivi.
- Utilisation de Git et d'un workflow de branches.
- Maquettes avec Figma, Penpot ou équivalent.
- Modèle de données SQL à justifier.
- Interface responsive sans React, Vue ou Angular.
- Backend/API avec logique métier.
- Conteneurisation avec Docker et compose.yml.
- Déploiement public avec nom de domaine et certificat SSL.
- Sécurité contre CSRF, injection SQL, XSS et exposition des secrets.
