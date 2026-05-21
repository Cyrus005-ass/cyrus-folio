<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Collaboration;
use App\Models\Certification;
use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Services\AnalyticsService;
use App\Services\NotificationService;

class PublicController extends Controller
{
    private ?array $profileCache = null;

    private function profile(): ?array
    {
        if ($this->profileCache === null) {
            $this->profileCache = (new Profile())->current();
        }

        return $this->profileCache;
    }

    private function baseData(array $data = []): array
    {
        $profile = $this->profile();
        $profileName = is_array($profile) ? (string) ($profile['full_name'] ?? '') : '';
        $profileAvatar = is_array($profile) ? (string) ($profile['avatar_url'] ?? '') : '';

        return array_merge([
            'profile' => $profile,
            'metaAuthor' => $profileName !== '' ? $profileName : (string) app_config('author', app_config('name', 'Cyrus-y ASSOGBA')),
            'metaImage' => $profileAvatar !== '' ? $profileAvatar : (string) app_config('og_image', ''),
        ], $data);
    }

    private function defaultMetaDescription(): string
    {
        $profile = $this->profile();
        $bio = is_array($profile) ? (string) ($profile['bio'] ?? '') : '';
        $fallback = (string) app_config('description', app_config('name', 'Cyrus-y ASSOGBA'));

        return excerpt($bio !== '' ? $bio : $fallback, 170);
    }

    public function home(): void
    {
        AnalyticsService::track();

        $projectModel = new Project();
        $skillModel = new Skill();
        $projects = $projectModel->publicAll();
        $featuredProjects = $projectModel->featured();
        $allSkills = $skillModel->active();
        $groupedSkills = $skillModel->groupedActive();
        $certifications = (new Certification())->active();

        $orderedGroups = [];
        foreach (['Frameworks', 'Langages', 'Outils', 'Securite', 'Autre'] as $category) {
            if (isset($groupedSkills[$category])) {
                $orderedGroups[$category] = $groupedSkills[$category];
            }
        }
        foreach ($groupedSkills as $category => $items) {
            if (!isset($orderedGroups[$category])) {
                $orderedGroups[$category] = $items;
            }
        }

        $homeStats = array_values(array_filter([
            ['value' => (string) count($projects), 'label' => 'Projets publiés'],
            ['value' => (string) count($allSkills), 'label' => 'Compétences actives'],
            ['value' => (string) count($certifications), 'label' => 'Certifications'],
        ], static fn (array $item): bool => (int) ($item['value'] ?? 0) > 0));

        $this->view('public/home', $this->baseData([
            'title' => 'Accueil',
            'metaDescription' => $this->defaultMetaDescription(),
            'featuredProjects' => $featuredProjects !== [] ? $featuredProjects : array_slice($projects, 0, 3),
            'homeSkills' => array_slice($allSkills, 0, 8),
            'homeSkillGroups' => array_slice($orderedGroups, 0, 3, true),
            'homeStats' => $homeStats,
        ]));
    }

    public function projects(): void
    {
        AnalyticsService::track();
        $profile = $this->profile();
        $profileName = is_array($profile) ? (string) ($profile['full_name'] ?? '') : '';
        $technology = $_GET['tech'] ?? null;
        $projects = (new Project())->publicAll();
        if ($technology) {
            $projects = array_values(array_filter($projects, fn ($p) => str_contains(mb_strtolower($p['technologies'] ?? ''), mb_strtolower($technology))));
        }
        $description = $technology
            ? 'Selection de projets autour de ' . $technology . ' dans le portfolio de ' . ($profileName !== '' ? $profileName : (string) app_config('name', 'Cyrus-y ASSOGBA')) . '.'
            : 'Decouvre les projets, stacks techniques et démonstrations du portfolio de ' . ($profileName !== '' ? $profileName : (string) app_config('name', 'Cyrus-y ASSOGBA')) . '.';

        $this->view('public/projects', $this->baseData([
            'title' => 'Projets',
            'metaDescription' => $description,
            'projects' => $projects,
            'technology' => $technology,
        ]));
    }

    public function projectDetail(string $slug): void
    {
        AnalyticsService::track();
        $project = (new Project())->findBySlug($slug);
        if (!$project || $project['statut'] !== 'publie') {
            http_response_code(404);
            $this->view('errors/404', ['uri' => current_uri()]);
            return;
        }

        $collaborations = (new Collaboration())->all('created_at ASC', 'project_id = ?', [(int) $project['id']]);

        $metaDescription = excerpt((string) (($project['description'] ?? '') ?: strip_tags((string) ($project['contenu'] ?? ''))), 170);

        $this->view('public/project-detail', $this->baseData([
            'title' => (string) ($project['titre'] ?? 'Projet'),
            'metaDescription' => $metaDescription,
            'metaType' => 'article',
            'metaImage' => (string) ($project['image_url'] ?? ''),
            'project' => $project,
            'collaborations' => $collaborations,
        ]));
    }

