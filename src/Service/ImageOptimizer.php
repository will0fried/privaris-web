<?php

namespace App\Service;

/**
 * Post-traitement des images uploadees depuis l'admin.
 *
 * Apres upload par EasyAdmin, on reecrit le fichier sur disque :
 *   - redimensionnement a la taille max utile (economise CPU cote affichage)
 *   - conversion WebP (qualite 82 : excellent compromis taille/rendu)
 *   - suppression du fichier source si l'extension change
 *
 * Aucune dependance externe : uniquement GD (livree avec PHP sur PlanetHoster).
 * Si GD n'est pas dispo ou si l'image est invalide, on renvoie le nom original
 * (degrade gracieux : l'image existe sans l'optim).
 */
final class ImageOptimizer
{
    public function __construct(
        private readonly string $projectDir,
    ) {}

    /**
     * Optimise le fichier designe par $filename dans /public/$subdir/.
     *
     * @param string $filename      Nom de fichier relatif (ex: "cover-foo-abc.jpg")
     * @param string $subdir        Sous-dossier de public/ (ex: "uploads/articles")
     * @param int    $maxWidth      Largeur max souhaitee (0 = pas de contrainte)
     * @param int    $maxHeight     Hauteur max souhaitee (0 = pas de contrainte)
     * @param int    $quality       Qualite WebP 0-100 (82 est l'optimum visuel)
     *
     * @return string Nom du fichier apres optim (potentiellement .webp)
     */
    public function optimize(
        string $filename,
        string $subdir,
        int $maxWidth = 1600,
        int $maxHeight = 900,
        int $quality = 82,
    ): string {
        // GD est requis
        if (!\function_exists('imagecreatefromstring')
            || !\function_exists('imagewebp')) {
            return $filename;
        }

        $absDir  = rtrim($this->projectDir, '/') . '/public/' . trim($subdir, '/');
        $srcPath = $absDir . '/' . $filename;

        if (!is_file($srcPath)) {
            return $filename;
        }

        $binary = @file_get_contents($srcPath);
        if ($binary === false || $binary === '') {
            return $filename;
        }

        $src = @imagecreatefromstring($binary);
        if ($src === false) {
            // Pas une image valide (ou format non supporte par GD) — on laisse tel quel.
            return $filename;
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Calcul du redimensionnement proportionnel (on n'agrandit jamais).
        [$newW, $newH] = $this->fit($origW, $origH, $maxWidth, $maxHeight);

        if ($newW !== $origW || $newH !== $origH) {
            $dst = imagecreatetruecolor($newW, $newH);
            // Fond blanc pour les PNG transparents converts en WebP.
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
            imagedestroy($src);
            $src = $dst;
        }

        // Nouveau nom : meme base, extension .webp
        $pathInfo  = pathinfo($filename);
        $baseName  = $pathInfo['filename'] ?? 'image';
        $newName   = $baseName . '.webp';
        $destPath  = $absDir . '/' . $newName;

        $ok = @imagewebp($src, $destPath, $quality);
        imagedestroy($src);

        if (!$ok) {
            return $filename;
        }

        // Supprime l'original s'il a une extension differente (evite les doublons).
        if (strtolower($pathInfo['extension'] ?? '') !== 'webp' && $srcPath !== $destPath) {
            @unlink($srcPath);
        }

        return $newName;
    }

    /**
     * Calcule les dimensions cibles en preservant le ratio.
     * Si maxW ou maxH vaut 0, la contrainte est ignoree.
     *
     * @return array{0:int,1:int}
     */
    private function fit(int $w, int $h, int $maxW, int $maxH): array
    {
        if ($w <= 0 || $h <= 0) {
            return [$w, $h];
        }

        $ratio = 1.0;
        if ($maxW > 0 && $w > $maxW) {
            $ratio = min($ratio, $maxW / $w);
        }
        if ($maxH > 0 && $h > $maxH) {
            $ratio = min($ratio, $maxH / $h);
        }

        if ($ratio >= 1.0) {
            return [$w, $h];
        }

        return [
            max(1, (int) floor($w * $ratio)),
            max(1, (int) floor($h * $ratio)),
        ];
    }

    /**
     * Supprime un fichier image si present. Utile quand l'admin remplace
     * ou retire une image existante.
     */
    public function delete(string $filename, string $subdir): void
    {
        if ($filename === '') {
            return;
        }
        // Ne supprime que les chemins relatifs (pas les URL externes).
        if (preg_match('#^https?://#i', $filename) || str_starts_with($filename, '//')) {
            return;
        }
        $absPath = rtrim($this->projectDir, '/') . '/public/' . trim($subdir, '/') . '/' . basename($filename);
        if (is_file($absPath)) {
            @unlink($absPath);
        }
    }
}
