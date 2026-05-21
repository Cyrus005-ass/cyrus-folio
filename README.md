# Portfolio OS

## Brief general
Portfolio OS est un portfolio PHP/MySQL administrable, pense pour tourner facilement sous WAMP et pour piloter un site public, un back-office admin, une petite API REST et un chatbot.

La version actuelle a ete adaptee vers une direction visuelle dark premium inspiree de l'ambiance Craftivo, sans recopier le template. Le site public, le dashboard admin et la page de connexion admin utilisent maintenant une meme base graphique : fond sombre, texte blanc casse, accent rouge/corail, cartes arrondies, transitions sobres et responsive mobile.

Ce README documente la structure du projet, le role des fichiers et dossiers utiles, le fonctionnement interne, les adaptations deja faites et la bonne facon d'etendre le code sans casser l'existant.

## Ce qui a ete realise sur ce projet
- Refonte visuelle globale du site public vers un rendu premium dark.
- Synchronisation du theme entre le site public, le dashboard admin et la page de connexion admin.
- Enrichissement de la page d'accueil sans en faire une page qui duplique tout le contenu des autres pages.
- Conservation de la video de presentation uniquement sur la page `A propos`.
- Ajout et affichage des reseaux sociaux en icones.
- Ajout du logo video `C-Y` a partir de `public/assets/uploads/C-y.mp4`.
- Correction des validations de liens pour GitHub, LinkedIn, Instagram, Facebook et WhatsApp dans le profil admin.
- Correction de l'acceptation des chemins d'assets locaux du type `assets/uploads/...` pour avatar, CV et video.
- Amelioration importante du chatbot avec fallback local intelligent, historique court de conversation, logs et diagnostics admin.
- Correction du hero mobile pour que la photo reste visible sans voile noir parasite.
- Refonte complete de la page de connexion admin.
- Nettoyage de l'espace de travail en supprimant les artefacts temporaires et le dossier de reference inutile au runtime.

## Stack technique
- PHP 8+ en mode procedural/MVC leger.
- MySQL ou MariaDB.
- Composer pour l'autoload des dependances.
- PHPMailer 6.9 pour les notifications email.
- HTML/CSS/JS vanilla, sans Bootstrap pour le layout.
- Bootstrap Icons uniquement pour les icones.
- Compatible WAMP/XAMPP en local.

## Demarrage local
### Prerequis
- PHP 8+
- MySQL ou MariaDB
- WAMP ou equivalent
- Composer
- Node.js optionnel pour regenerer les assets minifies

### Installation rapide
1. Placer le projet dans `c:\wamp64\www\portfolio-os`.
2. Importer `database/portfolio_os.sql` dans la base `portfolio_os`.
3. Copier `.env.example` vers `.env` pour une installation simple, ou creer un `.env.local` si ton `.env` contient deja la configuration de production.
4. Lancer `composer install` si le dossier `vendor/` n'est pas deja present.
5. Lancer `npm install` puis `npm run build:assets` si tu veux regenerer les assets minifies.
6. En local WAMP, ouvrir `http://localhost/portfolio-os`.
7. En local WAMP, ouvrir `http://localhost/portfolio-os/admin/login` pour l'administration.
8. En production, ouvrir `https://cyrus-youp.unaux.com`.
### Premier administrateur
Le projet peut creer automatiquement un premier compte admin si la table `users` est vide.

Pour cela :
- mettre `ADMIN_BOOTSTRAP_ENABLED=true` dans `.env`
- renseigner `ADMIN_BOOTSTRAP_NAME`, `ADMIN_BOOTSTRAP_EMAIL`, `ADMIN_BOOTSTRAP_PASSWORD`
- charger une page du site
- remettre ensuite `ADMIN_BOOTSTRAP_ENABLED=false`

## URLs utiles
- Site public : `https://cyrus-youp.unaux.com`
- A propos : `https://cyrus-youp.unaux.com/about`
- Contact : `https://cyrus-youp.unaux.com/contact`
- Login admin : `https://cyrus-youp.unaux.com/admin/login`
- Dashboard admin : `https://cyrus-youp.unaux.com/admin`
- API chatbot : `https://cyrus-youp.unaux.com/api/v1/chatbot/message`

