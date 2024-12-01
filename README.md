## Installation

Utilisez Docker pour lancer le projet facilement.

Se positionner dans le dossier **.docker** du projet, puis executer cette commande :

```bash
docker compose up -d --build 
```

### Services disponibles avec Docker.

- Projet backend-api - back-office admin  (symfony) : [localhost:81](http://localhost:81/)
- Projet front (react) : [localhost:3001](http://localhost:3001/)

- Interface PhpMyadmin : [localhost:8089](http://localhost:8089/)
- Interface mailcatcher (serveur d'emails en local) : [localhost:1080](http://localhost:1080/)

Vous pouvez changer les ports en modifiant dans le fichier **.docker/docker-compose.yml**.
