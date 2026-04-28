<?php

namespace App\Service;

use App\Entity\Episode;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Génère un flux RSS 2.0 podcast compatible iTunes / Apple Podcasts / Spotify.
 * Spec iTunes : https://podcasters.apple.com/support/823-podcast-requirements
 */
class PodcastFeedGenerator
{
    public function __construct(
        private readonly UrlGeneratorInterface $urls,
        /** @var array<string, mixed> */
        private readonly array $config,
    ) {}

    /** @param Episode[] $episodes */
    public function generate(array $episodes): string
    {
        $base = $this->urls->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $feedUrl = $this->urls->generate('app_podcast_feed', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $siteUrl = rtrim($base, '/');
        $coverAbs = str_starts_with($this->config['cover_url'], 'http')
            ? $this->config['cover_url']
            : $siteUrl.$this->config['cover_url'];

        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'utf-8');

        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:itunes', 'http://www.itunes.com/dtds/podcast-1.0.dtd');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->writeAttribute('xmlns:content', 'http://purl.org/rss/1.0/modules/content/');
        $xml->writeAttribute('xmlns:googleplay', 'http://www.google.com/schemas/play-podcasts/1.0');

        $xml->startElement('channel');

        // Atom self-link
        $xml->startElement('atom:link');
        $xml->writeAttribute('href', $feedUrl);
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        $xml->writeElement('title', (string) $this->config['title']);
        $xml->writeElement('link', $siteUrl);
        $xml->writeElement('language', (string) $this->config['language']);
        $xml->writeElement('copyright', '© '.date('Y').' '.$this->config['author']);
        $xml->writeElement('description', (string) $this->config['description']);
        $xml->writeElement('itunes:subtitle', (string) $this->config['subtitle']);
        $xml->writeElement('itunes:summary', (string) $this->config['description']);
        $xml->writeElement('itunes:author', (string) $this->config['author']);
        $xml->writeElement('itunes:explicit', $this->config['explicit'] ? 'true' : 'false');
        $xml->writeElement('itunes:type', 'episodic');

        // Owner
        $xml->startElement('itunes:owner');
        $xml->writeElement('itunes:name',  (string) $this->config['author']);
        $xml->writeElement('itunes:email', (string) $this->config['email']);
        $xml->endElement();

        // Image
        $xml->startElement('itunes:image');
        $xml->writeAttribute('href', $coverAbs);
        $xml->endElement();

        // Category
        $xml->startElement('itunes:category');
        $xml->writeAttribute('text', (string) $this->config['category']);
        $xml->endElement();

        // Items
        foreach ($episodes as $ep) {
            $episodeUrl = $this->urls->generate('app_podcast_show', ['slug' => $ep->getSlug()], UrlGeneratorInterface::ABSOLUTE_URL);
            $audioUrl = $ep->getAudioUrl();

            $xml->startElement('item');
            $xml->writeElement('title', (string) $ep->getTitle());
            $xml->writeElement('link', $episodeUrl);
            $xml->writeElement('guid', $episodeUrl);

            if ($ep->getPublishedAt()) {
                $xml->writeElement('pubDate', $ep->getPublishedAt()->format(\DateTimeInterface::RFC2822));
            }

            $xml->writeElement('description', (string) $ep->getExcerpt());
            $xml->startElement('content:encoded');
            $xml->writeCdata((string) $ep->getDescription());
            $xml->endElement();

            $xml->writeElement('itunes:subtitle', (string) $ep->getExcerpt());
            $xml->writeElement('itunes:duration', $ep->getDuration());
            $xml->writeElement('itunes:episode', (string) $ep->getNumber());
            $xml->writeElement('itunes:season', (string) preg_replace('/\D/', '', $ep->getSeason()));
            $xml->writeElement('itunes:episodeType', $ep->getEpisodeType());
            $xml->writeElement('itunes:explicit', $ep->isExplicit() ? 'true' : 'false');

            // Enclosure = fichier audio
            $xml->startElement('enclosure');
            $xml->writeAttribute('url', $audioUrl);
            $xml->writeAttribute('length', (string) $ep->getAudioSizeBytes());
            $xml->writeAttribute('type', $ep->getAudioMimeType());
            $xml->endElement();

            // Image d'épisode
            if ($ep->getCoverImageUrl()) {
                $xml->startElement('itunes:image');
                $xml->writeAttribute('href', $ep->getCoverImageUrl());
                $xml->endElement();
            }

            $xml->endElement(); // item
        }

        $xml->endElement(); // channel
        $xml->endElement(); // rss
        $xml->endDocument();

        return $xml->outputMemory();
    }
}