## Variables d'environnement importantes
### Application
- `APP_NAME` : nom public du site.
- `APP_URL` : URL de base du projet. Tres important pour les liens et assets.
- `APP_ENV` : `local` ou `production`.
- `APP_DEBUG` : active l'affichage des erreurs PHP en local.
- `ASSET_MINIFY` : charge les fichiers `.min.css` et `.min.js` quand ils existent.
- `.env.local` : surcharge optionnelle chargee uniquement en runtime local/CLI pour garder un `.env` de prod intact.
- `APP_AUTHOR`, `APP_DESCRIPTION`, `APP_KEYWORDS`, `APP_ROBOTS`, `APP_OG_IMAGE`, `APP_TWITTER_HANDLE`, `APP_THEME_COLOR` : meta SEO et partage.
### Base de donnees
- `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` : connexion MySQL.

### Firebase
- `FIREBASE_ENABLED` : active l'integration Firebase cote backend.
- `FIREBASE_PROJECT_ID` : identifiant du projet Firebase.
- `FIREBASE_CREDENTIALS` : chemin du fichier JSON de compte de service, recommande dans `storage/secure/firebase-service-account.json`.
- `GOOGLE_APPLICATION_CREDENTIALS` et `GOOGLE_CLOUD_PROJECT` sont aussi acceptes pour coller a la configuration officielle Google/Firebase.
- `FIREBASE_STORAGE_BUCKET` : bucket Firebase Storage si tu utilises le stockage.
- `FIREBASE_REQUIRE_VERIFIED_EMAIL` : refuse les connexions Firebase avec email non verifie.
- `FIREBASE_AUTO_PROVISION` : cree automatiquement un utilisateur local si l'email Firebase n'existe pas encore.
- `FIREBASE_AUTO_PROVISION_ROLE` : role local a attribuer lors de l'auto-provisioning.
- `FIREBASE_TIMEOUT`, `FIREBASE_CA_BUNDLE` : options reseau/SSL utiles surtout sous Windows.
- `FIREBASE_MESSAGES_SYNC` : active la synchro du module messages/contact vers Firestore.
- `FIREBASE_MESSAGES_COLLECTION` : nom de la collection Firestore pour les messages live.

### Bootstrap admin
- `ADMIN_BOOTSTRAP_ENABLED` : creation automatique du premier admin.
- `ADMIN_BOOTSTRAP_NAME`, `ADMIN_BOOTSTRAP_EMAIL`, `ADMIN_BOOTSTRAP_PASSWORD` : identifiants de bootstrap.

