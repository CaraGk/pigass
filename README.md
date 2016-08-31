PIGASS
======

PIGASS est un acronyme qui signifie « Projet d'Interface de Gestion des Adhérents de Structure Syndicale ». Il s'agit d'une application web développée à partir du projet GESSEH pour l'intersyndicale RéAGJIR.

Pré-requis
----------

Section à compléter

En vrac :
- php >= 5.6
- Extensions PHP : php-intl, php-curl, php-mbstring, php-xml, php-mysql
- MySQL (par défaut)

Installation
------------
Procédure d'installation en console sur un serveur GNU/Linux :

1. git clone https://github.com/CaraGk/pigass.git pigass/
2. cd pigass/
3. ./composer.phar selfupdate ; ./composer.phar install
4. Corriger les éventuelles erreurs de dépendances et indiquer les paramètres pour la génération du parameters.yml
5. ./bin/console doctrine:migrations:migrate --no-interaction
6. ./bin/console fos:user:create user@exemple.fr user@exemple.fr MonMotDePasse
7. ./bin/console fos:user:promote user@exemple.fr ROLE_ADMIN

À partir de là on peut accéder au site et se connecter avec l'utilisateur user@exemple.fr pour créer les structures.
