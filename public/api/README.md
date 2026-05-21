Les endpoints REST sont servis par le front-controller `public/index.php` via les routes `/api/v1/*`.

Exemple : `GET /api/v1/projects`.

Messages live Firebase :
- `POST /api/v1/messages` : cree un message archive en MySQL et tente une synchro Firestore.
- `GET /api/v1/messages` : renvoie un flux admin fusionne archive MySQL + live Firestore.
- `GET /api/v1/messages/{id}` : renvoie un message fusionne archive + live quand disponible.
- `PUT /api/v1/messages/{id}/read` : marque le message comme lu localement et le resynchronise.
- `DELETE /api/v1/messages/{id}` : supprime le message localement et cote Firestore.

Les endpoints admin `GET/PUT/DELETE` acceptent une session admin classique ou un header `Authorization: Bearer <Firebase idToken>` si Firebase est active.