### Mail
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION` : SMTP.
- `MAIL_FROM`, `MAIL_FROM_NAME` : expediteur.
- `MAIL_TO`, `MAIL_TO_NAME` : destinataire des notifications de contact.
- `MAIL_REPLY_TO`, `MAIL_TIMEOUT`, `MAIL_VERIFY_PEER` : options avancees.

### Dashboard
- `DASHBOARD_UNREAD_ALERT_DAYS` : seuil pour signaler les messages non lus anciens.
- `POST_VIEW_NOTIFICATION_THRESHOLDS` : paliers de vues du blog pour creer des notifications.

### Groq / chatbot
- `GROQ_API_URL` : endpoint Groq compatible OpenAI.
- `GROQ_MODEL` : modele utilise pour le chatbot.
- `GROQ_TIMEOUT` : timeout HTTP.
- `GROQ_API_KEY` : cle API Groq.
- `GROQ_CA_BUNDLE` : chemin optionnel vers un bundle CA si besoin SSL sur Windows.
## Comment l'application fonctionne
### Flux principal d'une requete web
1. `index.php` a la racine redirige vers `public/index.php`.
2. `.htaccess` force aussi les requetes vers le dossier `public/`.
3. `public/index.php` charge `app/Core/Bootstrap.php`.
4. `Bootstrap.php` :
   - demarre la session
   - charge `.env`
   - charge `.env.local` si l'execution se fait en local ou en CLI
   - charge Composer si present
   - enregistre l'autoload `App\`
   - charge tous les helpers
   - lance `SchemaService::ensureLatest()`
   - essaie `AuthService::ensureDefaultAdmin()`
   - tente `AuthService::restoreRememberedUser()`
5. `config/routes.php` construit le routeur.
6. `App\Core\Router` fait correspondre l'URL a un controleur.
7. Le controleur appelle models, services et helpers.
8. La vue est rendue via `view()` puis encapsulee dans un layout.
9. Le layout charge le CSS, le JS, le theme actif et les composants communs.

## Firebase
- Le backend inclut maintenant `app/services/FirebaseService.php` pour verifier un `idToken` Firebase, obtenir un token Google OAuth de service account et preparer les appels Firestore/Storage en REST.
- Le login API `POST /api/v1/auth/login` accepte maintenant `firebase_id_token`, `firebaseIdToken`, `idToken` ou un header `Authorization: Bearer <idToken>` quand Firebase est active.
- Le backend ne stocke pas la cle JSON dans le repo : place le fichier du compte de service dans `storage/secure/firebase-service-account.json` puis renseigne `FIREBASE_CREDENTIALS`.
- Le mapping d'authentification reste volontairement prudent : par defaut, un utilisateur Firebase doit correspondre a un utilisateur local via son email.
- Si tu actives `FIREBASE_AUTO_PROVISION=true`, le backend peut creer le compte local a la premiere connexion Firebase.
- `app/services/MessageService.php` synchronise maintenant les messages du formulaire de contact vers Firestore quand `FIREBASE_MESSAGES_SYNC=true`.
- `POST /api/v1/messages` archive en MySQL puis pousse le message vers Firestore si Firebase est active.
- `GET /api/v1/messages` et `GET /api/v1/messages/{id}` renvoient maintenant un flux fusionne archive MySQL + live Firestore pour les admins connectes.
- Les endpoints admin `GET /api/v1/messages*`, `PUT /api/v1/messages/{id}/read` et `DELETE /api/v1/messages/{id}` acceptent aussi un header `Authorization: Bearer <Firebase idToken>`.
- La page admin `/admin/messages` se resynchronise maintenant automatiquement via polling AJAX sur ce flux fusionne.

### Exemple : connexion admin
- `GET /admin/login` -> `AuthController::loginForm()` -> `resources/views/auth/login.php` avec `resources/layouts/auth.php`.
- `POST /admin/login` -> `AuthController::login()` -> `AuthService::login()`.
- `AuthService` verifie l'email, le hash du mot de passe, stocke la session et cree un cookie remember me si demande.
- La page admin devient ensuite accessible via `requireAdmin()` dans les controleurs.

### Exemple : theme admin -> site public
- L'admin modifie son theme dans `/admin/theme`.
- `ThemeController::save()` enregistre la ligne active en base.
- `ThemeService::activeTheme()` lit le theme actif.
- `ThemeService::cssVariables()` injecte les variables CSS dans `resources/layouts/public.php`, `resources/layouts/admin.php` et `resources/layouts/auth.php`.
- Le site public, l'admin et la page de login partagent donc les memes couleurs et polices.

### Exemple : formulaire de contact
- `resources/views/public/contact.php` poste vers `/contact`.
- `ContactController::store()` valide les champs, enregistre le message, cree une notification admin et tente l'envoi email.
- Les messages sont visibles dans `/admin/messages`.

### Exemple : chatbot
- `resources/components/chatbot-widget.php` affiche le widget.
- `public/assets/js/chatbot.js` capture le message et appelle `/api/v1/chatbot/message`.
- `ChatbotController::message()` delegue a `ChatbotService`.
- `ChatbotService` tente Groq si une cle existe.
- Si Groq echoue, un fallback local repond a partir du profil, des projets, des competences, des certifications et de la base de connaissance.
- Les echecs distants sont traces dans `storage/logs/chatbot.log`.

## Structure du projet
Note : la section suivante decrit tous les fichiers custom importants du projet. Les fichiers tiers de `vendor/` sont regroupes par package et ne sont pas detailles un par un.

### Racine
- `index.php` : point d'entree racine, relaie vers `public/index.php`.
- `.htaccess` : redirige les requetes vers `public/`.
- `.env` : configuration principale privee.
- `.env.local` : surcharge locale optionnelle pour WAMP/XAMPP et les tests CLI.
- `.env.example` : modele de configuration de depart.
- `composer.json` : dependances Composer. Ici PHPMailer.
- `composer.lock` : version verrouillee des dependances.
- `README.md` : documentation du projet.

### `config/`
- `config/app.php` : expose les valeurs applicatives lues depuis `.env` puis `.env.local` si present.
- `config/database.php` : stocke la config DB lue depuis `.env` puis `.env.local` si present.
- `config/routes.php` : declare toutes les routes publiques, admin et API.

### `database/`
- `database/portfolio_os.sql` : schema principal complet de la base de donnees.

### `storage/`
- `storage/logs/chatbot.log` : journal des erreurs ou refus cote Groq.

### `vendor/`
- `vendor/autoload.php` : autoload Composer.
- `vendor/composer/*` : fichiers internes d'autoload.
- `vendor/phpmailer/phpmailer/*` : librairie email utilisee par `MailService`.

### `app/Core/`
- `app/Core/Bootstrap.php` : bootstrap global de l'application.
- `app/Core/Controller.php` : classe mere des controleurs, avec `view()`, `json()`, `requireAdmin()`, `validateCsrf()`.
- `app/Core/Database.php` : connexion PDO et execution SQL.
- `app/Core/Env.php` : chargeur `.env` et `.env.local`.
- `app/Core/Model.php` : mini ORM simple pour CRUD generique.
- `app/Core/Router.php` : routeur maison avec params dynamiques du type `{id}` ou `{slug}`.

### `app/controllers/`
- `PublicController.php` : pages publiques `home`, `about`, `projects`, `skills`, `certifications`, `blog`, `contact`.
- `AuthController.php` : login/logout admin et login API.
- `DashboardController.php` : page principale du back-office.
- `ProjectController.php` : CRUD projets en admin + endpoints API.
- `SkillController.php` : CRUD competences en admin + endpoints API.
- `CertificationController.php` : CRUD certifications en admin + endpoints API.
- `PostController.php` : CRUD blog en admin + endpoints API.
- `ProfileController.php` : edition du profil principal, reseaux, media et mot de passe.
- `ThemeController.php` : edition et reset du theme actif.
- `ContactController.php` : formulaire de contact public, liste admin et API contacts.
- `ChatbotController.php` : endpoint chatbot, base de connaissance et ecran de test admin.
- `CollaborationController.php` : gestion des collaborateurs lies aux projets.
- `NotificationController.php` : centre de notifications admin et endpoints associes.
- `AnalyticsController.php` : stats admin et endpoints analytics.

### `app/models/`
- `User.php` : utilisateurs admin et mise a jour du dernier login.
- `RememberToken.php` : tokens remember me persistants.
- `Profile.php` : profil principal du portfolio, source de verite pour nom, bio, photo, CV, video et reseaux.
- `Project.php` : projets publics, featured et recherche par slug.
- `Skill.php` : competences actives et regroupement par categorie.
- `Certification.php` : certifications actives et certifications qui expirent bientot.
- `Post.php` : articles publies, lecture par slug, incrementation des vues.
- `Contact.php` : messages de contact et compte des non lus.
- `Notification.php` : notifications internes du dashboard.
- `ChatbotKnowledge.php` : base de connaissance du chatbot.
- `Collaboration.php` : membres/collaborateurs lies a des projets.
- `Theme.php` : theme actif.
- `Analytics.php` : resume, timeline, pages, devices et pays.
- `Activity.php` : journal d'activite admin et visiteurs.

### `app/services/`
- `AuthService.php` : login, logout, restore remember me, bootstrap du premier admin.
- `ThemeService.php` : lecture du theme actif et generation des variables CSS.
- `SchemaService.php` : maintenance automatique du schema DB pour les colonnes ajoutees au fil du projet.
- `ProjectService.php` : generation et unicite des slugs.
- `MailService.php` : notification email avec PHPMailer.
- `NotificationService.php` : creation de notifications internes et alertes certifications.
- `AnalyticsService.php` : tracking serveur des visites publiques.
- `ActivityService.php` : journalisation des actions sans bloquer l'application.
- `ChatbotService.php` : cerveau du chatbot, Groq + fallback local + logs.

### `app/helpers/`
- `helpers.php` : helpers globaux (`url`, `asset`, `absolute_url`, `view`, `flash`, `excerpt`, `theme_defaults`, `profile_social_links`, `social_platform_icon`, `presentation_video_data`, etc.).
- `auth.php` : session utilisateur, CSRF token et helpers auth.
- `routing.php` : detection de l'URL courante et methode HTTP avec override `_method`.
- `response.php` : reponses JSON et detection API.
- `validation.php` : lecture des donnees, validations email/URL/date, nettoyage rich text.
- `upload.php` : upload securise des fichiers vers `public/assets/...`.

### `app/middleware/`
Ces fichiers existent pour de futures extensions. Aujourd'hui, les controleurs utilisent surtout `requireAdmin()` et `validateCsrf()` directement.
- `AuthMiddleware.php` : redirection vers `/admin/login` si non connecte.
- `AdminMiddleware.php` : alias de `AuthMiddleware`.
- `CsrfMiddleware.php` : verification de jeton CSRF.
- `AnalyticsMiddleware.php` : wrapper vers `AnalyticsService::track()`.

### `resources/layouts/`
- `resources/layouts/public.php` : layout du site public, meta SEO, variables CSS du theme, navbar, footer, widget chatbot.
- `resources/layouts/admin.php` : layout du dashboard admin, sidebar, topbar, alerts et assets admin.
- `resources/layouts/auth.php` : layout de la page de connexion admin.

### `resources/components/`
- `resources/components/navbar.php` : navbar publique, logo video `C-Y`, menu responsive, CTA.
- `resources/components/footer.php` : footer public avec liens et reseaux en icones uniquement.
- `resources/components/chatbot-widget.php` : structure HTML du widget chatbot.
- `resources/components/sidebar.php` : navigation laterale du dashboard admin.

### `resources/views/public/`
- `home.php` : hero principal, texte anime, reseaux, intro, resume rapide, apercu services/projets sans tout dupliquer.
- `about.php` : presentation detaillee, infos de profil, reseaux, competences resumees et video de presentation.
- `projects.php` : listing des projets publics.
- `project-detail.php` : detail d'un projet par slug.
- `skills.php` : competences par categorie.
- `certifications.php` : certifications publiques.
- `blog.php` : listing des articles publies.
- `blog-detail.php` : detail d'un article.
- `contact.php` : formulaire de contact et infos de contact.

### `resources/views/admin/`
- `dashboard.php` : KPI, timeline, alertes, notifications, activites.
- `projects.php` : liste admin des projets.
- `project-form.php` : formulaire create/edit projet.
- `skills.php` : gestion des competences.
- `certifications.php` : liste admin des certifications.
- `certification-form.php` : formulaire create/edit certification.
- `blog.php` : liste admin des articles.
- `blog-form.php` : formulaire create/edit article.
- `collaborations.php` : gestion des collaborateurs.
- `messages.php` : liste des messages recus.
- `message-show.php` : lecture detaillee d'un message.
- `notifications.php` : centre de notifications.
- `analytics.php` : vues statistiques et geo chart.
- `theme.php` : editeur du theme visuel.
- `chatbot.php` : base de connaissance, test du chatbot, affichage de la source et du statut distant.
- `profile.php` : edition du profil principal et du mot de passe admin.

### `resources/views/auth/`
- `login.php` : page de connexion admin redesign.

### `resources/views/errors/`
- `404.php` : page d'erreur 404.

### `public/`
- `public/index.php` : front-controller principal.
- `public/api/README.md` : note rapide sur les endpoints REST.

### `public/assets/css/`
- `main.css` : style principal du site public, hero, pages internes, footer, chatbot, responsive, home, about, etc.
- `admin.css` : style du dashboard admin et de la page de connexion admin.
- `theme.css` : rappel que les variables CSS du theme sont injectees depuis PHP.
- `animations.css` : animations optionnelles chargees si le theme les active.

### `public/assets/js/`
- `main.js` : menu mobile, header scrolle, animation du texte du hero, reveals au scroll.
- `chatbot.js` : ouverture du widget, historique local, appel API chatbot.
- `admin.js` : autogrow textarea, preview images, mini rich editor, preview du theme, geo chart analytics.
- `theme-engine.js` : placeholder pour evolutions futures de preview theme.
- `analytics.js` : placeholder indiquant que le tracking est fait cote serveur.

### `public/assets/vendor/`
- `bootstrap-icons/` : police et CSS d'icones utilises dans le site et l'admin.

### `public/assets/uploads/`
- `public/assets/uploads/C-y.mp4` : logo video utilise dans la navbar.
- `public/assets/uploads/profile/*.jpeg` : avatar de profil courant.
- `public/assets/uploads/profile/*.pdf` : CV courant.
- D'autres fichiers peuvent etre ajoutes automatiquement via `upload_file()`.

## Pages publiques : logique fonctionnelle
### Accueil
La page `home` a ete pensee pour etre plus riche, mais sans redevenir une one-page qui remonte toutes les autres pages.

Ce qu'elle fait :
- affiche le hero, la photo, le titre et le texte anime
- affiche les reseaux sociaux
- affiche une intro courte sur le profil
- affiche des stats resumees
- affiche un apercu des expertises et projets
- renvoie ensuite vers les pages dediees (`about`, `skills`, `projects`, `contact`)

Le controleur limite volontairement le contenu avec `array_slice()` pour eviter l'effet "tout le site sur l'accueil".

### A propos
La page `about` centralise :
- le profil complet
- les infos principales
- les reseaux additionnels
- un resume des competences
- la video de presentation

La video n'est pas affichee sur l'accueil. Elle reste ici pour garder une vraie separation des pages.

### Projets, competences, certifications, blog, contact
Chaque page a son controleur dedie, son rendu public et, quand utile, son equivalent admin/API.

## Dashboard admin : ce qu'il permet
Le dashboard sert a piloter le portfolio sans toucher au code pour les contenus principaux.

Tu peux y gerer :
- le profil principal
- les projets
- les competences
- les certifications
- le blog
- les collaborateurs
- les messages recus
- le theme graphique
- la base de connaissance du chatbot
- les notifications
- les analytiques

## Base de donnees : tables principales
- `users` : comptes admin.
- `remember_tokens` : cookies remember me securises.
- `profiles` : profil principal du portfolio.
- `projects` : projets publics et featured.
- `skills` : competences classees.
- `certifications` : certifications et dates d'expiration.
- `posts` : articles de blog.
- `contacts` : messages provenant du formulaire de contact.
- `notifications` : notifications internes dashboard.
- `chatbot_knowledge` : paires question/reponse pour le chatbot.
- `collaborations` : collaborateurs lies aux projets.
- `themes` : theme actif.
- `analytics` : pages visitees, device, pays, sessions.
- `activities` : journal d'activite application/admin.

## API REST disponible
### Auth
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`

### Projects
- `GET /api/v1/projects`
- `POST /api/v1/projects`
- `PUT /api/v1/projects/{id}`
- `DELETE /api/v1/projects/{id}`

### Skills
- `GET /api/v1/skills`
- `POST /api/v1/skills`
- `PUT /api/v1/skills/{id}`
- `DELETE /api/v1/skills/{id}`

### Certifications
- `GET /api/v1/certifications`
- `POST /api/v1/certifications`
- `PUT /api/v1/certifications/{id}`
- `DELETE /api/v1/certifications/{id}`

### Blog
- `GET /api/v1/posts`
- `POST /api/v1/posts`
- `PUT /api/v1/posts/{id}`
- `DELETE /api/v1/posts/{id}`

### Contacts
- `POST /api/v1/contacts`
- `GET /api/v1/contacts`

### Notifications
- `GET /api/v1/notifications`
- `PUT /api/v1/notifications/{id}/read`

### Chatbot
- `POST /api/v1/chatbot/message`
- `GET /api/v1/chatbot/knowledge`
- `POST /api/v1/chatbot/knowledge`

### Analytics
- `GET /api/v1/analytics/summary`
- `GET /api/v1/analytics/pages`

## Systeme de theme
Le theme n'est pas un simple CSS statique. Il est pilote en base et injecte en variables CSS.

Pieces clefs :
- `ThemeController.php` : enregistre ou reset le theme.
- `ThemeService.php` : lit le theme actif et construit les variables CSS.
- `resources/layouts/public.php`, `resources/layouts/admin.php`, `resources/layouts/auth.php` : injectent ces variables dans la page.
- `public/assets/js/admin.js` : donne une preview live dans l'ecran theme.

Conseil : pour changer les couleurs ou polices globales, passer d'abord par l'admin theme plutot que de hardcoder partout dans `main.css`.

## Systeme reseaux sociaux
La source principale des reseaux est `profiles` via `ProfileController`.

Points importants :
- les champs dedies existent pour GitHub, LinkedIn, Twitter/X, Instagram, WhatsApp et Facebook
- les autres liens peuvent etre saisis dans `other_links`
- format recommande de `other_links` : une ligne par lien au format `Label | https://exemple.com`
- `profile_social_links()` reconstitue la liste standard
- `social_platform_icon()` choisit l'icone selon le label ou l'URL

Validation importante :
- les liens reseaux doivent etre de vraies URLs absolues (`https://...`)
- les media locaux comme avatar, CV ou video peuvent maintenant etre saisis en chemin relatif du type `assets/uploads/...`

## Systeme video de presentation
La video de presentation se base sur `profiles.presentation_video_url`.

Le helper `presentation_video_data()` accepte :
- YouTube
- Vimeo
- fichier video direct (`mp4`, `webm`, `ogg`)

La video est rendue dans `resources/views/public/about.php` et pas sur l'accueil.

## Systeme chatbot
### Fichiers clefs
- `resources/components/chatbot-widget.php` : widget HTML
- `public/assets/js/chatbot.js` : logique front
- `app/controllers/ChatbotController.php` : endpoint et admin knowledge
- `app/services/ChatbotService.php` : logique metier
- `resources/views/admin/chatbot.php` : console admin du chatbot
- `storage/logs/chatbot.log` : logs des erreurs distantes

### Comment il repond
1. Le widget envoie le message et un petit historique a `/api/v1/chatbot/message`.
2. `ChatbotService` tente d'abord Groq si `GROQ_API_KEY` existe.
3. Si Groq repond correctement, la reponse distante est utilisee.
4. Sinon, le service bascule sur une reponse locale basee sur :
   - le profil
   - les projets publics
   - les competences
   - les certifications
   - la base `chatbot_knowledge`
5. Le service renvoie aussi la source (`groq` ou `local`) dans la zone admin de test.

### Etat actuel a connaitre
Le code de connexion Groq a ete fiabilise, notamment pour la gestion SSL/CA sous Windows. En revanche, la cle Groq actuellement testee retourne un `403` lie a l'absence de credits/licence sur le compte equipe Groq. Cela veut dire :
- le chatbot n'est pas casse en code
- le fallback local fonctionne
- pour activer la vraie reponse distante, il faut une cle Groq valide et active

### Comment le rendre plus intelligent
- remplir correctement le profil admin
- publier de vrais projets et competences
- ajouter des connaissances dans `/admin/chatbot`
- fournir une cle Groq valide avec credits
- surveiller `storage/logs/chatbot.log` si la partie distante ne repond pas

## Systeme analytics
Le tracking analytics est surtout serveur-side.

Pieces clefs :
- `AnalyticsService::track()` est appele sur les pages publiques.
- Les visites admin et API sont ignorees.
- Les donnees sont stockees dans `analytics`.
- `AnalyticsController` et `Analytics` prepareront resume, timeline, pages, devices et pays.
- `admin.js` peut charger un GeoChart Google Charts si le script distant est disponible.

## Systeme mail
Les notifications de contact utilisent `MailService` et PHPMailer.

Fonctionnement :
- si `MAIL_HOST` ou `MAIL_MAILER=smtp` est configure, PHPMailer passe en SMTP
- sinon PHPMailer peut tenter `mail()`
- l'adresse destinataire principale est `MAIL_TO`
- le reply-to essaie de reprendre l'email du visiteur

## Fichiers clefs du redesign recent
Cette liste resume les fichiers les plus importants modifies/adaptes pour l'etat actuel du projet :
- `public/assets/css/main.css` : style global public, home, hero, mobile, footer, chatbox
- `public/assets/css/admin.css` : style dashboard et login admin
- `resources/views/public/home.php` : accueil enrichi, texte anime, apercus structures
- `resources/views/public/about.php` : presentation detaillee + video de presentation
- `resources/components/navbar.php` : branding `C-Y` + logo video
- `resources/components/footer.php` : reseaux en icones uniquement
- `resources/views/auth/login.php` : nouvelle page de connexion admin
- `resources/layouts/auth.php` : layout premium pour la connexion admin
- `app/controllers/ProfileController.php` : sauvegarde profil et validation reseaux/media
- `app/helpers/validation.php` : validation des URLs et assets publics
- `app/helpers/helpers.php` : helpers sociaux, video, theme par defaut
- `app/services/ChatbotService.php` : logique chatbot, Groq, fallback, logs
- `app/controllers/ChatbotController.php` : endpoint et diagnostics admin
- `public/assets/js/chatbot.js` : experience front du chatbot
- `public/assets/js/main.js` : menu mobile, animation de texte, reveals

## Conseils pour mieux utiliser ce code
- Utiliser `admin/profile` comme source de verite pour le nom, la bio, la photo, le CV, la video et les reseaux. Beaucoup de sections du site en dependent.
- Utiliser `admin/theme` pour la direction visuelle avant de modifier le CSS a la main.
- Garder la logique metier dans les controleurs/services, pas dans les vues.
- Garder les vues simples : affichage HTML + petites conditions uniquement.
- Utiliser `absolute_url()`, `asset()` et `url()` plutot que concatenner les chemins a la main.
- Utiliser `sanitize_rich_text()` pour les contenus riches du blog/projets.
- Stocker les fichiers uploades via `upload_file()` pour garder des chemins propres dans `public/assets/uploads`.
- Ne pas modifier `vendor/` a la main.
- Si tu ajoutes un nouveau reseau social dedie, penser a mettre a jour a la fois la DB, `ProfileController`, `validation.php`, `profile_social_links()` et `social_platform_icon()`.
- Si tu ajoutes une nouvelle page publique, faire le trio route + controleur + vue, puis ajouter le lien dans `navbar.php` et/ou `footer.php` si necessaire.
- Si tu ajoutes un nouveau module admin, faire le trio table SQL + model + controller + vue admin.

## Limitations et points d'attention
- Le projet n'utilise pas un framework complet type Laravel : tout est maison, donc il faut garder une discipline de structure.
- `SchemaService` joue le role de mini migration additive, mais ce n'est pas un vrai systeme de migrations versionnees.
- Le tracking analytics est basique et serveur-side.
- Le chatbot distant depend vraiment du compte Groq et de ses credits/licences.
- Les layouts lisent les variables de theme a chaque rendu, ce qui est pratique mais centralise beaucoup de responsabilites dans `ThemeService`.
- Les middlewares existent mais ne sont pas encore branches comme dans un framework full-stack ; les controleurs gerent eux-memes une bonne partie des gardes.

## Checklist de maintenance
- Verifier `APP_URL` des qu'un lien ou asset casse.
- Verifier la base `portfolio_os` si une page n'affiche plus ses donnees.
- Verifier `storage/logs/chatbot.log` si le chatbot distant repond mal.
- Verifier `MAIL_*` si les notifications de contact ne partent pas.
- Faire un hard refresh du navigateur apres gros changements CSS/JS.
- Garder `ADMIN_BOOTSTRAP_ENABLED=false` une fois le premier admin cree.
- Sauvegarder `public/assets/uploads/` et la base de donnees en meme temps pour ne pas perdre les references medias.

## Resume final
Ce projet est aujourd'hui un portfolio complet, administrable, responsive et coherent visuellement. Le coeur du systeme repose sur un MVC leger, une base MySQL, un theme pilote par variables CSS, un profil central qui nourrit plusieurs pages, un dashboard pour gerer les contenus, et un chatbot capable de repondre localement meme si le fournisseur IA distant n'est pas disponible.

Si tu veux continuer a le faire evoluer proprement, la meilleure logique est toujours la meme :
- la base porte la donnee
- les models lisent cette donnee
- les services encapsulent la logique transverse
- les controleurs preparent les ecrans
- les vues affichent
- le theme harmonise le tout