    public function skills(): void
    {
        AnalyticsService::track();
        $this->view('public/skills', $this->baseData([
            'title' => 'Compétences',
            'metaDescription' => 'Compétences techniques, outils et niveaux maîtrisés sur le portfolio de ' . (is_array($this->profile()) ? ((string) (($this->profile()['full_name'] ?? '') ?: app_config('name', 'Cyrus-y ASSOGBA'))) : (string) app_config('name', 'Cyrus-y ASSOGBA')) . '.',
            'groups' => (new Skill())->groupedActive(),
        ]));
    }

    public function certifications(): void
    {
        AnalyticsService::track();
        $this->view('public/certifications', $this->baseData([
            'title' => 'Certifications',
            'metaDescription' => 'Certifications, badges et liens de verification du portfolio de ' . (is_array($this->profile()) ? ((string) (($this->profile()['full_name'] ?? '') ?: app_config('name', 'Cyrus-y ASSOGBA'))) : (string) app_config('name', 'Cyrus-y ASSOGBA')) . '.',
            'certifications' => (new Certification())->active(),
        ]));
    }

    public function blog(): void
    {
        AnalyticsService::track();
        $this->view('public/blog', $this->baseData([
            'title' => 'Blog',
            'metaDescription' => "Articles, retours d'expérience et veille technique publiés sur le portfolio de " . (is_array($this->profile()) ? ((string) (($this->profile()['full_name'] ?? '') ?: app_config('name', 'Cyrus-y ASSOGBA'))) : (string) app_config('name', 'Cyrus-y ASSOGBA')) . '.',
            'posts' => (new Post())->published(),
        ]));
    }

    public function blogDetail(string $slug): void
    {
        AnalyticsService::track();
        $post = (new Post())->findBySlug($slug);
        if (!$post || $post['statut'] !== 'publie') {
            http_response_code(404);
            $this->view('errors/404', ['uri' => current_uri()]);
            return;
        }

        $postModel = new Post();
        $postModel->incrementViews((int) $post['id']);
        $post = $postModel->find((int) $post['id']) ?? $post;

        $thresholds = array_values(array_filter(array_map('intval', explode(',', (string) env('POST_VIEW_NOTIFICATION_THRESHOLDS', '10,50,100,250,500')))));
        if (in_array((int) ($post['view_count'] ?? 0), $thresholds, true)) {
            NotificationService::push('post', 'Palier de vues atteint', 'L article ' . ($post['titre'] ?? 'du blog') . ' a atteint ' . (int) ($post['view_count'] ?? 0) . ' vues.', '/admin/blog', 'post-views:' . (int) $post['id'] . ':' . (int) ($post['view_count'] ?? 0));
        }

        $metaDescription = excerpt((string) (($post['extrait'] ?? '') ?: strip_tags((string) ($post['contenu'] ?? ''))), 170);

        $this->view('public/blog-detail', $this->baseData([
            'title' => (string) ($post['titre'] ?? 'Article'),
            'metaDescription' => $metaDescription,
            'metaType' => 'article',
            'metaImage' => (string) ($post['image_url'] ?? ''),
            'post' => $post,
        ]));
    }

    public function contact(): void
    {
        AnalyticsService::track();
        $this->view('public/contact', $this->baseData([
            'title' => 'Contact',
            'metaDescription' => 'Contacte ' . (is_array($this->profile()) ? ((string) (($this->profile()['full_name'] ?? '') ?: app_config('name', 'Cyrus-y ASSOGBA'))) : (string) app_config('name', 'Cyrus-y ASSOGBA')) . ' pour une mission, une collaboration ou une prise de contact.',
        ]));
    }

    public function about(): void
    {
        AnalyticsService::track();
        $projects = (new Project())->publicAll();
        $allSkills = (new Skill())->active();
        $certifications = (new Certification())->active();
        $aboutStats = [
            ['value' => (string) count($projects), 'label' => 'Projets'],
            ['value' => (string) count($allSkills), 'label' => 'Compétences'],
            ['value' => (string) count($certifications), 'label' => 'Certifications'],
        ];
        $hasStats = array_sum(array_map(static fn ($item) => (int) ($item['value'] ?? 0), $aboutStats)) > 0;

        $this->view('public/about', $this->baseData([
            'title' => 'À propos',
            'metaDescription' => 'En savoir plus sur ' . (is_array($this->profile()) ? ((string) (($this->profile()['full_name'] ?? '') ?: app_config('name', 'Cyrus-y ASSOGBA'))) : (string) app_config('name', 'Cyrus-y ASSOGBA')) . ', son parcours, son profil et sa présentation.',
            'skills' => array_slice($allSkills, 0, 6),
            'aboutStats' => $hasStats ? $aboutStats : [],
        ]));
    }
}
