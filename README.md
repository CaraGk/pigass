PIGASS
======

PIGASS est un acronyme qui signifie « Projet d'Interface de Gestion des Adhérents de Structure Syndicale ». Il s'agit d'une application web développée à partir du projet GESSEH pour l'intersyndicale RéAGJIR.

Pré-requis
----------

- php >= 7.2 pour la branche 2.x
- Extensions PHP : php-intl, php-curl, php-mbstring, php-xml, php-mysql, php-zip, php-gd, php-dom, php-xml
- MySQL (par défaut)
- NodeJS
- Composer

Installation
------------
Procédure d'installation (en production) en console sur un serveur GNU/Linux :

1. git clone https://github.com/CaraGk/pigass.git pigass/
2. cd pigass/
3. Indiquer les variables d'environnement de .env.dist dans la configuration de votre VirtualHost (si Apache2)
4. composer install --no-dev --optimize-autoloader
5. Corriger les éventuelles erreurs de dépendances
6. ./bin/console doctrine:migrations:migrate --no-interaction
7. ./bin/console fos:user:create user@exemple.fr user@exemple.fr MonMotDePasse
8. ./bin/console fos:user:promote user@exemple.fr ROLE_ADMIN
9. ./bin/console assets:install web
10. yarn install
11. yarn encore production

À partir de là on peut accéder au site et se connecter avec l'utilisateur user@exemple.fr pour créer les structures.

Problèmes fréquents
-------------------
Si sur un formulaire, vous obtenez l'erreur « There is no suitable CSPRNG installed on your system », c'est vraisemenbablement que votre système utilise PHP5. Il existe 3 possibilités pour se débarrasser de ce problème :
- Ajouter paragonie/random_compat au composer.json (./composer.phar require paragonie/random_compat ~1.4 ; ./composer.phar install) ;
- Si vous utilisez open_basedir, ajoutez /dev/urandom à la liste des répertoires autorisés ;
- Passer à la version 7.0 de PHP.

Si à l'identification de l'utilisateur, rien ne se passe et que les logs indiquent un « Populated the TokenStorage with an anonymous Token. » : 
- Ajouter à la configuration d'Apache : FcgidPassHeader     Authorization
