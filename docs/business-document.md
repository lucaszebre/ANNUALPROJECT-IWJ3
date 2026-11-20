---
title: "CodeCampus : plateforme d'apprentissage du code"
subtitle: "Document métier · Projet annuel 3IWJ 2025-2026 · Lucas, Sorya, Yohan"
lang: fr
# PDF: docker run --rm -v "$PWD/docs:/data" pandoc/typst business-document.md --pdf-engine=typst -o business-document.pdf
papersize: a4
fontsize: 10pt
margin:
  x: 1.8cm
  y: 1.5cm
---

## Contexte

Les écoles, les bootcamps et les formateurs indépendants enseignent de plus en plus le code à distance ou en hybride. Le cours en ligne type est une suite de vidéos, complétée par des exercices à rendre et un espace de questions. Pour cela, l'équipe pédagogique assemble plusieurs outils : une plateforme vidéo, un éditeur de code en ligne, un LMS pour les rendus et les notes, une messagerie pour les questions. Les données des étudiants se retrouvent réparties chez plusieurs prestataires, souvent payants.

## Problème métier

- **La vidéo est passive.** On ne peut pas retrouver un passage précis d'un cours d'une heure, et une question posée sur le forum n'est pas reliée au moment de la vidéo qui l'a fait naître.
- **La correction est lente.** Les exercices de code sont corrigés à la main : l'étudiant attend son retour des jours, alors qu'il a besoin de savoir tout de suite si son code fonctionne.
- **L'enseignant ne voit pas où ses étudiants bloquent.** Il ne sait ni à quelle minute ils décrochent d'une vidéo, ni quel exercice fait échouer la majorité de la promotion. Il le découvre trop tard, à l'examen.

## Solution proposée

Une plateforme SaaS, auto-hébergeable, qui réunit dans un même parcours la vidéo, le cours écrit et les exercices de code, autour de trois piliers :

1. **La vidéo est indexée.** Chaque vidéo est transcrite automatiquement. L'étudiant cherche un mot dans les transcriptions et arrive à la seconde où il est prononcé. Il commente à un timecode précis, et l'enseignant répond au bon endroit.
2. **Les exercices sont exécutés.** Le code de l'étudiant tourne dans un conteneur isolé et est corrigé automatiquement par les tests écrits par l'enseignant. Le résultat arrive en quelques secondes.
3. **L'enseignant voit où ça bloque.** Un tableau de bord montre la courbe de décrochage de chaque vidéo et le taux d'échec de chaque exercice.

Autour de ces piliers : catalogue de cours par catégories, leçons combinant vidéo, texte, exercice et quiz, parcours avec prérequis entre leçons, suivi de progression, commentaires modérés. Chaque organisation (école, centre de formation) dispose d'un espace isolé avec ses propres quotas. L'interface est responsive, sur ordinateur et sur mobile.

## Acteurs

| Acteur | Rôle |
|:-----------------|:--------------------------------------------------------------------------------|
| **Étudiant** | Suit un parcours de leçons, regarde les vidéos, cherche dans les transcriptions, commente au timecode, soumet ses exercices et suit sa progression. |
| **Enseignant** | Crée ses cours, leçons, vidéos, exercices et tests, définit les prérequis, répond aux commentaires et consulte les statistiques de ses cours. |
| **Administrateur** | Gère les utilisateurs, les cours, la modération des commentaires et les quotas de son organisation. |
| **Organisation** | École ou centre de formation cliente : ses utilisateurs, ses cours et ses données sont isolés de ceux des autres organisations. |

## Règles métier

- Il faut être connecté pour commenter, soumettre du code ou accéder à un cours privé.
- Un cours ne peut être publié que s'il contient au moins une leçon.
- Une vidéo n'est visible qu'une fois son transcodage terminé.
- Une leçon reste verrouillée tant que ses prérequis ne sont pas validés ; les prérequis ne forment jamais de cycle.
- Le code soumis ne s'exécute que dans un bac à sable sans réseau, limité en CPU, en mémoire et en temps.
- Un enseignant ne modifie que ses propres cours, sauf l'administrateur.
- Les données d'une organisation ne sont jamais accessibles depuis une autre ; chaque organisation a des quotas de stockage vidéo et d'exécutions de code.

## Choix techniques et coût

Laravel (PHP) avec Blade et Livewire, PostgreSQL pour les données, la file de jobs et la recherche plein texte, Garage (stockage S3) pour les vidéos, ffmpeg pour le streaming HLS, Whisper pour la transcription, Docker pour chaque service et Caddy pour le HTTPS. Transcodage, transcription et exécution de code sont trois types de jobs sur **une seule file d'attente** : une architecture, trois fonctionnalités. Tout est auto-hébergé sur un VPS à 5-10 € par mois, plus un nom de domaine, sans service payant.

## Périmètre et risques

- **MVP (29/11/2026)** : comptes et rôles, cours et leçons, pipeline vidéo complet, commentaires, progression, déploiement en HTTPS.
- **V1 (13/12/2026)** : exercices exécutés et corrigés, transcription et recherche, commentaires au timecode, prérequis, quiz, statistiques enseignant, multi-organisation.
- **Risques** : Whisper tourne sur CPU, donc lentement ; la file d'attente rend la transcription asynchrone et son temps est mesuré. Un conteneur partage le noyau de l'hôte ; le bac à sable est durci (utilisateur non root, système de fichiers en lecture seule, aucun réseau, limites de ressources et de processus). Si le temps manque, les axes secondaires sont réduits sans toucher au cœur : pipeline vidéo et exécution de code.
