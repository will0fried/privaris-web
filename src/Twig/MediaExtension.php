<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Resolveur d'URL pour les images : accepte indifferemment une URL absolue
 * (ancien champ UrlField) ou un nom de fichier relatif (nouveau champ
 * ImageField qui stocke juste "cover-foo-abc.webp").
 *
 * Usage : {{ article.coverImageUrl|media_url('articles') }}
 */
final class MediaExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('media_url', [$this, 'mediaUrl']),
        ];
    }

    /**
     * @param string|null $value Valeur brute enregistree en base.
     * @param string      $type  Dossier sous public/uploads/ (ex: "articles").
     */
    public function mediaUrl(?string $value, string $type): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        // URL absolue (http/https ou protocol-relative) → on laisse passer tel quel.
        if (preg_match('#^https?://#i', $value) || str_starts_with($value, '//')) {
            return $value;
        }

        // Chemin deja prefixe par /uploads ou /images → on le respecte.
        if (str_starts_with($value, '/')) {
            return $value;
        }

        // Sinon : chemin relatif uploade depuis l'admin.
        return '/uploads/' . trim($type, '/') . '/' . ltrim($value, '/');
    }
}
